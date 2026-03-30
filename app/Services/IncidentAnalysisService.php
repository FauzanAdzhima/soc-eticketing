<?php

namespace App\Services;

use App\Models\IncidentAnalysis;
use App\Models\IncidentIoc;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class IncidentAnalysisService
{
    /**
     * Default submit behavior: if this analyst already has an analysis on the ticket, that row is
     * updated and IOCs are replaced. Set {@see $asAddendum} to true to always insert a new row.
     *
     * @param  array{severity?: string|null, impact?: string|null, root_cause?: string|null, recommendation?: string|null, analysis_result?: string|null}  $analysis
     * @param  list<array{type_id: int, value?: string, description?: string|null}>  $iocRows
     * @return array{0: IncidentAnalysis, 1: 'created'|'updated'|'addendum'}
     */
    public function store(Ticket $ticket, User $analyst, array $analysis, array $iocRows, bool $asAddendum = false): array
    {
        Gate::forUser($analyst)->authorize('analyze', $ticket);

        return DB::transaction(function () use ($ticket, $analyst, $analysis, $iocRows, $asAddendum) {
            $payload = [
                'ticket_id' => $ticket->id,
                'performed_by' => $analyst->id,
                'severity' => $analysis['severity'] ?? null,
                'impact' => $analysis['impact'] ?? null,
                'root_cause' => $analysis['root_cause'] ?? null,
                'recommendation' => $analysis['recommendation'] ?? null,
                'analysis_result' => $analysis['analysis_result'] ?? null,
            ];

            $mode = 'created';

            if ($asAddendum) {
                $record = IncidentAnalysis::query()->create($payload);
                $mode = 'addendum';
            } else {
                $existing = IncidentAnalysis::query()
                    ->where('ticket_id', $ticket->id)
                    ->where('performed_by', $analyst->id)
                    ->orderByDesc('id')
                    ->first();

                if ($existing !== null) {
                    $existing->update([
                        'severity' => $payload['severity'],
                        'impact' => $payload['impact'],
                        'root_cause' => $payload['root_cause'],
                        'recommendation' => $payload['recommendation'],
                        'analysis_result' => $payload['analysis_result'],
                    ]);
                    $existing->iocs()->delete();
                    $record = $existing;
                    $mode = 'updated';
                } else {
                    $record = IncidentAnalysis::query()->create($payload);
                }
            }

            foreach ($iocRows as $row) {
                $value = trim((string) ($row['value'] ?? ''));
                if ($value === '') {
                    continue;
                }

                IncidentIoc::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'analysis_id' => $record->id,
                    'incident_ioc_type_id' => (int) $row['type_id'],
                    'value' => $value,
                    'description' => filled($row['description'] ?? null) ? (string) $row['description'] : null,
                ]);
            }

            $ticket->refresh();
            $ticket->updateSubStatus(Ticket::SUB_STATUS_ANALYSIS, $analyst, false);

            return [$record->fresh(['iocs']), $mode];
        });
    }
}
