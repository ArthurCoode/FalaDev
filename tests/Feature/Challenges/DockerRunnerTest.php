<?php

use App\Challenges\ChallengeCatalog;
use App\Challenges\Running\ChallengeJudge;
use App\Enums\ChallengeLanguage;

/*
 * Runs real containers, so it only runs when asked:
 * FALADEV_TEST_DOCKER=1 php artisan test --filter=DockerRunner
 * (build the images first: php artisan challenges:runners --build).
 * "php artisan challenges:verify" checks every challenge the same way.
 */

beforeEach(function () {
    if (! env('FALADEV_TEST_DOCKER')) {
        $this->markTestSkipped('Set FALADEV_TEST_DOCKER=1 to run the real code runners.');
    }

    config(['faladev.challenges.runner' => 'docker']);
});

test('the first reference solution of each language passes in its container', function (ChallengeLanguage $language) {
    $challenge = app(ChallengeCatalog::class)->forLanguage($language)[0];

    $result = app(ChallengeJudge::class)->judge($challenge, $challenge->solution);

    expect($result->allPassed())->toBeTrue(json_encode($result->toArray()));
})->with(ChallengeLanguage::cases());
