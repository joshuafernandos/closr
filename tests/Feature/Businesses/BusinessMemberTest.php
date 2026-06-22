<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;

test('business member roles can be updated by owners', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->patch(route('businesses.members.update', [$business, $member]), [
            'role' => BusinessRole::Admin->value,
        ]);

    $response->assertRedirect(route('businesses.edit', $business));

    expect($business->members()->where('user_id', $member->id)->first()->pivot->role->value)->toEqual(BusinessRole::Admin->value);
});

test('business member roles cannot be updated by non owners', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($admin, ['role' => BusinessRole::Admin->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('businesses.members.update', [$business, $member]), [
            'role' => BusinessRole::Admin->value,
        ]);

    $response->assertForbidden();
});

test('business members can be removed by owners', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('businesses.members.destroy', [$business, $member]));

    $response->assertRedirect(route('businesses.edit', $business));

    expect($member->fresh()->belongsToBusiness($business))->toBeFalse();
});

test('business members cannot be removed by non owners', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($admin, ['role' => BusinessRole::Admin->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('businesses.members.destroy', [$business, $member]));

    $response->assertForbidden();
});

test('business owner cannot be removed', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('businesses.members.destroy', [$business, $owner]));

    $response->assertForbidden();

    expect($owner->fresh()->belongsToBusiness($business))->toBeTrue();
});

test('business member role cannot be set to owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->patch(route('businesses.members.update', [$business, $member]), [
            'role' => BusinessRole::Owner->value,
        ]);

    $response->assertSessionHasErrors('role');

    expect($business->members()->where('user_id', $member->id)->first()->pivot->role->value)->toEqual(BusinessRole::Member->value);
});

test('removed member current business is set to personal business', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $personalBusiness = $member->personalBusiness();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $member->update(['current_business_id' => $business->id]);

    $this
        ->actingAs($owner)
        ->delete(route('businesses.members.destroy', [$business, $member]));

    expect($member->fresh()->current_business_id)->toEqual($personalBusiness->id);
});