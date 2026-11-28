<?php

use App\Challenges\ChallengeCatalog;
use App\Enums\ChallengeLanguage;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function challengeData(array $overrides = []): array
{
    return [
        'slug' => 'sample',
        'title' => 'Sample',
        'difficulty' => 'easy',
        'summary' => 'Um exemplo.',
        'signature' => ['name' => 'addAll', 'params' => ['nums' => 'int[]', 'extra' => 'int'], 'returns' => 'int'],
        'statement' => ['Given `nums`, {{return}} the sum.'],
        'examples' => [['input' => 'nums = [1]', 'output' => '1']],
        'constraints' => [],
        'glossary' => ['return' => 'devolver'],
        'hints' => [],
        'tests' => [
            ['args' => [[1], 0], 'expected' => 1],
            ['args' => [[1, 2], 0], 'expected' => 3],
            ['args' => [[], 5], 'expected' => 5],
        ],
        'explanation' => ['en' => 'I add them.', 'pt' => 'Eu somo.'],
        'solution' => 'function addAll(nums, extra) {}',
        ...$overrides,
    ];
}

/**
 * @param  list<array<string, mixed>>  $entries
 */
function catalogOf(array $entries, string $language = 'javascript'): ChallengeCatalog
{
    return ChallengeCatalog::fromArray([$language => $entries], perLanguage: null);
}

test('the real catalog has six complete challenges in every language', function () {
    $catalog = ChallengeCatalog::load(__DIR__.'/../../../resources/challenges');

    foreach (ChallengeLanguage::cases() as $language) {
        expect($catalog->forLanguage($language))->toHaveCount(ChallengeCatalog::PER_LANGUAGE);
    }

    foreach ($catalog->all() as $challenge) {
        expect($challenge->hints)->not->toBeEmpty()
            ->and($challenge->examples)->not->toBeEmpty()
            ->and(count($challenge->tests))->toBeGreaterThanOrEqual(4);
    }
});

test('a language with a different number of challenges is refused', function () {
    expect(fn () => ChallengeCatalog::fromArray(['javascript' => [challengeData()]]))
        ->toThrow(InvalidArgumentException::class, 'exactly 6 challenges');
});

test('challenges keep their order and know the next one in the same language', function () {
    $catalog = ChallengeCatalog::fromArray([
        'javascript' => [challengeData(['slug' => 'first']), challengeData(['slug' => 'second'])],
        'python' => [challengeData(['slug' => 'third'])],
    ], perLanguage: null);

    expect($catalog->after($catalog->find('first'))?->slug)->toBe('second')
        ->and($catalog->after($catalog->find('second')))->toBeNull()
        ->and($catalog->after($catalog->find('third')))->toBeNull()
        ->and($catalog->find('third')?->language)->toBe(ChallengeLanguage::Python)
        ->and($catalog->find('missing'))->toBeNull();
});

test('the page gets the glossary, the tests as calls and no reference solution', function () {
    $page = catalogOf([challengeData()], 'python')->find('sample')->toPage();

    expect($page['glossary'])->toBe([['term' => 'return', 'meaning' => 'devolver']])
        ->and($page['language'])->toBe('python')
        ->and($page['signature'])->toBe('def add_all(nums: list[int], extra: int) -> int')
        ->and($page['tests'][1])->toBe(['call' => 'add_all([1, 2], 0)', 'expected' => '3'])
        ->and($page)->not->toHaveKey('solution');
});

test('every language writes its own starter code from the signature', function (string $language, string $expected) {
    $challenge = catalogOf([challengeData()], $language)->find('sample');

    expect($challenge->starter())->toContain($expected);
})->with([
    'javascript' => ['javascript', 'function addAll(nums, extra) {'],
    'typescript' => ['typescript', 'function addAll(nums: number[], extra: number): number {'],
    'python' => ['python', 'def add_all(nums: list[int], extra: int) -> int:'],
    'java' => ['java', 'public int addAll(int[] nums, int extra) {'],
    'csharp' => ['csharp', 'public int AddAll(int[] nums, int extra)'],
    'go' => ['go', 'func addAll(nums []int, extra int) int {'],
    'php' => ['php', 'function addAll(array $nums, int $extra): int'],
]);

test('broken challenges fail loudly', function (array $overrides, string $message) {
    expect(fn () => catalogOf([challengeData($overrides)]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'bad slug' => [['slug' => 'Bad Slug'], 'needs a slug'],
    'unknown difficulty' => [['difficulty' => 'extreme'], 'unknown difficulty'],
    'bad function name' => [['signature' => ['name' => 'Not valid', 'params' => ['n' => 'int'], 'returns' => 'int']], 'camelCase function name'],
    'reserved function name' => [['signature' => ['name' => 'main', 'params' => ['n' => 'int'], 'returns' => 'int']], 'camelCase function name'],
    'multi-word parameter' => [['signature' => ['name' => 'solve', 'params' => ['maxValue' => 'int'], 'returns' => 'int']], 'single lowercase words'],
    'unknown type' => [['signature' => ['name' => 'solve', 'params' => ['n' => 'float'], 'returns' => 'int']], 'unknown type'],
    'no reference solution' => [['solution' => ''], 'needs a solution'],
    'term missing from the glossary' => [['statement' => ['Find the {{median}}.']], 'missing from the glossary'],
    'term outside the statement' => [['hints' => ['Use a {{return}}.']], 'only work in the statement'],
    'too few tests' => [['tests' => [['args' => [[1], 0], 'expected' => 1]]], 'at least three tests'],
    'wrong number of args' => [['tests' => array_fill(0, 3, ['args' => [[1]], 'expected' => 1])], 'different number of args'],
    'argument of the wrong type' => [['tests' => array_fill(0, 3, ['args' => ['1', 0], 'expected' => 1])], 'is not a int[]'],
    'expected of the wrong type' => [['tests' => array_fill(0, 3, ['args' => [[1], 0], 'expected' => true])], 'expected value is not a int'],
    'int out of 32-bit range' => [['tests' => array_fill(0, 3, ['args' => [[1], 3_000_000_000], 'expected' => 1])], 'is not a int'],
    'no translation' => [['explanation' => ['en' => 'Done.', 'pt' => '']], 'English and in Portuguese'],
]);

test('slugs are unique across languages', function () {
    expect(fn () => ChallengeCatalog::fromArray([
        'javascript' => [challengeData()],
        'go' => [challengeData()],
    ], perLanguage: null))->toThrow(InvalidArgumentException::class, 'used twice');
});
