<?php

namespace App\Http\Controllers\BandMembers;

use App\Enums\BandUserRoleEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandMembers\StoreBandMemberRequest;
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
            ->get(['users.id', 'name', 'email'])
            ->map(function (User $user) {
                // The belongsToMany pivot hands back `role` as a plain string
                // (it's a generic Pivot, not the cast-aware BandUser model).
                $role = BandUserRoleEnum::from($user->pivot->role);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
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
            'roles' => array_map(
                static fn (BandUserRoleEnum $role) => ['value' => $role->value, 'label' => $role->label()],
                BandUserRoleEnum::cases(),
            ),
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
            ? "{$user->name} has an account and is on the roster — they can set a password via \"forgot password\"."
            : "{$user->name} is on the roster.";

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
}
