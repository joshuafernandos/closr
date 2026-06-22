<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\User;

test('expired invitations are deleted by the scheduled cleanup', function () {
    $this->travelTo(now()->startOfDay());

    $owner = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $expiredInvitation = BusinessInvitation::factory()->expired()->create([
        'business_id' => $business->id,
        'invited_by' => $owner->id,
    ]);

    $unexpiredInvitation = BusinessInvitation::factory()->expiresIn(1)->create([
        'business_id' => $business->id,
        'invited_by' => $owner->id,
    ]);

    $invitationWithoutExpiration = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'invited_by' => $owner->id,
    ]);

    $this->artisan('schedule:run')->assertSuccessful();

    $this->assertDatabaseMissing('business_invitations', [
        'id' => $expiredInvitation->id,
    ]);

    $this->assertDatabaseHas('business_invitations', [
        'id' => $unexpiredInvitation->id,
    ]);

    $this->assertDatabaseHas('business_invitations', [
        'id' => $invitationWithoutExpiration->id,
    ]);
});