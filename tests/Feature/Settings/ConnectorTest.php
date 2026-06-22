<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\CatalogueOrigin;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function ownerOfBusiness(): User
{
    $user = User::factory()->create();
    $business = Business::factory()->create(['is_personal' => true]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);
    $user->update(['current_business_id' => $business->id]);

    return $user->refresh();
}

test('the connector page can be rendered', function () {
    $user = ownerOfBusiness();

    $this->actingAs($user)
        ->get(route('connector.edit'))
        ->assertOk();
});

test('guests cannot access the connector', function () {
    $this->get(route('connector.edit'))->assertRedirect(route('login'));
});

test('a business can connect a WooCommerce store with valid credentials', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([]),
    ]);

    $user = ownerOfBusiness();

    $response = $this->actingAs($user)->post(route('connector.store'), [
        'url' => 'https://store.test',
        'key' => 'ck_valid',
        'secret' => 'cs_valid',
    ]);

    $response->assertRedirect(route('connector.edit'));

    $origin = $user->currentBusiness->catalogueOrigin;

    expect($origin)->not->toBeNull()
        ->and($origin->driver)->toBe('woocommerce')
        ->and($origin->config['url'])->toBe('https://store.test')
        ->and($origin->config['key'])->toBe('ck_valid');
});

test('invalid credentials are rejected and nothing is stored', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response(['message' => 'unauthorized'], 401),
    ]);

    $user = ownerOfBusiness();

    $response = $this->from(route('connector.edit'))->actingAs($user)->post(route('connector.store'), [
        'url' => 'https://store.test',
        'key' => 'ck_bad',
        'secret' => 'cs_bad',
    ]);

    $response->assertSessionHasErrors('url');

    expect($user->currentBusiness->catalogueOrigin)->toBeNull();
});

test('a non-https store url is rejected', function () {
    Http::fake();

    $user = ownerOfBusiness();

    $response = $this->from(route('connector.edit'))->actingAs($user)->post(route('connector.store'), [
        'url' => 'http://store.test',
        'key' => 'ck_valid',
        'secret' => 'cs_valid',
    ]);

    $response->assertSessionHasErrors('url');
    Http::assertNothingSent();
});

test('a business can disconnect its store', function () {
    $user = ownerOfBusiness();

    CatalogueOrigin::factory()->create(['business_id' => $user->current_business_id]);

    $this->actingAs($user)
        ->delete(route('connector.destroy'))
        ->assertRedirect(route('connector.edit'));

    expect($user->currentBusiness->fresh()->catalogueOrigin)->toBeNull();
});
