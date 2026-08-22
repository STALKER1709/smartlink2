<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Services\Ai\ServiceDraftWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceDraftController extends Controller
{
    public function __construct(private readonly ServiceDraftWriter $writer) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:600'],
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $draft = $this->writer->draft(
            provider: $request->user(),
            notes: $validated['notes'],
            category: isset($validated['category_id'])
                ? ServiceCategory::find($validated['category_id'])
                : null,
            city: $validated['city'] ?? null,
        );

        if ($draft === null) {
            return response()->json([
                'message' => $this->writer->isOpenToPlan($request->user())
                    ? __('ui.draft.failed')
                    : __('ui.draft.plan_required'),
            ], 422);
        }

        return response()->json($draft);
    }
}
