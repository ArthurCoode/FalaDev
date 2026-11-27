<?php

use App\Enums\InterviewCategory;
use App\Enums\InterviewStatus;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

test('the session page includes the sample phrase for the audio check', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('samplePhrase', config('faladev.speech.sample_phrase')));
});

test('the page links the pre-recorded sample phrase when it exists', function () {
    $phrases = fakePrerecordedPhrases();
    $phrases->store(config('faladev.speech.sample_phrase'), 'mp3-bytes');
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sampleAudioUrl', $phrases->url(config('faladev.speech.sample_phrase'))));
});

test('without a recording the page gets no audio URL and the text stays on screen', function () {
    fakePrerecordedPhrases();
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->get(route('interviews.show', $session))
        ->assertInertia(fn (Assert $page) => $page->where('sampleAudioUrl', null));
});

test('showing the session page never calls the TTS provider', function () {
    config(['faladev.speech.tts' => 'elevenlabs']);
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)->get(route('interviews.show', $session))->assertOk();

    Http::assertNothingSent();
});

test('finishing the checks marks a draft session as ready', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)
        ->post(route('interviews.ready', $session))
        ->assertRedirect(route('interviews.show', $session));

    expect($session->fresh()->status)->toBe(InterviewStatus::Ready);
});

test('marking ready twice is harmless', function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs($session->user)->post(route('interviews.ready', $session));
    $this->actingAs($session->user)
        ->post(route('interviews.ready', $session))
        ->assertSessionHasNoErrors();

    expect($session->fresh()->status)->toBe(InterviewStatus::Ready);
});

test('a finished session cannot go back to ready', function () {
    $session = InterviewSession::factory()->completed()->create();

    $this->actingAs($session->user)
        ->post(route('interviews.ready', $session))
        ->assertSessionHasErrors('status');

    expect($session->fresh()->status)->toBe(InterviewStatus::ReportReady);
});

test("users cannot mark another user's session as ready", function () {
    $session = InterviewSession::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('interviews.ready', $session))
        ->assertNotFound();

    expect($session->fresh()->status)->toBe(InterviewStatus::Draft);
});

test('changing the setup of a ready session sends it back to the checks', function () {
    $user = User::factory()->create();
    $session = InterviewSession::factory()->for($user)->create();
    $this->actingAs($user)->post(route('interviews.ready', $session));

    $this->actingAs($user)->post(route('interviews.store'), [
        'category' => 'behavioral',
        'level' => 'mid',
        'duration_minutes' => '15',
        'data_notice_accepted' => 'on',
    ])->assertRedirect(route('interviews.show', $session));

    $session->refresh();
    expect($user->interviewSessions()->count())->toBe(1)
        ->and($session->status)->toBe(InterviewStatus::Draft)
        ->and($session->category)->toBe(InterviewCategory::Behavioral);
});
