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

test('monthly usage counts only the current user sessions created this month', function () {
    config(['faladev.usage.monthly_session_limit' => 3]);
    $user = User::factory()->create();

    InterviewSession::factory()->for($user)->count(2)->create();
    InterviewSession::factory()->for($user)->create(['created_at' => now()->subMonthNoOverflow()->startOfMonth()]);
    InterviewSession::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('usage.used', 2)
            ->where('usage.limit', 3));
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
