<?php

namespace App\Http\Controllers\Api\Member;

use App\Enums\BandUserRoleEnum;
use App\Http\Controllers\Api\ApiController;
use App\Models\Band;
use App\Models\User;
use App\Services\BandMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The mobile equivalent of the web BandMembers area. Band-scoped rather than
 * session-"active-band" scoped: the band is named in the URL. Listing is open
 * to any member of that band; adding, editing roles, and removing are limited
 * to its owners and admins. Business rules (last-owner guards) and the write
 * operations themselves are delegated to {@see BandMemberService}, exactly as
 * the web controller does.
 */
class BandMemberController extends ApiController
{
    /**
     * The band's roster, plus whether the caller may manage it and the role
     * options for the add / edit forms.
     */
    public function index(Request $request, Band $band): JsonResponse
    {
        $this->assertMember($band, $request->user());

        return response()->json($this->rosterPayload($band, $request->user()));
    }

    /**
     * Add a member to the band by email.
     */
    public function store(Request $request, Band $band, BandMemberService $members): JsonResponse
    {
        $this->assertManager($band, $request->user());

        $data = $this->validateWrite($request, $band);

        $members->addMember($band, $data);

        return response()->json($this->rosterPayload($band->fresh(), $request->user()), 201);
    }

    /**
     * Update a roster member's shared account details and role within the band.
     */
    public function update(Request $request, Band $band, User $user, BandMemberService $members): JsonResponse
    {
        $this->assertManager($band, $request->user());
        $this->assertOnRoster($band, $user);

        $data = $this->validateWrite($request, $band, $user);

        if ($members->wouldDemoteLastOwner($band, $user, BandUserRoleEnum::from($data['role']))) {
            throw ValidationException::withMessages([
                'role' => "{$user->name} is the band's only owner. Make someone else an owner first.",
            ]);
        }

        $members->updateMember($band, $user, $data);

        return response()->json($this->rosterPayload($band->fresh(), $request->user()));
    }

    /**
     * Remove a member from the band's roster. The account is left intact; only
     * their place in this band is removed. Members may remove themselves.
     */
    public function destroy(Request $request, Band $band, User $user, BandMemberService $members): JsonResponse
    {
        $actor = $request->user();

        // Managers can remove anyone; a member may always remove themselves.
        if (! $user->is($actor)) {
            $this->assertManager($band, $actor);
        } else {
            $this->assertMember($band, $actor);
        }

        $this->assertOnRoster($band, $user);

        if ($members->wouldLeaveBandOwnerless($band, $user)) {
            throw ValidationException::withMessages([
                'user' => "{$user->name} is the band's only owner. Make someone else an owner first.",
            ]);
        }

        $members->removeMember($band, $user);

        return response()->json([
            ...$this->rosterPayload($band->fresh(), $actor),
            'message' => $user->is($actor) ? 'You left the band.' : "{$user->name} was removed.",
        ]);
    }

    /**
     * Shared validation for add and edit. On edit, the member's own email is
     * allowed to stay; on add, an email already on this roster is rejected.
     *
     * @return array{name: string, email: string, role: string, critical: bool}
     */
    private function validateWrite(Request $request, Band $band, ?User $editing = null): array
    {
        $request->merge([
            'name' => is_string($request->name) ? trim($request->name) : $request->name,
            'email' => is_string($request->email) ? mb_strtolower(trim($request->email)) : $request->email,
        ]);

        $emailRule = ['required', 'email', 'max:255'];

        if ($editing !== null) {
            // Editing a shared account: keep email unique, but the member's own
            // current address is fine to keep.
            $emailRule[] = Rule::unique('users', 'email')->ignore($editing->getKey());
        } else {
            // Adding: reject anyone already on this band's roster.
            $emailRule[] = function (string $attribute, mixed $value, callable $fail) use ($band): void {
                $existing = User::where('email', $value)->first();

                if ($existing && $band->users()->whereKey($existing->getKey())->exists()) {
                    $fail('That person is already in your band.');
                }
            };
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRule,
            'role' => ['required', Rule::enum(BandUserRoleEnum::class)],
            'critical' => ['boolean'],
        ]);
    }

    /**
     * The roster list, management flag, and role options in one payload.
     */
    private function rosterPayload(Band $band, User $actor): array
    {
        $members = $band->users()
            ->orderBy('name')
            ->get(['users.id', 'name', 'email', 'phone_number'])
            ->map(function (User $user) use ($actor) {
                $role = BandUserRoleEnum::from($user->pivot->role);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $role->value,
                    'role_label' => $role->label(),
                    'critical' => (bool) $user->pivot->critical,
                    'is_you' => $user->id === $actor->id,
                ];
            });

        return [
            'data' => $members,
            'can_manage' => $this->isManager($band, $actor),
            'roles' => array_map(
                static fn (BandUserRoleEnum $role) => ['value' => $role->value, 'label' => $role->label()],
                BandUserRoleEnum::cases(),
            ),
        ];
    }

    private function isManager(Band $band, User $user): bool
    {
        return in_array(
            $band->getUserRole($user),
            [BandUserRoleEnum::Owner, BandUserRoleEnum::Admin],
            true,
        );
    }

    private function assertMember(Band $band, User $user): void
    {
        abort_unless($band->getUserRole($user) !== null, 403);
    }

    private function assertManager(Band $band, User $user): void
    {
        abort_unless($this->isManager($band, $user), 403);
    }

    private function assertOnRoster(Band $band, User $user): void
    {
        abort_unless($band->users()->whereKey($user->getKey())->exists(), 404);
    }
}
