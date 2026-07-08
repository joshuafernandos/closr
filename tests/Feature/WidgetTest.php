<?php

use App\Enums\WidgetTemplate;
use App\Models\CatalogueOrigin;
use App\Models\User;
use App\Models\Widget;

test('guests cannot access widgets', function () {
    $this->get(route('widgets.index'))->assertRedirect(route('login'));
});

test('the widgets page lists the business widgets and its sources', function () {
    $user = User::factory()->create();
    $origin = CatalogueOrigin::factory()->for($user->currentBusiness)->create();
    Widget::factory()->for($user->currentBusiness)->create([
        'catalogue_origin_id' => $origin->id,
    ]);

    $this->actingAs($user)
        ->get(route('widgets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('widgets')
            ->has('widgets', 1)
            ->has('sources', 1)
        );
});

test('a business can create a widget', function () {
    $user = User::factory()->create();
    $origin = CatalogueOrigin::factory()->for($user->currentBusiness)->create();

    $response = $this->actingAs($user)->post(route('widgets.store'), [
        'name' => 'Storefront assistant',
        'template' => 'component',
        'accent_color' => '#ff8800',
        'catalogue_origin_id' => $origin->id,
    ]);

    $widget = $user->currentBusiness->widgets()->first();

    expect($widget)->not->toBeNull()
        ->and($widget->name)->toBe('Storefront assistant')
        ->and($widget->template)->toBe(WidgetTemplate::Component)
        ->and($widget->accent_color)->toBe('#ff8800')
        ->and($widget->catalogue_origin_id)->toBe($origin->id)
        ->and($widget->widget_key)->toStartWith('clsr_pub_');

    $response->assertRedirect(route('widgets.edit', $widget));
});

test('a widget can be created without a source', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('widgets.store'), [
        'name' => 'No source yet',
        'template' => 'chat',
        'accent_color' => '#123456',
    ])->assertRedirect();

    expect($user->currentBusiness->widgets()->first()->catalogue_origin_id)->toBeNull();
});

test('a widget cannot use a source from another business', function () {
    $user = User::factory()->create();
    $otherOrigin = CatalogueOrigin::factory()->create();

    $this->actingAs($user)
        ->from(route('widgets.index'))
        ->post(route('widgets.store'), [
            'name' => 'Sneaky',
            'template' => 'chat',
            'accent_color' => '#123456',
            'catalogue_origin_id' => $otherOrigin->id,
        ])
        ->assertSessionHasErrors('catalogue_origin_id');

    expect($user->currentBusiness->widgets()->count())->toBe(0);
});

test('an invalid accent colour is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('widgets.index'))
        ->post(route('widgets.store'), [
            'name' => 'Bad colour',
            'template' => 'chat',
            'accent_color' => 'red',
        ])
        ->assertSessionHasErrors('accent_color');
});

test('a business can update its widget', function () {
    $user = User::factory()->create();
    $widget = Widget::factory()->for($user->currentBusiness)->create([
        'name' => 'Old',
        'template' => WidgetTemplate::Chat,
    ]);

    $this->actingAs($user)->put(route('widgets.update', $widget), [
        'name' => 'New name',
        'template' => 'component',
        'accent_color' => '#0a0a0a',
        'catalogue_origin_id' => null,
    ])->assertRedirect(route('widgets.edit', $widget));

    $widget->refresh();

    expect($widget->name)->toBe('New name')
        ->and($widget->template)->toBe(WidgetTemplate::Component)
        ->and($widget->accent_color)->toBe('#0a0a0a');
});

test('a business can delete its widget', function () {
    $user = User::factory()->create();
    $widget = Widget::factory()->for($user->currentBusiness)->create();

    $this->actingAs($user)
        ->delete(route('widgets.destroy', $widget))
        ->assertRedirect(route('widgets.index'));

    expect(Widget::find($widget->id))->toBeNull();
});

test('a user cannot edit another business widget', function () {
    $user = User::factory()->create();
    $widget = Widget::factory()->create();

    $this->actingAs($user)
        ->get(route('widgets.edit', $widget))
        ->assertNotFound();
});

test('a user cannot update another business widget', function () {
    $user = User::factory()->create();
    $widget = Widget::factory()->create(['name' => 'Theirs']);

    $this->actingAs($user)->put(route('widgets.update', $widget), [
        'name' => 'Hijacked',
        'template' => 'chat',
        'accent_color' => '#000000',
    ])->assertNotFound();

    expect($widget->fresh()->name)->toBe('Theirs');
});
