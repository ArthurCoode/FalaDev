<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the tutorial page renders', function () {
    $this->actingAs(User::factory()->create()->fresh())
        ->get(route('tutorial'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tutorial')
            ->where('auth.user.tutorial_dismissed_at', null));
});

test('"não exibir novamente" hides the tutorial and goes to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tutorial.dismiss'))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->tutorial_dismissed_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->whereNot('auth.user.tutorial_dismissed_at', null));
});

test('the tutorial can come back from the settings', function () {
    $user = User::factory()->create(['tutorial_dismissed_at' => now()]);

    $this->actingAs($user)
        ->from(route('appearance.edit'))
        ->post(route('tutorial.restore'))
        ->assertRedirect(route('appearance.edit'));

    expect($user->fresh()->tutorial_dismissed_at)->toBeNull();
});
