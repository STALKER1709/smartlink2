<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = ServiceCategory::query()->active()->orderBy('name')->get();

        $recentServices = Service::query()
            ->active()
            ->available()
            ->fromListedProvider()
            ->with(['provider.providerProfile', 'category', 'images'])
            ->latest()
            ->take(8)
            ->get();

        return view('home', [
            'categories' => $categories,
            'recentServices' => $recentServices,
        ]);
    }
}
