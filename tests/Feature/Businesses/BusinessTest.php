<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the businesses index page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('businesses.index'));

    $response->assertOk();
});

test('businesses can be created', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Test Business',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('businesses', [
        'name' => 'Test Business',
        'is_personal' => false,
    ]);
});

test('business slug uses next available suffix', function () {
    $user = User::factory()->create();

    Business::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Business::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Business::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Acme',
        ]);

    $this->assertDatabaseHas('businesses', [
        'name' => 'Acme',
        'slug' => 'acme-11',
    ]);
});

test('the business edit page can be rendered', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->get(route('businesses.edit', $business));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('businesses/edit')
            ->where('members.0.role', BusinessRole::Owner->value)
            ->where('members.0.role_label', BusinessRole::Owner->label()),
        );
});

test('businesses can be updated by owners', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['name' => 'Original Name']);

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->patch(route('businesses.update', $business), [
            'name' => 'Updated Name',
        ]);

    $response->assertRedirect(route('businesses.edit', $business->fresh()));

    $this->assertDatabaseHas('businesses', [
        'id' => $business->id,
        'name' => 'Updated Name',
    ]);
});

test('businesses cannot be updated by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->patch(route('businesses.update', $business), [
            'name' => 'Updated Name',
        ]);

    $response->assertForbidden();
});

test('businesses can be deleted by owners', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $business->id,
    ]);
});

test('business deletion requires name confirmation', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => 'Wrong Name',
        ]);

    $response->assertSessionHasErrors('name');

    $this->assertDatabaseHas('businesses', [
        'id' => $business->id,
        'deleted_at' => null,
    ]);
});

test('deleting current business switches to alphabetically first remaining business', function () {
    $user = User::factory()->create(['name' => 'Mike']);

    $zuluBusiness = Business::factory()->create(['name' => 'Zulu Business']);
    $zuluBusiness->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $alphaBusiness = Business::factory()->create(['name' => 'Alpha Business']);
    $alphaBusiness->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $betaBusiness = Business::factory()->create(['name' => 'Beta Business']);
    $betaBusiness->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $user->update(['current_business_id' => $zuluBusiness->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $zuluBusiness), [
            'name' => $zuluBusiness->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $zuluBusiness->id,
    ]);

    expect($user->fresh()->current_business_id)->toEqual($alphaBusiness->id);
});

test('deleting current business falls back to personal business when alphabetically first', function () {
    $user = User::factory()->create();
    $personalBusiness = $user->personalBusiness();
    $business = Business::factory()->create(['name' => 'Zulu Business']);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $user->update(['current_business_id' => $business->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $business->id,
    ]);

    expect($user->fresh()->current_business_id)->toEqual($personalBusiness->id);
});

test('deleting non current business leaves current business unchanged', function () {
    $user = User::factory()->create();
    $personalBusiness = $user->personalBusiness();
    $business = Business::factory()->create();
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $user->update(['current_business_id' => $personalBusiness->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $business->id,
    ]);

    expect($user->fresh()->current_business_id)->toEqual($personalBusiness->id);
});

test('members can leave non personal businesses', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->delete(route('businesses.leave', $business));

    $response->assertRedirect(route('businesses.index'));
    $response->assertInertiaFlash('toast', ['type' => 'success', 'message' => "You left the business \"{$business->name}\""]);

    expect($member->fresh()->belongsToBusiness($business))->toBeFalse();
});

test('leaving current business switches to alphabetically first remaining business', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create(['name' => 'Mike']);

    $zuluBusiness = Business::factory()->create(['name' => 'Zulu Business']);
    $zuluBusiness->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $zuluBusiness->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $alphaBusiness = Business::factory()->create(['name' => 'Alpha Business']);
    $alphaBusiness->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $betaBusiness = Business::factory()->create(['name' => 'Beta Business']);
    $betaBusiness->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $member->update(['current_business_id' => $zuluBusiness->id]);

    $response = $this
        ->actingAs($member)
        ->delete(route('businesses.leave', $zuluBusiness));

    $response->assertRedirect(route('businesses.index'));

    expect($member->fresh()->belongsToBusiness($zuluBusiness))->toBeFalse();
    expect($member->fresh()->current_business_id)->toEqual($alphaBusiness->id);
});

test('personal businesses cannot be left', function () {
    $user = User::factory()->create();
    $personalBusiness = $user->personalBusiness();

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.leave', $personalBusiness));

    $response->assertForbidden();

    expect($user->fresh()->belongsToBusiness($personalBusiness))->toBeTrue();
});

test('business owners cannot leave their business', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('businesses.leave', $business));

    $response->assertForbidden();

    expect($owner->fresh()->belongsToBusiness($business))->toBeTrue();
});

test('users cannot leave businesses they dont belong to', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.leave', $business));

    $response->assertForbidden();
});

test('deleting business switches other affected users to their personal business', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $business = Business::factory()->create();
    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $owner->update(['current_business_id' => $business->id]);
    $member->update(['current_business_id' => $business->id]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    expect($member->fresh()->current_business_id)->toEqual($member->personalBusiness()->id);
});

test('personal businesses cannot be deleted', function () {
    $user = User::factory()->create();

    $personalBusiness = $user->personalBusiness();

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $personalBusiness), [
            'name' => $personalBusiness->name,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('businesses', [
        'id' => $personalBusiness->id,
        'deleted_at' => null,
    ]);
});

test('businesses cannot be deleted by non owners', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertForbidden();
});

test('users can switch businesses', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.switch', $business));

    $response->assertRedirect();

    expect($user->fresh()->current_business_id)->toEqual($business->id);
});

test('users cannot switch to business they dont belong to', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.switch', $business));

    $response->assertForbidden();
});

test('guests cannot access businesses', function () {
    $response = $this->get(route('businesses.index'));

    $response->assertRedirect(route('login'));
});