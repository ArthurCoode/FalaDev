<?php

namespace App\Http\Controllers;

use App\Services\PilotMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pilot metrics page, only for the accounts in FALADEV_PILOT_ADMINS.
 * Everyone else gets 404, so the page's existence is not revealed.
 */
class PilotMetricsController extends Controller
{
    /** Period options in days; 0 means "all time". */
    private const PERIODS = [7, 30, 90, 0];

    public function __invoke(Request $request, PilotMetrics $metrics): Response
    {
        abort_unless(Gate::allows('view-pilot-metrics'), 404);

        $days = (int) $request->query('days', '30');
        $days = in_array($days, self::PERIODS, true) ? $days : 30;

        return Inertia::render('pilot/Metrics', [
            'days' => $days,
            'periods' => self::PERIODS,
            'metrics' => $metrics->summary($days > 0 ? now()->subDays($days) : null),
        ]);
    }
}
