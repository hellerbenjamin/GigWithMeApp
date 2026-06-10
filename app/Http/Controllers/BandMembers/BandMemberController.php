<?php

namespace App\Http\Controllers\BandMembers;

use App\Enums\BandUserRoleEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandMembers\DestroyBandMemberRequest;
use App\Http\Requests\BandMembers\StoreBandMemberRequest;
use App\Http\Requests\BandMembers\UpdateBandMemberRequest;
use App\Models\User;
use App\Services\BandMemberService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The active band's roster. Listing is open to any member; adding people is
 * limited to owners and admins (enforced here for the create screen and in
 * {@see StoreBandMemberRequest} for the write).
 */
class BandMemberController extends Controller
{
    /**
     * List the active band's members with their roles.
     */
    public function index(): Response
    {
        $band = ActiveBand::band();

        $members = $band->users()
            ->orderBy('name')
            ->get(['users.id', 'name', 'email', 'phone_number'])
            ->map(function (User $user) {
                // The belongsToMany pivot hands back `role` as a plain string
                // (it's a generic Pivot, not the cast-aware BandUser model).
                $role = BandUserRoleEnum::from($user->pivot->role);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phoneNumber' => $user->phone_number,
                    'role' => $role->value,
                    'roleLabel' => $role->label(),
                    'isYou' => $user->id === auth()->id(),
                ];
            });

        return Inertia::render('BandMembers/Index', [
            'members' => $members,
            'canManage' => $this->canManage(),
        ]);
    }

    /**
     * Show the add-member form.
     */
    public function create(): Response
    {
        abort_unless($this->canManage(), 403);

        return Inertia::render('BandMembers/Create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    /**
     * Add a member to the active band.
     */
    public function store(StoreBandMemberRequest $request, BandMemberService $members): RedirectResponse
    {
        ['user' => $user, 'created' => $created] = $members->addMember(
            ActiveBand::band(),
            $request->validated(),
        );

        $message = $created
            ? "{$user->name} is on the roster. We emailed them an invitation to set up their gig alerts."
            : "{$user->name} is on the roster.";

        return to_route('band-members.index')->with('success', $message);
    }

    /**
     * Show the edit form for a roster member.
     */
    public function edit(User $user): Response
    {
        abort_unless($this->canManage(), 403);

        $band = ActiveBand::band();
        abort_unless($band->users()->whereKey($user->getKey())->exists(), 404);

        return Inertia::render('BandMembers/Edit', [
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phoneNumber' => $user->phone_number,
                'smsConsent' => $user->hasSmsConsent(),
                'role' => $band->getUserRole($user)->value,
            ],
            'roles' => $this->roleOptions(),
        ]);
    }

    /**
     * Update a roster member's details and role. Editing reaches into the
     * user's shared account (name / email / phone), so it's limited to managers
     * and blocked when it would demote the band's last owner.
     */
    public function update(UpdateBandMemberRequest $request, User $user, BandMemberService $members): RedirectResponse
    {
        $band = ActiveBand::band();
        abort_unless($band->users()->whereKey($user->getKey())->exists(), 404);

        $data = $request->validated();

        if ($members->wouldDemoteLastOwner($band, $user, BandUserRoleEnum::from($data['role']))) {
            return back()->with('error', "{$user->name} is the band's only owner — make someone else an owner first.");
        }

        $members->updateMember($band, $user, $data);

        return to_route('band-members.index')->with('success', "{$user->name}'s details were updated.");
    }

    /**
     * Remove a member from the active band's roster. The user's account is left
     * intact; only their place in this band is removed. Blocked when it would
     * leave the band without an owner.
     */
    public function destroy(DestroyBandMemberRequest $request, User $user, BandMemberService $members): RedirectResponse
    {
        $band = ActiveBand::band();

        // Only people actually on this roster can be removed from it.
        abort_unless($band->users()->whereKey($user->getKey())->exists(), 404);

        if ($members->wouldLeaveBandOwnerless($band, $user)) {
            return back()->with('error', "{$user->name} is the band's only owner — make someone else an owner first.");
        }

        $members->removeMember($band, $user);

        $message = $user->is($request->user())
            ? 'You left the band.'
            : "{$user->name} was removed from the roster.";

        return to_route('band-members.index')->with('success', $message);
    }

    /**
     * Whether the current user may manage the active band's roster.
     */
    private function canManage(): bool
    {
        return in_array(
            ActiveBand::band()?->getUserRole(auth()->user()),
            [BandUserRoleEnum::Owner, BandUserRoleEnum::Admin],
            true,
        );
    }

    /**
     * Role options as { value, label } for the add / edit selects.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            static fn (BandUserRoleEnum $role) => ['value' => $role->value, 'label' => $role->label()],
            BandUserRoleEnum::cases(),
        );
    }
}
