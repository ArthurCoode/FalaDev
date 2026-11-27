<?php

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Enums\InterviewLevel;
use App\Enums\InterviewStatus;
use App\Models\InterviewSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function validSetup(array $overrides = []): array
{
    return array_merge([
        'category' => 'system_design',
        'level' => 'mid',
        'duration_minutes' => '30',
        'data_notice_accepted' => 'on',
    ], $overrides);
}

test('guests cannot open the interview setup', function () {
    $this->get(route('interviews.create'))->assertRedirect(route('login'));
});

test('the setup screen offers every category, level and duration', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('interviews.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('interviews/Create')
            ->has('options.categories', 3)
            ->has('options.levels', 3)
            ->has('options.durations', 2)
            ->where('options.categories.0.label', 'Algoritmos')
            ->where('draft', null));
});

test('saving the setup creates a draft session and opens it', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('interviews.store'), validSetup());

    $session = $user->interviewSessions()->sole();
    $response->assertRedirect(route('interviews.show', $session));

    expect($session->status)->toBe(InterviewStatus::Draft)
        ->and($session->category)->toBe(InterviewCategory::SystemDesign)
        ->and($session->level)->toBe(InterviewLevel::Mid)
        ->and($session->duration_minutes)->toBe(InterviewDuration::Long)
        ->and($session->started_at)->toBeNull();
});

test('saving again updates the open draft instead of creating another', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('interviews.store'), validSetup());
    $this->actingAs($user)->post(route('interviews.store'), validSetup(['category' => 'behavioral']));

    expect($user->interviewSessions()->count())->toBe(1)
        ->and($user->interviewSessions()->sole()->category)->toBe(InterviewCategory::Behavioral);
});

test('the setup screen is prefilled with the open draft', function () {
    $user = User::factory()->create();
    InterviewSession::factory()->for($user)->create([
        'category' => InterviewCategory::Algorithms,
        'level' => InterviewLevel::Junior,
        'duration_minutes' => InterviewDuration::Short,
    ]);

    $this->actingAs($user)
        ->get(route('interviews.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('draft.category', 'algorithms')
            ->where('draft.level', 'junior')
            ->where('draft.duration_minutes', 15));
});

test('invalid setups are rejected', function (array $data, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('interviews.store'), validSetup($data))
        ->assertSessionHasErrors($field);

    expect($user->interviewSessions()->count())->toBe(0);
})->with([
    'unknown category' => [['category' => 'frontend'], 'category'],
    'unknown level' => [['level' => 'staff'], 'level'],
    'unsupported duration' => [['duration_minutes' => '45'], 'duration_minutes'],
    'data notice not accepted' => [['data_notice_accepted' => null], 'data_notice_accepted'],
]);

test('the setup is blocked once the monthly limit is reached', function () {
    config(['faladev.usage.monthly_session_limit' => 1]);
    $user = User::factory()->create();
    InterviewSession::factory()->for($user)->completed()->create(['started_at' => now()]);

    $this->actingAs($user)
        ->post(route('interviews.store'), validSetup())
        ->assertSessionHasErrors(['usage' => 'Você atingiu o limite de entrevistas deste mês.']);

    expect($user->interviewSessions()->count())->toBe(1);
});

test('users can open their own session', function () {
    $session = InterviewSession::factory()->create(['category' => InterviewCategory::Behavioral]);

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->component('interviews/Show')
            ->where('session.id', $session->id)
            ->where('session.category', 'Comportamental')
            ->where('session.canEditSetup', true));
});

test("users cannot see another user's session", function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('interviews.show', $session))
        ->assertNotFound();
});
