export type BusinessRole = 'owner' | 'admin' | 'member';

export type Business = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    role?: BusinessRole;
    roleLabel?: string;
    isCurrent?: boolean;
};

export type BusinessMember = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    role: BusinessRole;
    role_label: string;
};

export type BusinessInvitation = {
    code: string;
    email: string;
    role: BusinessRole;
    role_label: string;
    created_at: string;
};

export type BusinessInvitationContext = {
    code: string;
    businessName: string;
};

export type DashboardInvitation = {
    code: string;
    inviterName: string;
    business: {
        name: string;
        slug: string;
    };
};

export type BusinessPermissions = {
    canUpdateBusiness: boolean;
    canDeleteBusiness: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
};

export type RoleOption = {
    value: BusinessRole;
    label: string;
};
