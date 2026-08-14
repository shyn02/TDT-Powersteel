<x-filament-panels::page>
    <div
        x-data="{
            scrollToBottom() {
                const el = this.$refs.thread;
                if (el) el.scrollTop = el.scrollHeight;
            }
        }"
        x-init="scrollToBottom()"
        x-on:livewire:updated.window="scrollToBottom()"
        wire:poll.5s="markIncomingAsRead"
        style="margin: 0; padding: 0;"
    >
        <div
            x-ref="thread"
            style="
                display: flex;
                flex-direction: column;
                gap: 4px;
                height: 420px;
                overflow-y: auto;
                padding: 16px;
                margin: 0;
                background: rgb(243 244 246);
                border-radius: 12px;
                border: 1px solid rgb(229 231 235);
            "
        >
            @forelse ($this->record->messages()->orderBy('created_at')->get() as $message)
                @php
                    $isStaff = $message->sender === 'staff';
                @endphp
                <div style="display: flex; justify-content: {{ $isStaff ? 'flex-end' : 'flex-start' }}; margin: 0; padding: 2px 0;">
                    <div style="max-width: 65%; display: flex; flex-direction: column; align-items: {{ $isStaff ? 'flex-end' : 'flex-start' }}; margin: 0;">
                        <div style="
                            padding: 8px 13px;
                            border-radius: 16px;
                            {{ $isStaff
                                ? 'background: rgb(249 115 22); color: white; border-bottom-right-radius: 4px;'
                                : 'background: white; color: rgb(17 24 39); border: 1px solid rgb(229 231 235); border-bottom-left-radius: 4px;' }}
                            font-size: 14px;
                            line-height: 1.35;
                            white-space: pre-wrap;
                            word-break: break-word;
                            margin: 0;
                        ">{{ $message->message }}</div>
                        <span style="font-size: 10.5px; color: rgb(156 163 175); margin: 2px 4px 0;">
                            {{ $isStaff ? 'Staff' : 'Client' }} · {{ \Carbon\Carbon::parse($message->created_at)->format('g:i A') }}
                        </span>
                    </div>
                </div>
            @empty
                <p style="text-align: center; color: rgb(156 163 175); margin: auto;">No messages yet.</p>
            @endforelse
        </div>

        {{-- Quick replies: tap to fill the input box below, then edit/send as needed --}}
        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0 0;">
            @foreach ($quickReplies as $reply)
                <button
                    type="button"
                    wire:click="useQuickReply('{{ addslashes($reply) }}')"
                    style="
                        padding: 5px 12px;
                        border-radius: 999px;
                        background: white;
                        border: 1px solid rgb(229 231 235);
                        color: rgb(75 85 99);
                        font-size: 12px;
                        cursor: pointer;
                        white-space: nowrap;
                    "
                    title="{{ $reply }}"
                >
                    {{ \Illuminate\Support\Str::limit($reply, 34) }}
                </button>
            @endforeach
        </div>

        <form wire:submit="sendReply" style="display: flex; gap: 10px; margin: 10px 0 0;">
            <input
                type="text"
                wire:model="newMessage"
                placeholder="Type a reply…"
                autocomplete="off"
                style="
                    flex: 1;
                    padding: 12px 16px;
                    border-radius: 999px;
                    border: 1px solid rgb(209 213 219);
                    font-size: 14px;
                    outline: none;
                    margin: 0;
                "
            >
            <button
                type="submit"
                style="
                    padding: 0 22px;
                    border-radius: 999px;
                    background: rgb(249 115 22);
                    color: white;
                    font-weight: 600;
                    font-size: 14px;
                    border: none;
                    cursor: pointer;
                "
            >
                Send
            </button>
        </form>
    </div>
</x-filament-panels::page>
