<x-site.layout :auth-lite="true" title="My Account — TNF Today">
    <div class="tnf-page-content mx-auto max-w-3xl space-y-6">
        @if(session('success'))
            <div class="rounded-tnf-lg border border-green-200 bg-green-50 px-4 py-3 text-tnf-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Profile --}}
        <div class="rounded-tnf-lg bg-white p-6 shadow-card">
            <h1 class="tnf-section-title mb-4">My Account</h1>
            <h2 class="text-tnf-lg font-semibold text-tnf-navy">{{ $user->name }}</h2>
            <p class="text-tnf-sm text-tnf-muted">{{ $user->email }}</p>
            <div class="mt-3 flex flex-wrap gap-3 text-tnf-sm">
                <span class="rounded-full bg-tnf-gray px-3 py-1 font-medium text-tnf-navy">
                    {{ $roleLabel }}
                </span>
                <span class="rounded-full px-3 py-1 font-medium {{ $hasPremium ? 'bg-green-100 text-green-800' : 'bg-tnf-gray-dark text-tnf-muted' }}">
                    Subscription: {{ $hasPremium ? 'Active' : 'Inactive' }}
                </span>
            </div>

            @unless($hasPremium)
                <p class="mt-4 rounded-tnf bg-tnf-gray p-3 text-tnf-sm text-tnf-muted">
                    Premium ePaper editions marked as subscriber-only require an active subscription.
                    Contact TNF Today to activate your membership.
                </p>
            @endunless

            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('profile.edit') }}" class="tnf-btn-outline text-tnf-sm">
                    Profile &amp; delete account
                </a>
                <a href="{{ route('page.privacy') }}" class="tnf-btn-outline text-tnf-sm">
                    Privacy Policy
                </a>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="tnf-btn-outline text-tnf-sm">Log out</button>
            </form>
        </div>

        @if($isMember)
            {{-- KPI cards --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach([
                    ['label' => 'Total', 'value' => $kpis['total']],
                    ['label' => 'Live on site', 'value' => $kpis['live']],
                    ['label' => 'Article removed', 'value' => $kpis['removed']],
                    ['label' => 'Pending', 'value' => $kpis['pending']],
                    ['label' => 'Rejected', 'value' => $kpis['rejected']],
                ] as $kpi)
                    <div class="rounded-tnf-lg bg-white p-4 text-center shadow-card">
                        <p class="text-tnf-2xl font-bold text-tnf-red">{{ $kpi['value'] }}</p>
                        <p class="mt-1 text-tnf-xs font-medium uppercase tracking-wide text-tnf-muted">{{ $kpi['label'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Submit form --}}
            <div class="rounded-tnf-lg bg-white p-6 shadow-card">
                <h2 class="mb-4 text-tnf-lg font-bold text-tnf-navy">Submit News</h2>
                <form action="{{ route('account.submissions.store') }}" method="POST" enctype="multipart/form-data" class="tnf-account-form space-y-5">
                    @csrf

                    <div class="tnf-field">
                        <label for="title" class="tnf-label">Title</label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required
                            class="tnf-input" placeholder="News headline in Hindi">
                        @error('title')<p class="text-tnf-sm text-tnf-red">{{ $message }}</p>@enderror
                    </div>

                    <div class="tnf-field">
                        <label for="category_id" class="tnf-label">Category</label>
                        <select name="category_id" id="category_id" required class="tnf-select">
                            <option value="">Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="text-tnf-sm text-tnf-red">{{ $message }}</p>@enderror
                    </div>

                    <div id="submission-editor-root" class="tnf-field" data-upload-url="{{ route('account.submissions.upload-image') }}">
                        <label class="tnf-label">Story</label>
                        <div class="tnf-submission-editor">
                            <div id="submission-editor-toolbar" class="tnf-submission-editor__toolbar" aria-label="Formatting toolbar"></div>
                            <div id="submission-editor-body" class="tnf-submission-editor__body"></div>
                        </div>
                        <input type="hidden" name="content" id="content" value="{{ old('content') }}" required>
                        @error('content')<p class="text-tnf-sm text-tnf-red">{{ $message }}</p>@enderror
                    </div>

                    <div class="tnf-field">
                        <span class="tnf-label">Featured image <span class="font-normal text-tnf-muted">(optional)</span></span>
                        <label for="image" class="tnf-file-picker">
                            <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
                            <span class="tnf-file-picker__box">
                                <span class="tnf-file-picker__icon" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <span class="tnf-file-picker__title">Tap to upload image</span>
                                <span class="tnf-file-picker__hint">JPEG, PNG, WebP or GIF — max 150 KB</span>
                            </span>
                            <span id="image-filename" class="tnf-file-picker__name">No file chosen</span>
                        </label>
                        @error('image')<p class="text-tnf-sm text-tnf-red">{{ $message }}</p>@enderror
                    </div>

                    <div class="tnf-field">
                        <label for="embed_url" class="tnf-label">Video URL <span class="font-normal text-tnf-muted">(optional)</span></label>
                        <input type="url" name="embed_url" id="embed_url" value="{{ old('embed_url') }}"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="tnf-input">
                        @error('embed_url')<p class="text-tnf-sm text-tnf-red">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="tnf-btn-primary w-full sm:w-auto">Submit for review</button>
                </form>
            </div>

            {{-- Submissions list --}}
            <div class="rounded-tnf-lg bg-white p-6 shadow-card">
                <h2 class="mb-4 text-tnf-lg font-bold text-tnf-navy">My Submissions</h2>

                @if($submissions->isEmpty())
                    <p class="text-tnf-sm text-tnf-muted">You have not submitted any stories yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($submissions as $submission)
                            <div class="rounded-tnf border border-tnf-gray-dark p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-semibold text-tnf-navy">{{ $submission->title }}</h3>
                                        <p class="mt-1 text-tnf-xs text-tnf-muted">
                                            @if($submission->category)
                                                <span>{{ $submission->category->name }}</span>
                                                <span class="mx-1">·</span>
                                            @endif
                                            Submitted {{ $submission->created_at->format('M d, Y g:i A') }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-tnf-xs font-semibold {{ $submission->statusBadgeClass() }}">
                                        {{ $submission->displayStatus() }}
                                    </span>
                                </div>

                                @if($submission->rejection_reason)
                                    <p class="mt-2 text-tnf-sm text-tnf-muted">
                                        <span class="font-medium">Reason:</span> {{ $submission->rejection_reason }}
                                    </p>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if($submission->isLive() && $submission->promotedArticle)
                                        <a href="{{ route('article.show', $submission->promotedArticle->slug) }}"
                                           class="text-tnf-sm font-semibold text-tnf-red hover:underline">
                                            View live article
                                        </a>
                                    @endif

                                    @if($submission->canWithdraw())
                                        <form action="{{ route('account.submissions.destroy', $submission) }}" method="POST"
                                              onsubmit="return confirm('Withdraw this submission?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-tnf-sm font-semibold text-tnf-muted hover:text-tnf-red">
                                                Withdraw
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-tnf-lg bg-white p-6 shadow-card">
                <p class="text-tnf-sm text-tnf-muted">
                    Staff accounts use the admin panel. Member submission tools are available to registered members only.
                </p>
                <a href="{{ url('/admin') }}" class="tnf-btn-primary mt-4 inline-flex">Open admin</a>
            </div>
        @endif
    </div>
    @push('styles')
        @vite(['resources/css/submission-editor.css'])
    @endpush

    @push('scripts')
        <script type="application/json" id="submission-editor-initial">@json(old('content', ''))</script>
        @vite(['resources/js/submission-editor.js'])
    @endpush
</x-site.layout>
