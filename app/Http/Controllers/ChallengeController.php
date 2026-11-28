<?php

namespace App\Http\Controllers;

use App\Challenges\Challenge;
use App\Challenges\ChallengeCatalog;
use App\Challenges\Running\ChallengeJudge;
use App\Enums\ChallengeLanguage;
use App\Models\ChallengeSolution;
use App\Study\StudyRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Code challenges: interview-style problems in English, six per language.
 * The tests run on the server, in an isolated container per run, and the
 * server grades them: a solve (and its XP) only counts when they pass there.
 */
class ChallengeController extends Controller
{
    /** Longest code accepted, in characters. */
    public const MAX_CODE_LENGTH = 20_000;

    /** The cookie with the last language picked on the list. */
    public const LANGUAGE_COOKIE = 'challenge_language';

    public function __construct(private readonly ChallengeCatalog $catalog) {}

    public function index(Request $request): Response
    {
        $solved = $this->solvedSlugs($request);
        $query = $request->query('language');
        $cookie = $request->cookie(self::LANGUAGE_COOKIE);
        $language = (is_string($query) ? ChallengeLanguage::tryFrom($query) : null)
            ?? (is_string($cookie) ? ChallengeLanguage::tryFrom($cookie) : null)
            ?? ChallengeLanguage::JavaScript;

        return Inertia::render('challenges/Index', [
            'language' => $language->value,
            'languages' => array_map(function (ChallengeLanguage $language) use ($solved) {
                $slugs = array_map(fn (Challenge $challenge) => $challenge->slug, $this->catalog->forLanguage($language));

                return [
                    'value' => $language->value,
                    'label' => $language->label(),
                    'total' => count($slugs),
                    'solved' => count(array_intersect($slugs, $solved)),
                ];
            }, ChallengeLanguage::cases()),
            'challenges' => array_map(fn (Challenge $challenge) => [
                'slug' => $challenge->slug,
                'language' => $challenge->language->value,
                'title' => $challenge->title,
                'difficulty' => $challenge->difficulty,
                'summary' => $challenge->summary,
                'terms' => count($challenge->glossary),
                'solved' => in_array($challenge->slug, $solved, true),
            ], $this->catalog->all()),
        ]);
    }

    public function show(Request $request, string $challenge): Response
    {
        $found = $this->find($challenge);
        $next = $this->catalog->after($found);

        $solution = ChallengeSolution::query()
            ->where('user_id', $request->user()->id)
            ->where('challenge_slug', $found->slug)
            ->first();

        return Inertia::render('challenges/Show', [
            'challenge' => $found->toPage(),
            'savedCode' => $solution?->code,
            'solved' => $solution !== null,
            'next' => $next ? ['slug' => $next->slug, 'title' => $next->title] : null,
            'xpReward' => StudyRecorder::XP_CHALLENGE,
            'timeLimit' => (int) config('faladev.challenges.time_limit'),
        ]);
    }

    /**
     * Runs the tests against the code. When every test passes, the code is
     * kept as the user's solution and the first solve earns XP.
     */
    public function run(Request $request, ChallengeJudge $judge, StudyRecorder $recorder, string $challenge): JsonResponse
    {
        $found = $this->find($challenge);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:'.self::MAX_CODE_LENGTH],
        ]);

        $result = $judge->judge($found, $validated['code']);

        return response()->json([
            ...$result->toArray(),
            'outcome' => $result->allPassed()
                ? $recorder->solveChallenge($request->user(), $found, $validated['code'])
                : null,
        ]);
    }

    private function find(string $slug): Challenge
    {
        $challenge = $this->catalog->find($slug);

        abort_if($challenge === null, 404);

        return $challenge;
    }

    /**
     * @return list<string>
     */
    private function solvedSlugs(Request $request): array
    {
        return array_values(ChallengeSolution::query()
            ->where('user_id', $request->user()->id)
            ->get(['challenge_slug'])
            ->map(fn (ChallengeSolution $solution) => $solution->challenge_slug)
            ->all());
    }
}
