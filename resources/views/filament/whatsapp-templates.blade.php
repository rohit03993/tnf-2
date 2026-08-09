@php
    /** @var list<array{name?: string, language?: string, status?: string, category?: ?string, body?: ?string}> $templates */
    $templates = $templates ?? [];
    $syncedAt = $syncedAt ?? null;
@endphp

<div class="space-y-3 text-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-gray-500 dark:text-gray-400">
            @if($syncedAt)
                Last synced: {{ $syncedAt }}
            @else
                Not synced yet. Click <strong>Sync templates</strong> in the header.
            @endif
        </p>
        <a
            href="https://business.facebook.com/wa/manage/message-templates/"
            target="_blank"
            rel="noopener"
            class="font-medium text-primary-600 hover:underline"
        >
            Open Meta template manager →
        </a>
    </div>

    @if(count($templates) === 0)
        <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            No templates cached. Create them in Meta, wait for approval, then sync here.
        </p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full min-w-[640px] text-left">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Lang</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Category</th>
                        <th class="px-3 py-2 font-medium">Body preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($templates as $template)
                        @php
                            $status = strtoupper((string) ($template['status'] ?? 'UNKNOWN'));
                            $statusClass = match ($status) {
                                'APPROVED' => 'text-green-700 bg-green-50 dark:text-green-300 dark:bg-green-950',
                                'PENDING', 'IN_APPEAL' => 'text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-950',
                                'REJECTED', 'DISABLED', 'PAUSED', 'DELETED' => 'text-red-700 bg-red-50 dark:text-red-300 dark:bg-red-950',
                                default => 'text-gray-700 bg-gray-50 dark:text-gray-300 dark:bg-gray-900',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $template['name'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $template['language'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $template['category'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::limit((string) ($template['body'] ?? '—'), 80) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
