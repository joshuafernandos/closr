<?php

use App\Models\BusinessInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    BusinessInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired business invitations');
