<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\ProviderStatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function __construct(private readonly ProviderStatisticsService $statistics) {}

    public function index(Request $request): View
    {
        $provider = $request->user();

        // Les statistiques sont un avantage de palier : les ouvrir à tous
        // viderait la grille tarifaire de son sens.
        abort_unless($provider->currentPlan()?->has_stats === true, 403);

        return view('provider.statistics.index', [
            'stats' => $this->statistics->forProvider($provider),
        ]);
    }
}
