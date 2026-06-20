<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubmissionRequest;
use App\Models\Submission;
use App\Notifications\NewSubmissionNotification;
use App\Services\AdminService;
use App\Services\SubmissionWorkflowService;
use App\Support\Api\WpContentSerializer;
use App\Support\SubmissionContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        $submission = Submission::query()->create([
            'user_id' => $request->user()->id,
            'title' => $request->string('title')->toString(),
            'content' => SubmissionContent::sanitize($request->string('content')->toString()),
            'embed_url' => $request->input('embed_url'),
            'category_id' => $request->integer('category_id'),
            'status' => SubmissionStatus::Pending,
        ]);

        $submission->load(['user', 'category']);
        AdminService::currentAdmin()?->notify(new NewSubmissionNotification($submission));

        return response()->json([
            'data' => WpContentSerializer::submission($submission),
        ], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $submissions = $request->user()
            ->submissions()
            ->latest()
            ->get()
            ->map(fn (Submission $submission) => WpContentSerializer::submission($submission));

        return response()->json(['data' => $submissions]);
    }

    public function approve(Request $request, Submission $submission): JsonResponse
    {
        $this->authorize('approve', $submission);

        abort_unless($submission->status === SubmissionStatus::Pending, 422, 'Submission is not pending.');

        $validated = $request->validate([
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $article = SubmissionWorkflowService::approve(
            $submission,
            $request->user(),
            $validated['category_ids'],
        );

        return response()->json([
            'data' => WpContentSerializer::submission($submission->fresh()),
            'article_id' => $article->id,
            'article_slug' => $article->slug,
        ]);
    }

    public function reject(Request $request, Submission $submission): JsonResponse
    {
        $this->authorize('reject', $submission);

        abort_unless($submission->status === SubmissionStatus::Pending, 422, 'Submission is not pending.');

        SubmissionWorkflowService::reject($submission, $request->input('rejection_reason'));

        return response()->json([
            'data' => WpContentSerializer::submission($submission->fresh()),
        ]);
    }

}
