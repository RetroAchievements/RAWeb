<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketState;
use App\Community\Services\SubscriptionService;
use App\Enums\ClientSupportLevel;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\EmulatorCoreRestriction;
use App\Models\PlayerSession;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketCreatedNotification;
use App\Platform\Data\StoreTicketData;
use App\Platform\Services\UserAgentService;
use App\Platform\Services\UserTicketCountService;
use InvalidArgumentException;

class CreateTicketAction
{
    public function execute(StoreTicketData $data, User $user): Ticket
    {
        // Ticketable is typed as Achievement|Leaderboard, but all the code below this block
        // assumes an achievement. For now, we'll fail loudly here if a leaderboard is passed in.
        if (!$data->ticketable instanceof Achievement) {
            throw new InvalidArgumentException('Ticket creation supports achievements only.');
        }

        $emulator = Emulator::where('name', $data->emulator)->first();

        $achievement = $data->ticketable;
        $maintainer = $achievement->getMaintainerAt(now());

        $ticket = Ticket::create([
            'ticketable_type' => 'achievement',
            'ticketable_id' => $achievement->id,
            'reporter_id' => $user->id,
            'ticketable_author_id' => $maintainer?->id,
            'type' => $data->issue,
            'hardcore' => $data->mode === 'hardcore',
            'body' => $this->formatTicketNote($data, !$emulator),
        ]);

        if ($maintainer) {
            app(UserTicketCountService::class)->clearForUserId($maintainer->id);
        }

        $ticket->game_hash_id = $data->gameHash->id;
        $ticket->emulator_core = $data->core ?: null;

        if ($emulator) {
            $ticket->emulator_id = $emulator->id;
            $ticket->emulator_version = $data->emulatorVersion;
        }

        $ticket->state = $this->resolveTicketState($data, $user, $achievement, $emulator);

        $ticket->save();

        // Don't notify developers about quarantined tickets.
        if ($ticket->state !== TicketState::Quarantined) {
            $this->sendInitialTicketEmailToAssignee($ticket, $achievement, $maintainer);
            $this->sendInitialTicketEmailsToSubscribers($ticket, $achievement, $maintainer);
        }

        return $ticket;
    }

    private function resolveTicketState(
        StoreTicketData $data,
        User $user,
        Achievement $achievement,
        ?Emulator $emulator,
    ): TicketState {
        // The form's emulator list is the emulators table plus a synthetic "Other" entry, so a
        // name that resolves to nothing means the reporter is on a client we can't reason about.
        // Hold the ticket rather than failing open on a missing primary signal.
        if (!$emulator) {
            return TicketState::Quarantined;
        }

        // Quarantine a ticket when the reported emulator can't debug triggers or is casual-only.
        if (!$emulator->can_debug_triggers || $emulator->softcore_only) {
            return TicketState::Quarantined;
        }

        // Quarantine a ticket when the reported core is restricted.
        if (!empty($data->core) && EmulatorCoreRestriction::forCore($data->core)->exists()) {
            return TicketState::Quarantined;
        }

        // Fall back to the session's user agent. It carries signals the form can't:
        // client version thresholds and offline submission clients.
        $latestSession = PlayerSession::where('user_id', $user->id)
            ->where('game_id', $achievement->game_id)
            ->latest()
            ->first();
        if ($latestSession?->user_agent) {
            $userAgentService = new UserAgentService();

            [$clientSupportLevel, $coreRestriction] = $userAgentService
                ->getSupportLevelAndCoreRestriction($latestSession->user_agent);

            if ($coreRestriction || $clientSupportLevel === ClientSupportLevel::CasualOnly) {
                return TicketState::Quarantined;
            }

            // Quarantine a ticket when it's opened against an emulator that lacks toolkit support.
            $sessionEmulator = $userAgentService->getEmulatorUserAgent($latestSession->user_agent)?->emulator;
            if ($sessionEmulator && !$sessionEmulator->can_debug_triggers) {
                return TicketState::Quarantined;
            }
        }

        return TicketState::Open;
    }

    private function sendInitialTicketEmailToAssignee(Ticket $ticket, Achievement $achievement, ?User $maintainer): void
    {
        if (
            $maintainer
            && $maintainer->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])
            && BitSet($maintainer->preferences_bitfield, UserPreference::EmailOn_TicketActivity)
        ) {
            $maintainer->notify(new TicketCreatedNotification($ticket, $achievement->game, $achievement, isMaintainer: true));
        }
    }

    private function sendInitialTicketEmailsToSubscribers(Ticket $ticket, Achievement $achievement, ?User $maintainer): void
    {
        $game = $achievement->game;

        $subscriptionService = new SubscriptionService();
        $subscribers = $subscriptionService->getSubscribers(SubscriptionSubjectType::GameTickets, $game->id)
            ->filter(fn ($s) => isset($s->email) && BitSet($s->preferences_bitfield, UserPreference::EmailOn_TicketActivity));

        foreach ($subscribers as $subscriber) {
            if ($subscriber->is($maintainer)) {
                // maintainer explicitly notified regardless of subscription state via
                // the assignee notification above. don't notify them again.
            } elseif ($subscriber->is($ticket->reporter)) {
                // reporter doesn't need to be notified of the new ticket. they just created it!
            } else {
                $subscriber->notify(new TicketCreatedNotification($ticket, $game, $achievement, isMaintainer: false));
            }
        }
    }

    private function formatTicketNote(StoreTicketData $data, bool $captureEmulatorData): string
    {
        $note = trim($data->description);
        $extraNotes = [];

        // Add rich presence if provided.
        if ($data->extra) {
            $richPresence = $this->decodeExtra($data->extra);
            if ($richPresence) {
                $extraNotes[] = "Rich Presence at time of trigger:\n{$richPresence}";
            }
        }

        // When the emulator isn't in the DB, embed its info in the note
        // so it's still visible to developers.
        if ($captureEmulatorData) {
            $emulatorInfo = $data->emulator;
            if ($data->core && in_array($data->emulator, ['RetroArch', 'RALibRetro'], true)) {
                $emulatorInfo .= " ({$data->core})";
            }
            $extraNotes[] = "Emulator: {$emulatorInfo}";
            $extraNotes[] = "Emulator Version: {$data->emulatorVersion}";
        }

        // Combine all notes.
        if (!empty($extraNotes)) {
            $note .= "\n\n" . implode("\n", $extraNotes);
        }

        return $note;
    }

    private function decodeExtra(?string $extra): ?string
    {
        if (!$extra) {
            return null;
        }

        $decoded = json_decode(base64_decode($extra));
        if (!$decoded) {
            return null;
        }

        return $decoded->triggerRichPresence ?? null;
    }
}
