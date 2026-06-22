<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration screen includes business invitation context', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create(['name' => 'Laravel Business']);
    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('businessInvitation.code', $invitation->code)
        ->where('businessInvitation.businessName', 'Laravel Business'),
    );
});

test('new users can register with a business name', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'business_name' => 'Acme Store',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    $response->assertRedirect(route('dashboard'));

    $business = $user->currentBusiness;

    expect($business)->not->toBeNull()
        ->and($business->name)->toBe('Acme Store')
        ->and($user->ownsBusiness($business))->toBeTrue();
});

test('registration requires a business name', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('business_name');
    $this->assertGuest();
});
