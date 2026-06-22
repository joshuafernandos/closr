<?php

namespace App\Enums;

enum BusinessPermission: string
{
    case UpdateBusiness = 'business:update';
    case DeleteBusiness = 'business:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';
}
