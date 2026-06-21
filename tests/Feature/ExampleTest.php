<?php

use App\Models\User;

test('guests are redirected to login from the home route', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));
});

test('authenticated users are redirected to their team dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect("/{$user->currentTeam->slug}/dashboard");
});
