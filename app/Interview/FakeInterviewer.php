<?php

namespace App\Interview;

use App\Enums\InterviewCategory;
use App\Enums\InterviewDuration;
use App\Interview\Contracts\Interviewer;
use App\Models\InterviewSession;

/**
 * Development stand-in until the LLM interviewer exists. It follows a fixed
 * script per category: each main question is followed by one follow-up.
 * It does not read the answers and does not adapt to the level; those are
 * the LLM's job. Every line is fixed, so all of them can be pre-recorded.
 */
class FakeInterviewer implements Interviewer
{
    /** Main questions per session, by duration. */
    private const MAIN_QUESTIONS = [
        InterviewDuration::Short->value => 3,
        InterviewDuration::Long->value => 4,
    ];

    /** @var array<string, array{questions: list<string>, follow_ups: list<string>}> */
    private const SCRIPT = [
        InterviewCategory::Algorithms->value => [
            'questions' => [
                'Given an array of integers and a target number, how would you find two numbers that add up to the target? Please walk me through your approach before thinking about code.',
                'How would you check whether a string of brackets, like parentheses and square brackets, is balanced?',
                'Imagine you receive a large log file and need the ten most frequent error messages. How would you approach that?',
                'How would you detect whether a linked list has a cycle?',
            ],
            'follow_ups' => [
                'What is the time and space complexity of that approach?',
                'Which edge cases would you test, and how does your solution handle them?',
                'Is there a way to make it faster, or to use less memory? What would you trade off?',
            ],
        ],
        InterviewCategory::SystemDesign->value => [
            'questions' => [
                "Let's design a URL shortener, a service that turns long links into short ones. What requirements would you clarify first?",
                'How would you design a notification system that sends emails and push notifications to millions of users?',
                'How would you design the backend of a simple chat application with one-to-one messages?',
                'How would you design a rate limiter for a public API?',
            ],
            'follow_ups' => [
                'Where do you expect the main bottleneck to be, and how would you handle it?',
                'Which database would you choose here, and why?',
                'What happens if one of those components fails?',
            ],
        ],
        InterviewCategory::Behavioral->value => [
            'questions' => [
                'Tell me about a time you disagreed with a teammate about a technical decision. What happened?',
                'Can you describe a project or task that did not go as planned? What did you learn from it?',
                'Tell me about a time you had to learn a new technology quickly to deliver something.',
                'Describe a situation where you helped a teammate or improved the way your team worked.',
            ],
            'follow_ups' => [
                'What was your specific role in that situation?',
                'What was the result, and how did you measure it?',
                'Looking back, what would you do differently?',
            ],
        ],
    ];

    public function nextQuestion(InterviewSession $session): ?NextQuestion
    {
        $script = self::SCRIPT[$session->category->value];
        $turns = $session->turns()->get();
        $mainAsked = $turns->where('is_follow_up', false)->count();
        $last = $turns->last();

        if ($last !== null && ! $last->is_follow_up) {
            $followUps = $script['follow_ups'];

            return new NextQuestion($followUps[($mainAsked - 1) % count($followUps)], isFollowUp: true);
        }

        if ($mainAsked < self::MAIN_QUESTIONS[$session->duration_minutes->value]) {
            return new NextQuestion($script['questions'][$mainAsked]);
        }

        return null;
    }

    public function fixedLines(): array
    {
        $lines = [];

        foreach (self::SCRIPT as $script) {
            array_push($lines, ...$script['questions'], ...$script['follow_ups']);
        }

        return $lines;
    }
}
