<?php

namespace App\Http\Controllers\Web;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Media;
use App\Models\Submission;
use App\Notifications\NewSubmissionNotification;
use App\Services\AdminService;
use App\Support\SubmissionContent;
use App\Support\TnfImageUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function store(StoreSubmissionRequest $request): RedirectResponse
    {
        $media = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('submissions', 'public');

            $media = Media::query()->create([
                'disk' => 'public',
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'alt' => $request->string('title')->toString(),
            ]);
        }

        $submission = Submission::query()->create([
            'user_id' => $request->user()->id,
            'title' => $request->string('title')->toString(),
            'content' => SubmissionContent::sanitize($request->string('content')->toString()),
            'embed_url' => $request->input('embed_url'),
            'featured_media_id' => $media?->id,
            'category_id' => $request->integer('category_id'),
            'status' => SubmissionStatus::Pending,
        ]);

        $submission->load('user');

        $admin = AdminService::currentAdmin();
        if ($admin) {
            $admin->notify(new NewSubmissionNotification($submission));
        }

        return redirect()
            ->route('account')
            ->with('success', 'Your story was submitted and is pending review.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('create', Submission::class), 403);

        $validated = $request->validate([
            'image' => TnfImageUpload::validationRules(required: true),
        ]);

        $file = $validated['image'];
        $path = $file->store('submissions/inline', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function destroy(Submission $submission): RedirectResponse
    {
        $this->authorize('delete', $submission);

        abort_unless($submission->canWithdraw(), 403, 'This submission cannot be withdrawn.');

        if ($submission->featured_media_id) {
            $media = $submission->featuredMedia;
            if ($media && Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
            $media?->delete();
        }

        $submission->delete();

        return redirect()
            ->route('account')
            ->with('success', 'Submission withdrawn.');
    }

}
