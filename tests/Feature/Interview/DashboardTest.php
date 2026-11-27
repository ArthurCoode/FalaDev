<?php

use App\Enums\InterviewCategory;
use App\Enums\InterviewLevel;
use App\Models\InterviewSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the dashboard lists only the five most recent sessions of the current user', function () {
    $user = User::factory()->create();

    foreach (range(1, 6) as $daysAgo) {
        InterviewSession::factory()->for($user)->create(['created_at' => now()->subDays($daysAgo)]);
    }
    $latest = InterviewSession::factory()->for($user)->create([
        'category' => InterviewCategory::Algorithms,
        'level' => InterviewLevel::Senior,
    ]);
    InterviewSession::factory()->create(); // belongs to someone else

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('recentSessions', 5)
            ->where('recentSessions.0.id', $latest->id)
            ->where('recentSessions.0.category', 'Algoritmos')
            ->where('recentSessions.0.level', 'Sênior')
            ->where('recentSessions.0.statusLabel', 'Em configuração'));
});

test('monthly usage counts only sessions the current user started this month', function () {
    config(['faladev.usage.monthly_session_limit' => 3]);
    $user = User::factory()->create();

    InterviewSession::factory()->for($user)->count(2)->create(['started_at' => now()]);
    InterviewSession::factory()->for($user)->create(); // configured, never started
    InterviewSession::factory()->for($user)->create(['started_at' => now()->subMonthNoOverflow()->startOfMonth()]);
    InterviewSession::factory()->create(['started_at' => now()]); // someone else

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('usage.used', 2)
            ->where('usage.limit', 3)
            ->where('usage.remaining', 1));
});

test('a new user sees an empty history', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentSessions', 0)
            ->where('usage.used', 0));
});

test('server messages are shown in Portuguese', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);

    $this->post(route('register.store'), [])
        ->assertSessionHasErrors(['email' => 'O campo e-mail é obrigatório.']);
});

test('the dashboard offers to continue the running interview', function () {
    $running = InterviewSession::factory()->inProgress()->create();

    $this->actingAs($running->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('inProgress.id', $running->id)
            ->where('inProgress.category', $running->category->label()));
});

test('without a running interview the dashboard offers a new one', function () {
    $user = User::factory()->create();
    InterviewSession::factory()->for($user)->completed()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('inProgress', null));
});
