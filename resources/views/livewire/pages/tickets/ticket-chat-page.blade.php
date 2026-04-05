<div class="mx-auto max-w-3xl space-y-4">
    <div>
        <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Chat tiket') }} — {{ $ticket->ticket_number }}
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $ticket->title }}
        </p>
    </div>

    @livewire('ticket.chat', ['ticket' => $ticket], key('ticket-chat-' . $ticket->id))
</div>
