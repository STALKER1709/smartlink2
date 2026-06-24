<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function store(StoreReviewRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('create', [Review::class, $serviceRequest]);

        $review = DB::transaction(function () use ($request, $serviceRequest) {
            $review = Review::create([
                'request_id' => $serviceRequest->id,
                'client_id' => $serviceRequest->client_id,
                'provider_id' => $serviceRequest->provider_id,
                'rating' => $request->validated('rating'),
                'comment' => $request->validated('comment'),
            ]);

            $provider = $serviceRequest->provider;
            $stats = $provider->reviewsReceived()->visible();

            $provider->providerProfile?->forceFill([
                'rating_avg' => round($stats->avg('rating') ?? 0, 2),
                'rating_count' => $stats->count(),
            ])->save();

            return $review;
        });

        $this->notifications->notifyNewReview($review);

        return back()->with('status', 'Merci pour votre avis !');
    }
}
