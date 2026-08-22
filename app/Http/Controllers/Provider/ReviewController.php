<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $provider = $request->user();

        $reviews = Review::query()
            ->where('provider_id', $provider->id)
            ->visible()
            ->with('client.clientProfile')
            ->latest('created_at')
            ->paginate(10);

        $ratingBreakdown = collect(range(5, 1))->mapWithKeys(fn (int $rating) => [
            $rating => Review::query()->where('provider_id', $provider->id)->visible()->where('rating', $rating)->count(),
        ]);

        return view('provider.reviews.index', [
            'reviews' => $reviews,
            'ratingBreakdown' => $ratingBreakdown,
            'ratingAvg' => $provider->providerProfile?->rating_avg ?? 0,
            'ratingCount' => $provider->providerProfile?->rating_count ?? 0,
        ]);
    }
}
