<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\User;
use App\Notifications\Businesses\BusinessInvitation as BusinessInvitationNotification;
use Illuminate\Support\Facades\Notification;

test('business invitations can be created', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($owner)
        ->post(route('businesses.invitations.store', $business), [
            'email' => 'invited@example.com',
            'role' => BusinessRole::Member->value,
        ]);

    $response->assertRedirect(route('businesses.edit', $business));

    $this->assertDatabaseHas('business_invitations', [
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'role' => BusinessRole::Member->value,
    ]);
});

test('invitation email for existing users uses login route', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => $invitedUser->email,
        'invited_by' => $owner->id,
    ]);

    $mail = (new BusinessInvitationNotification($invitation))->toMail($invitedUser);

    expect($mail->actionUrl)->toBe(route('login', ['invitation' => $invitation->code]));
    $this->assertStringContainsString('dashboard', implode(' ', $mail->introLines));
});

test('invitation email for unknown users uses login route', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'unknown@example.com',
        'invited_by' => $owner->id,
    ]);

    $mail = (new BusinessInvitationNotification($invitation))->toMail((object) []);

    expect($mail->actionUrl)->toBe(route('login', ['invitation' => $invitation->code]));
    $this->assertStringContainsString('log in', strtolower(implode(' ', $mail->introLines)));
});

test('business invitations can be created by admins', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($admin, ['role' => BusinessRole::Admin->value]);

    $response = $this
        ->actingAs($admin)
        ->post(route('businesses.invitations.store', $business), [
            'email' => 'invited@example.com',
            'role' => BusinessRole::Member->value,
        ]);

    $response->assertRedirect(route('businesses.edit', $business));
});

test('existing business members cannot be invited', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->post(route('businesses.invitations.store', $business), [
            'email' => 'member@example.com',
            'role' => BusinessRole::Member->value,
        ]);

    $response->assertSessionHasErrors('email');
});

test('duplicate invitations cannot be created', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $business = Business::factory()->create();
    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($owner)
        ->post(route('businesses.invitations.store', $business), [
            'email' => 'invited@example.com',
            'role' => BusinessRole::Member->value,
        ]);

    $response->assertSessionHasErrors('email');
});

test('business invitations cannot be created by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->post(route('businesses.invitations.store', $business), [
            'email' => 'invited@example.com',
            'role' => BusinessRole::Member->value,
        ]);

    $response->assertForbidden();
});

test('business invitations can be cancelled by owners', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('businesses.invitations.destroy', [$business, $invitation]));

    $response->assertRedirect(route('businesses.edit', $business));

    $this->assertDatabaseMissing('business_invitations', [
        'id' => $invitation->id,
    ]);
});

test('business invitations can be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'role' => BusinessRole::Member,
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation));

    $response->assertRedirect(route('dashboard'));
    $response->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invitation accepted.']);

    expect($invitedUser->fresh()->belongsToBusiness($business))->toBeTrue();
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('business invitations can be declined by the invited user', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->delete(route('invitations.decline', $invitation));

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('business_invitations', [
        'id' => $invitation->id,
    ]);
});

test('business invitations cannot be declined by uninvited user', function () {
    $owner = User::factory()->create();
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($uninvitedUser)
        ->delete(route('invitations.decline', $invitation));

    $response->assertSessionHasErrors('invitation');

    $this->assertDatabaseHas('business_invitations', [
        'id' => $invitation->id,
    ]);
});

test('accepted business invitations cannot be declined', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->accepted()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->delete(route('invitations.decline', $invitation));

    $response->assertSessionHasErrors('invitation');

    $this->assertDatabaseHas('business_invitations', [
        'id' => $invitation->id,
    ]);
});

test('business invitations cannot be accepted by uninvited user', function () {
    $owner = User::factory()->create();
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($uninvitedUser)
        ->get(route('invitations.accept', $invitation));

    $response->assertSessionHasErrors('invitation');

    expect($uninvitedUser->fresh()->belongsToBusiness($business))->toBeFalse();
});

test('expired invitations cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->expired()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation));

    $response->assertSessionHasErrors('invitation');

    expect($invitedUser->fresh()->belongsToBusiness($business))->toBeFalse();
});