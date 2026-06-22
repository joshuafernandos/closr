<?php

namespace App\Data;

readonly class BusinessPermissions
{
    public function __construct(
        public bool $canUpdateBusiness,
        public bool $canDeleteBusiness,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
    ) {
        //
    }
}
