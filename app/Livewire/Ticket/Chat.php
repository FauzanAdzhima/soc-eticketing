<?php

namespace App\Livewire\Ticket;

use App\Models\Ticket;
use App\Models\TicketChatRead;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class Chat extends Component
{
    use WithFileUploads;

    public Ticket $ticket;

    #[Locked]
    public bool $isGuest = false;

    /** Plain token for guest attachment URLs only; empty when staff. */
    #[Locked]
    public string $guestToken = '';

    public string $body = '';

    /** @var 'internal'|'external' */
    public string $visibility = 'external';

    public $attachment = null;

    public function mount(Ticket $ticket, ?string $guestToken = null): void
    {
        $this->ticket = $ticket;

        $token = $guestToken !== null ? trim($guestToken) : '';

        if ($token !== '') {
            if (! $this->reporterTokenMatches($token)) {
                abort(403);
            }
            $this->isGuest = true;
            $this->guestToken = $token;

            return;
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->authorize('view', $this->ticket);
        $this->syncDefaultVisibility();
    }

    public function updatedVisibility(string $value): void
    {
        if ($this->isGuest) {
            return;
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        if ($value === TicketMessage::VISIBILITY_INTERNAL && ! $user->can('ticket.chat.send_internal')) {
            $this->visibility = TicketMessage::VISIBILITY_EXTERNAL;
        }
    }

    public function updatedBody(): void
    {
        if (trim($this->body) === '') {
            return;
        }

        $throttleKey = $this->isGuest
            ? 'ticket_chat_typing_pulse.g.'.hash('sha256', $this->guestToken)
            : 'ticket_chat_typing_pulse.u.'.Auth::id();

        if (Cache::has($throttleKey)) {
            return;
        }

        Cache::put($throttleKey, 1, 1);
        $this->pulseTyping();
    }

    public function canSendExternal(): bool
    {
        if ($this->isGuest) {
            return true;
        }

        $user = Auth::user();

        return $user instanceof User && $user->can('ticket.chat.send_external');
    }

    public function canSendInternal(): bool
    {
        if ($this->isGuest) {
            return false;
        }

        $user = Auth::user();

        return $user instanceof User && $user->can('ticket.chat.send_internal');
    }

    public function canUseChat(): bool
    {
        return $this->isGuest || $this->canSendExternal() || $this->canSendInternal();
    }

    public function removeComposerAttachment(): void
    {
        $this->reset('attachment');
    }

    public function sendMessage(): void
    {
        if (! $this->canUseChat()) {
            abort(403);
        }

        $this->validate([
            'body' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar'],
        ]);

        $text = trim($this->body);
        if ($text === '') {
            $this->addError('body', __('Pesan tidak boleh kosong.'));

            return;
        }

        $user = Auth::user();

        if ($this->isGuest) {
            $visibility = TicketMessage::VISIBILITY_EXTERNAL;
            $userId = null;
            $guestName = $this->ticket->reporter_name ?: 'Pelapor';
        } else {
            if (! $user instanceof User) {
                abort(403);
            }

            $visibility = $this->visibility === TicketMessage::VISIBILITY_INTERNAL
                ? TicketMessage::VISIBILITY_INTERNAL
                : TicketMessage::VISIBILITY_EXTERNAL;

            if ($visibility === TicketMessage::VISIBILITY_INTERNAL && ! $user->can('ticket.chat.send_internal')) {
                abort(403);
            }

            if ($visibility === TicketMessage::VISIBILITY_EXTERNAL && ! $user->can('ticket.chat.send_external')) {
                abort(403);
            }

            $userId = $user->id;
            $guestName = null;
        }

        $attachmentPath = null;
        $originalName = null;
        if ($this->attachment !== null) {
            $attachmentPath = $this->attachment->store('ticket-chat/'.$this->ticket->public_id, 'public');
            $originalName = $this->attachment->getClientOriginalName();
        }

        TicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $userId,
            'guest_name' => $guestName,
            'visibility' => $visibility,
            'message' => $text,
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => $originalName,
        ]);

        $this->reset('body', 'attachment');
        $this->syncDefaultVisibility();

        $this->markChatRead();
        $this->dispatch('ticket-chat-scroll-bottom');
    }

    public function markChatRead(): void
    {
        $maxId = $this->maxVisibleMessageId();
        if ($maxId === null) {
            return;
        }

        if ($this->isGuest) {
            session([$this->guestReadSessionKey() => $maxId]);

            return;
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        TicketChatRead::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'ticket_id' => $this->ticket->id,
            ],
            ['last_read_message_id' => $maxId]
        );
    }

    public function pulseTyping(): void
    {
        $key = 'ticket_chat_typing.'.$this->ticket->id;
        $payload = Cache::get($key, []);
        $now = now()->timestamp;
        foreach ($payload as $id => $data) {
            if (($data['until'] ?? 0) < $now) {
                unset($payload[$id]);
            }
        }

        if ($this->isGuest) {
            $senderKey = 'g:'.hash('sha256', $this->guestToken);
            $payload[$senderKey] = [
                'until' => $now + 6,
            ];
        } else {
            $user = Auth::user();
            if (! $user instanceof User) {
                return;
            }

            $senderKey = 'u:'.$user->id;
            $payload[$senderKey] = [
                'name' => $user->name,
                'until' => $now + 6,
            ];
        }

        Cache::put($key, $payload, 12);
    }

    public static function roleBadgeColor(string $roleName): string
    {
        return match ($roleName) {
            'admin' => 'zinc',
            'pic' => 'violet',
            'analis' => 'indigo',
            'responder' => 'emerald',
            'koordinator' => 'amber',
            'pimpinan' => 'rose',
            default => 'zinc',
        };
    }

    public function attachmentUrl(TicketMessage $message): string
    {
        if ($message->attachment_path === null || $message->attachment_path === '') {
            return '#';
        }

        if ($this->isGuest) {
            return route('tickets.track.chat.attachment', [
                'ticket' => $this->ticket->public_id,
                'token' => $this->guestToken,
                'message' => $message->id,
            ]);
        }

        return route('tickets.chat.attachment.show', [
            'ticket' => $this->ticket->public_id,
            'message' => $message->id,
        ]);
    }

    public function render(): View
    {
        $this->ticket->refresh();

        $query = TicketMessage::query()
            ->forTicket($this->ticket->id)
            ->withSenderRoles()
            ->orderBy('created_at');

        if ($this->isGuest) {
            $query->visibleToGuest();
        }

        $chatMessages = $query->get();
        $lastReadMessageId = $this->currentLastReadMessageId();
        $firstUnreadMessageId = $this->firstUnreadMessageId($chatMessages, $lastReadMessageId);

        return view('livewire.ticket.chat', [
            'chatMessages' => $chatMessages,
            'firstUnreadMessageId' => $firstUnreadMessageId,
            'typingIndicator' => $this->resolveTypingIndicator(),
        ]);
    }

    private function reporterTokenMatches(string $plainToken): bool
    {
        $stored = $this->ticket->reporter_chat_token_hash;

        if (! is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, hash('sha256', $plainToken));
    }

    private function syncDefaultVisibility(): void
    {
        if ($this->isGuest) {
            return;
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        if (! $user->can('ticket.chat.send_internal')) {
            $this->visibility = TicketMessage::VISIBILITY_EXTERNAL;
        } elseif (! $user->can('ticket.chat.send_external')) {
            $this->visibility = TicketMessage::VISIBILITY_INTERNAL;
        } else {
            $this->visibility = TicketMessage::VISIBILITY_EXTERNAL;
        }
    }

    private function maxVisibleMessageId(): ?int
    {
        $query = TicketMessage::query()->forTicket($this->ticket->id);
        if ($this->isGuest) {
            $query->visibleToGuest();
        }

        $max = $query->max('id');

        return is_numeric($max) ? (int) $max : null;
    }

    private function guestReadSessionKey(): string
    {
        $tokenPart = $this->guestToken !== '' ? substr(hash('sha256', $this->guestToken), 0, 16) : 'x';

        return 'ticket_chat_guest_read.'.$this->ticket->id.'.'.$tokenPart;
    }

    private function currentLastReadMessageId(): ?int
    {
        if ($this->isGuest) {
            $v = session($this->guestReadSessionKey());

            return is_numeric($v) ? (int) $v : null;
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            return null;
        }

        $v = TicketChatRead::query()
            ->where('user_id', $user->id)
            ->where('ticket_id', $this->ticket->id)
            ->value('last_read_message_id');

        return is_numeric($v) ? (int) $v : null;
    }

    /**
     * @param  Collection<int, TicketMessage>  $messages
     */
    private function firstUnreadMessageId(Collection $messages, ?int $lastReadMessageId): ?int
    {
        foreach ($messages as $message) {
            if ($lastReadMessageId === null || (int) $message->id > $lastReadMessageId) {
                return (int) $message->id;
            }
        }

        return null;
    }

    private function resolveTypingIndicator(): ?string
    {
        $key = 'ticket_chat_typing.'.$this->ticket->id;
        $payload = Cache::get($key, []);
        $now = now()->timestamp;

        $guestTyping = false;
        /** @var list<string> $otherStaffNames */
        $otherStaffNames = [];

        foreach ($payload as $senderKey => $data) {
            if (($data['until'] ?? 0) < $now) {
                continue;
            }

            $senderKey = (string) $senderKey;

            if (str_starts_with($senderKey, 'g:')) {
                $guestTyping = true;

                continue;
            }

            if (str_starts_with($senderKey, 'u:')) {
                $uid = (int) substr($senderKey, 2);
                if ($this->isGuest) {
                    $otherStaffNames[] = 'tim';

                    continue;
                }

                if (Auth::id() !== null && $uid === (int) Auth::id()) {
                    continue;
                }

                $otherStaffNames[] = (string) ($data['name'] ?? __('Tim'));
            }
        }

        if ($this->isGuest) {
            return $otherStaffNames !== [] ? __('Tim sedang mengetik…') : null;
        }

        $parts = [];
        if ($guestTyping) {
            $parts[] = ($this->ticket->reporter_name ?: __('Pelapor')).' '.__('sedang mengetik…');
        }

        foreach (array_unique($otherStaffNames) as $name) {
            $parts[] = $name.' '.__('sedang mengetik…');
        }

        return $parts === [] ? null : implode(' ', $parts);
    }
}
