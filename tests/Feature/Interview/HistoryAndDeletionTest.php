<?php

use App\Enums\UsageReason;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the history lists every session of the user, newest first, paginated', function () {
    $user = User::factory()->create();
    foreach (range(1, 16) as $daysAgo) {
        InterviewSession::factory()->for($user)->create(['created_at' => now()->subDays($daysAgo)]);
    }
    $newest = InterviewSession::factory()->for($user)->create();
    InterviewSession::factory()->create(); // someone else's

    $this->actingAs($user)
        ->get(route('interviews.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('interviews/Index')
            ->where('sessions.total', 17)
            ->where('sessions.last_page', 2)
            ->has('sessions.data', 15)
            ->where('sessions.data.0.id', $newest->id));
});

test('guests cannot see the history', function () {
    $this->get(route('interviews.index'))->assertRedirect(route('login'));
});

test('deleting a session removes its questions and answers', function () {
    $session = InterviewSession::factory()->completed()->create();
    InterviewTurn::factory()->answered()->for($session, 'session')->create();

    $this->actingAs($session->user)
        ->delete(route('interviews.destroy', $session))
        ->assertRedirect(route('interviews.index'));

    expect(InterviewSession::find($session->id))->toBeNull()
        ->and(InterviewTurn::where('interview_session_id', $session->id)->count())->toBe(0);
});

test('deleting a session does not give the monthly allowance back', function () {
    $user = User::factory()->create();
    $session = InterviewSession::factory()->for($user)->completed()->create(['started_at' => now()]);

    $this->actingAs($user)->delete(route('interviews.destroy', $session));

    expect($user->usageRecords()->where('reason', UsageReason::InterviewStarted)->count())->toBe(1);
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('usage.used', 1));
});

test("users cannot delete another user's session", function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('interviews.destroy', $session))
        ->assertNotFound();

    expect(InterviewSession::find($session->id))->not->toBeNull();
});

test('starting an interview records one unit of usage', function () {
    $session = InterviewSession::factory()->ready()->create();

    $this->actingAs($session->user)->post(route('interviews.start', $session));
    $this->actingAs($session->user)->post(route('interviews.start', $session)); // double click

    expect($session->user->usageRecords()->count())->toBe(1)
        ->and($session->user->usageRecords()->first()->interview_session_id)->toBe($session->id);
});
