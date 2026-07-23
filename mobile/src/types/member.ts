export type MemberRole = 'member' | 'admin' | 'owner';

export interface RosterMember {
    id: number;
    name: string;
    email: string;
    phone_number: string | null;
    role: MemberRole;
    role_label: string;
    critical: boolean;
    is_you: boolean;
}

export interface RoleOption {
    value: MemberRole;
    label: string;
}

/** Response shape of GET/POST/PUT/DELETE /bands/{band}/members. */
export interface RosterResponse {
    data: RosterMember[];
    can_manage: boolean;
    roles: RoleOption[];
    message?: string;
}
