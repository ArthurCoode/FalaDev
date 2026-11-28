<?php

use App\Enums\ExerciseType;
use App\Study\Curriculum;
use App\Study\Exercise;

function realCurriculum(): Curriculum
{
    return Curriculum::load(__DIR__.'/../../../resources/study/curriculum.php');
}

/**
 * @param  array<string, mixed>  $exercise
 * @return array<int, array<string, mixed>>
 */
function curriculumWith(array $exercise): array
{
    return [[
        'slug' => 'unit', 'title' => 'Unit', 'description' => 'A unit.',
        'lessons' => [[
            'slug' => 'lesson', 'title' => 'Lesson', 'description' => 'A lesson.',
            'exercises' => [$exercise],
        ]],
    ]];
}

test('the real curriculum loads and every lesson mixes kinds of exercise', function () {
    $curriculum = realCurriculum();

    expect($curriculum->units)->not->toBeEmpty();

    foreach ($curriculum->lessons() as $lesson) {
        $types = array_map(fn (Exercise $exercise) => $exercise->type, $lesson->exercises);

        expect(count($lesson->exercises))->toBeGreaterThanOrEqual(5)
            ->and($types)->toContain(ExerciseType::Choice)
            ->and($types)->toContain(ExerciseType::Order);
    }
});

test('every exercise of the real curriculum accepts its own answer and rejects the others', function () {
    foreach (realCurriculum()->lessons() as $lesson) {
        foreach ($lesson->exercises as $exercise) {
            if ($exercise->type === ExerciseType::Order) {
                expect($exercise->isCorrect(Exercise::words((string) $exercise->sentence)))->toBeTrue();

                continue;
            }

            expect($exercise->isCorrect($exercise->options[0]))->toBeTrue();

            foreach (array_slice($exercise->options, 1) as $wrong) {
                expect($exercise->isCorrect($wrong))->toBeFalse();
            }
        }
    }
});

test('the distractors of an order exercise are not words of its sentence', function () {
    foreach (realCurriculum()->lessons() as $lesson) {
        foreach ($lesson->exercises as $exercise) {
            $words = array_map('mb_strtolower', Exercise::words((string) $exercise->sentence));

            foreach ($exercise->distractors as $distractor) {
                expect($words)->not->toContain(mb_strtolower($distractor));
            }
        }
    }
});

test('the lessons of a unit are chained in order', function () {
    $unit = realCurriculum()->units[0];

    expect($unit->lessons[0]->previousSlug)->toBeNull()
        ->and($unit->lessons[1]->previousSlug)->toBe($unit->lessons[0]->slug);
});

test('order answers ignore case, punctuation and curly apostrophes', function () {
    $exercise = Exercise::fromArray('l', [
        'id' => 'x', 'type' => 'order', 'prompt' => 'p', 'explain' => 'e',
        'sentence' => "I'm giving a talk about testing.",
        'accept' => ["About testing, I'm giving a talk."],
    ]);

    expect($exercise->isCorrect(["i\u{2019}m", 'GIVING', 'a', 'talk', 'about', 'testing']))->toBeTrue()
        ->and($exercise->isCorrect(['About', 'testing', "I'm", 'giving', 'a', 'talk']))->toBeTrue()
        ->and($exercise->isCorrect(['giving', "I'm", 'a', 'talk', 'about', 'testing']))->toBeFalse()
        ->and($exercise->isCorrect('I am giving a talk'))->toBeFalse()
        ->and($exercise->isCorrect(['key' => 'not a list']))->toBeFalse();
});

test('choice answers must match the right option exactly', function () {
    $exercise = Exercise::fromArray('l', [
        'id' => 'x', 'type' => 'choice', 'prompt' => 'p', 'explain' => 'e',
        'options' => ['Right', 'Wrong'],
    ]);

    expect($exercise->isCorrect('Right'))->toBeTrue()
        ->and($exercise->isCorrect('right'))->toBeFalse()
        ->and($exercise->isCorrect(0))->toBeFalse()
        ->and($exercise->isCorrect(null))->toBeFalse();
});

test('the page gets shuffled options with the right answer, and words with their solutions', function () {
    $choice = Exercise::fromArray('l', [
        'id' => 'c', 'type' => 'gap', 'prompt' => 'p', 'explain' => 'e',
        'sentence' => 'It ___ on the load.', 'options' => ['depends', 'depend', 'depending'],
    ])->toPage();
    $order = Exercise::fromArray('l', [
        'id' => 'o', 'type' => 'order', 'prompt' => 'p', 'explain' => 'e',
        'sentence' => 'It depends on the load.', 'distractors' => ['of'],
    ])->toPage();

    expect($choice['key'])->toBe('l.c')
        ->and($choice['answer'])->toBe('depends')
        ->and($choice['options'])->toEqualCanonicalizing(['depends', 'depend', 'depending'])
        ->and($order['tokens'])->toEqualCanonicalizing(['It', 'depends', 'on', 'the', 'load', 'of'])
        ->and($order['solutions'])->toBe([['it', 'depends', 'on', 'the', 'load']]);
});

test('broken content fails loudly', function (array $exercise, string $message) {
    expect(fn () => Curriculum::fromArray(curriculumWith($exercise)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'bad id' => [['id' => 'Bad Id', 'type' => 'choice', 'prompt' => 'p', 'explain' => 'e', 'options' => ['a', 'b']], 'needs an id'],
    'unknown type' => [['id' => 'x', 'type' => 'essay', 'prompt' => 'p', 'explain' => 'e'], 'unknown type'],
    'no explanation' => [['id' => 'x', 'type' => 'choice', 'prompt' => 'p', 'explain' => '', 'options' => ['a', 'b']], 'needs a explain'],
    'one option' => [['id' => 'x', 'type' => 'choice', 'prompt' => 'p', 'explain' => 'e', 'options' => ['a']], 'two different options'],
    'repeated option' => [['id' => 'x', 'type' => 'choice', 'prompt' => 'p', 'explain' => 'e', 'options' => ['a', 'a']], 'two different options'],
    'gap without blank' => [['id' => 'x', 'type' => 'gap', 'prompt' => 'p', 'explain' => 'e', 'sentence' => 'No blank.', 'options' => ['a', 'b']], 'one "___"'],
    'short sentence' => [['id' => 'x', 'type' => 'order', 'prompt' => 'p', 'explain' => 'e', 'sentence' => 'Too short'], 'at least three words'],
    'accept with other words' => [['id' => 'x', 'type' => 'order', 'prompt' => 'p', 'explain' => 'e', 'sentence' => 'It depends on it.', 'accept' => ['It relies on it.']], 'same words'],
]);

test('slugs are unique across units and lessons', function () {
    $data = curriculumWith(['id' => 'x', 'type' => 'choice', 'prompt' => 'p', 'explain' => 'e', 'options' => ['a', 'b']]);
    $data[0]['lessons'][0]['slug'] = 'unit';

    expect(fn () => Curriculum::fromArray($data))->toThrow(InvalidArgumentException::class, 'used twice');
});
