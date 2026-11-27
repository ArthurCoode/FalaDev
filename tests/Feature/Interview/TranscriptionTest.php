<?php

use App\Enums\InterviewCategory;
use App\Models\InterviewSession;
use App\Models\User;
use App\Speech\Contracts\SpeechToText;
use App\Speech\Exceptions\TranscriptionFailed;
use App\Speech\FakeSpeechToText;
use Illuminate\Http\UploadedFile;

function runningInterview(): InterviewSession
{
    $session = InterviewSession::factory()->ready()->create(['category' => InterviewCategory::Behavioral]);
    test()->actingAs($session->user)->post(route('interviews.start', $session));

    return $session->refresh();
}

function recording(string $mime = 'audio/webm', int $kilobytes = 100): UploadedFile
{
    return UploadedFile::fake()->create('answer.webm', $kilobytes, $mime);
}

function transcribeRequest(InterviewSession $session, $turn, UploadedFile $audio, ?User $as = null)
{
    return test()->actingAs($as ?? $session->user)
        ->post(route('interviews.turns.transcribe', [$session, $turn]), ['audio' => $audio], ['Accept' => 'application/json']);
}

test('a recording is transcribed for review without answering the question', function () {
    $session = runningInterview();
    $turn = $session->turns()->first();

    transcribeRequest($session, $turn, recording())
        ->assertOk()
        ->assertJson(['transcript' => FakeSpeechToText::TRANSCRIPT]);

    $turn->refresh();
    expect($turn->isAnswered())->toBeFalse()
        ->and($turn->answer_transcript)->toBeNull()
        ->and($turn->metadata['transcriptions'])->toBe(1);
});

test('the transcript text is not stored in the metadata', function () {
    $session = runningInterview();
    $turn = $session->turns()->first();

    transcribeRequest($session, $turn, recording());

    expect(json_encode($turn->fresh()->metadata))->not->toContain('Simulated');
});

test('Safari recordings (audio/mp4) are accepted', function () {
    $session = runningInterview();

    transcribeRequest($session, $session->turns()->first(), recording('audio/mp4'))->assertOk();
});

test('files that are not audio are rejected', function () {
    $session = runningInterview();

    transcribeRequest($session, $session->turns()->first(), recording('application/pdf'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('audio');
});

test('recordings above the upload limit are rejected', function () {
    config(['faladev.interview.max_answer_upload_kb' => 100]);
    $session = runningInterview();

    transcribeRequest($session, $session->turns()->first(), recording(kilobytes: 200))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('audio');
});

test('an STT failure returns 503 so the browser can keep the recording and retry', function () {
    app()->instance(SpeechToText::class, new class implements SpeechToText
    {
        public function transcribe(string $path, string $mimeType): string
        {
            throw new TranscriptionFailed('provider down');
        }
    });
    $session = runningInterview();

    transcribeRequest($session, $session->turns()->first(), recording())
        ->assertStatus(503)
        ->assertJson(['message' => 'Não foi possível transcrever agora.']);
});

test('an answered question cannot be transcribed again', function () {
    $session = runningInterview();
    $turn = $session->turns()->first();
    $this->actingAs($session->user)->post(route('interviews.turns.answer', [$session, $turn]), ['transcript' => 'Done.']);

    transcribeRequest($session, $turn, recording())->assertStatus(409);
});

test("another user's recording endpoint responds 404", function () {
    $session = runningInterview();

    transcribeRequest($session, $session->turns()->first(), recording(), User::factory()->create())
        ->assertNotFound();
});
