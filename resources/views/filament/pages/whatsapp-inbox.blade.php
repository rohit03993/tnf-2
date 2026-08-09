<div class="tnf-wa-inbox">
    <div class="tnf-wa-inbox__shell">
        <aside class="tnf-wa-inbox__list">
            <div class="tnf-wa-inbox__list-head">
                <h2>Chats</h2>
                <p>{{ count($conversations) }} conversation{{ count($conversations) === 1 ? '' : 's' }}</p>
            </div>

            <div class="tnf-wa-inbox__search">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, number, message…"
                />
            </div>

            <div class="tnf-wa-inbox__items">
                @forelse ($conversations as $conversation)
                    <button
                        type="button"
                        wire:click="selectConversation({{ \Illuminate\Support\Js::from($conversation['phone']) }})"
                        @class([
                            'tnf-wa-inbox__item',
                            'tnf-wa-inbox__item--active' => $selectedPhone === ($conversation['phone'] ?? null),
                        ])
                    >
                        <span class="tnf-wa-inbox__avatar">{{ strtoupper(substr($conversation['name'], 0, 1)) }}</span>
                        <span class="tnf-wa-inbox__item-body">
                            <span class="tnf-wa-inbox__item-top">
                                <strong>{{ $conversation['name'] }}</strong>
                                <span>{{ $conversation['last_at_label'] }}</span>
                            </span>
                            <span class="tnf-wa-inbox__item-preview">
                                @if (($conversation['last_direction'] ?? '') === 'inbound')
                                    <em>Them:</em>
                                @else
                                    <em>You:</em>
                                @endif
                                {{ $conversation['preview'] }}
                            </span>
                            <span class="tnf-wa-inbox__item-phone">{{ $conversation['phone_display'] }}</span>
                        </span>
                        @if ($conversation['unread'] ?? false)
                            <span class="tnf-wa-inbox__dot"></span>
                        @endif
                    </button>
                @empty
                    <p class="tnf-wa-inbox__empty">No conversations yet. Incoming WhatsApp messages will appear here.</p>
                @endforelse
            </div>
        </aside>

        <section class="tnf-wa-inbox__chat">
            @if (! filled($selectedPhone))
                <div class="tnf-wa-inbox__placeholder">
                    <p class="font-semibold">Select a chat</p>
                    <p>Open any number on the left to see sent &amp; received messages, including images.</p>
                </div>
            @else
                <div class="tnf-wa-inbox__chat-head">
                    <div>
                        <p class="tnf-wa-inbox__chat-name">{{ $this->selectedTitle }}</p>
                        <p class="tnf-wa-inbox__chat-phone">{{ $this->selectedPhoneDisplay }}</p>
                    </div>
                    <button type="button" wire:click="loadThread" class="tnf-wa-inbox__refresh">Refresh</button>
                </div>

                <div class="tnf-wa-inbox__messages" wire:poll.30s="loadThread">
                    @foreach ($messages as $message)
                        <div @class([
                            'tnf-wa-inbox__bubble',
                            'tnf-wa-inbox__bubble--out' => ($message['direction'] ?? '') === 'outbound',
                            'tnf-wa-inbox__bubble--in' => ($message['direction'] ?? '') === 'inbound',
                        ])>
                            @if (($message['is_image'] ?? false) && filled($message['media_url'] ?? null))
                                <a href="{{ $message['media_url'] }}" target="_blank" rel="noopener" class="tnf-wa-inbox__image-link">
                                    <img src="{{ $message['media_url'] }}" alt="WhatsApp image" class="tnf-wa-inbox__image" />
                                </a>
                            @elseif (($message['is_media'] ?? false) && filled($message['media_url'] ?? null))
                                <a href="{{ $message['media_url'] }}" target="_blank" rel="noopener" class="tnf-wa-inbox__file">
                                    📎 {{ $message['media_filename'] ?: 'Download media' }}
                                </a>
                            @endif

                            @if (filled($message['body'] ?? null))
                                <p>{{ $message['body'] }}</p>
                            @endif

                            <span class="tnf-wa-inbox__meta">
                                {{ $message['at'] }}
                                @if (filled($message['status'] ?? null))
                                    · {{ $message['status'] }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                <form wire:submit="sendReply" class="tnf-wa-inbox__composer">
                    <textarea
                        wire:model="replyBody"
                        rows="2"
                        placeholder="Reply within 24 hours of their last message…"
                    ></textarea>
                    <button type="submit">Send</button>
                </form>
            @endif
        </section>
    </div>
</div>

<style>
.tnf-wa-inbox { margin: -0.5rem; }
.tnf-wa-inbox__shell {
    display: grid;
    grid-template-columns: minmax(260px, 340px) 1fr;
    min-height: 70vh;
    border: 1px solid rgba(15,23,42,.12);
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
}
.tnf-wa-inbox__list { border-right: 1px solid rgba(15,23,42,.1); background: #f8fafc; display:flex; flex-direction:column; }
.tnf-wa-inbox__list-head { padding: 1rem 1rem .5rem; }
.tnf-wa-inbox__list-head h2 { margin:0; font-size:1rem; font-weight:700; }
.tnf-wa-inbox__list-head p { margin:.15rem 0 0; font-size:.75rem; color:#64748b; }
.tnf-wa-inbox__search { padding: .5rem 1rem 1rem; }
.tnf-wa-inbox__search input {
    width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:.55rem .75rem; font-size:.875rem;
}
.tnf-wa-inbox__items { overflow:auto; flex:1; }
.tnf-wa-inbox__item {
    width:100%; text-align:left; display:flex; gap:.75rem; align-items:flex-start;
    padding:.75rem 1rem; border:0; border-bottom:1px solid rgba(15,23,42,.06); background:transparent; cursor:pointer;
}
.tnf-wa-inbox__item:hover { background:#eef2ff; }
.tnf-wa-inbox__item--active { background:#fee2e2; }
.tnf-wa-inbox__avatar {
    width:2.25rem; height:2.25rem; border-radius:999px; background:#BC1E38; color:#fff;
    display:inline-flex; align-items:center; justify-content:center; font-weight:700; flex:none;
}
.tnf-wa-inbox__item-body { min-width:0; flex:1; display:flex; flex-direction:column; gap:.15rem; }
.tnf-wa-inbox__item-top { display:flex; justify-content:space-between; gap:.5rem; font-size:.8rem; }
.tnf-wa-inbox__item-top span { color:#64748b; white-space:nowrap; }
.tnf-wa-inbox__item-preview { font-size:.78rem; color:#475569; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.tnf-wa-inbox__item-phone { font-size:.7rem; color:#94a3b8; }
.tnf-wa-inbox__dot { width:.55rem; height:.55rem; border-radius:999px; background:#BC1E38; margin-top:.35rem; flex:none; }
.tnf-wa-inbox__empty { padding:1.5rem 1rem; color:#64748b; font-size:.875rem; }
.tnf-wa-inbox__chat { display:flex; flex-direction:column; min-width:0; background:#f1f5f9; }
.tnf-wa-inbox__placeholder { margin:auto; text-align:center; color:#64748b; padding:2rem; }
.tnf-wa-inbox__chat-head {
    display:flex; justify-content:space-between; align-items:center; gap:1rem;
    padding:.85rem 1rem; background:#fff; border-bottom:1px solid rgba(15,23,42,.08);
}
.tnf-wa-inbox__chat-name { margin:0; font-weight:700; }
.tnf-wa-inbox__chat-phone { margin:0; font-size:.8rem; color:#64748b; }
.tnf-wa-inbox__refresh {
    border:1px solid #cbd5e1; background:#fff; border-radius:8px; padding:.35rem .7rem; font-size:.8rem; cursor:pointer;
}
.tnf-wa-inbox__messages {
    flex:1; overflow:auto; padding:1rem; display:flex; flex-direction:column; gap:.65rem;
}
.tnf-wa-inbox__bubble {
    max-width:min(78%, 34rem); padding:.65rem .8rem; border-radius:14px; font-size:.875rem; line-height:1.4;
    box-shadow:0 1px 2px rgba(15,23,42,.06);
}
.tnf-wa-inbox__bubble--in { align-self:flex-start; background:#fff; }
.tnf-wa-inbox__bubble--out { align-self:flex-end; background:#fecaca; }
.tnf-wa-inbox__image { display:block; max-width:100%; border-radius:10px; margin-bottom:.4rem; }
.tnf-wa-inbox__file { display:inline-block; margin-bottom:.35rem; color:#BC1E38; font-weight:600; }
.tnf-wa-inbox__meta { display:block; margin-top:.35rem; font-size:.7rem; color:#64748b; }
.tnf-wa-inbox__composer {
    display:flex; gap:.5rem; padding:.75rem; background:#fff; border-top:1px solid rgba(15,23,42,.08);
}
.tnf-wa-inbox__composer textarea {
    flex:1; border:1px solid #cbd5e1; border-radius:10px; padding:.55rem .7rem; font-size:.875rem; resize:vertical;
}
.tnf-wa-inbox__composer button {
    border:0; background:#BC1E38; color:#fff; border-radius:10px; padding:0 1rem; font-weight:700; cursor:pointer;
}
@media (max-width: 900px) {
    .tnf-wa-inbox__shell { grid-template-columns: 1fr; min-height: auto; }
    .tnf-wa-inbox__list { max-height: 40vh; }
    .tnf-wa-inbox__chat { min-height: 55vh; }
}
</style>
