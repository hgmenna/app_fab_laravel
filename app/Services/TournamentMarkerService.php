<?php

namespace App\Services;

use App\Livewire\CompetitionOrganizationUpdates;
use App\Models\FiveQuillasMatch;
use App\Models\MarkerMatchAssignment;
use App\Models\MarkerTournamentLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TournamentMarkerService
{
    public function __construct(private MarkerApi $api, private FiveQuillasCompetitionService $competition) {}

    public static function publicScoreKey(int $matchId): string
    {
        return 'tournament-marker-score:'.$matchId;
    }

    /** @return array<string, string> */
    public function pilotUsers(string $club): array
    {
        if (! isset($this->api->pilotClubOptions()[$club])) {
            return [];
        }

        $result = $this->api->request('GET', '/users?club='.rawurlencode($club));
        if (! is_array($result['users'] ?? null)) {
            throw new RuntimeException('El marcador no devolvió la lista de usuarios habilitados.');
        }

        return collect($result['users'])
            ->filter(fn ($username): bool => is_string($username) && $username !== '')
            ->mapWithKeys(fn (string $username): array => [$username => $username])
            ->all();
    }

    public function connect(int $tournamentId, array $credentials): void
    {
        $code = Str::lower(trim($credentials['institution']));
        $pilotClubs = $this->api->pilotClubOptions();
        if ($pilotClubs !== null && ! array_key_exists($code, $pilotClubs)) {
            throw new RuntimeException('Elegí un club habilitado para la prueba.');
        }
        $existing = MarkerTournamentLink::query()->where('tournament_id', $tournamentId)->first();
        if ($existing && $existing->club_code !== $code && $existing->assignments()->whereIn('status', ['pending', 'sent'])->exists()) {
            throw new RuntimeException('Todavía hay partidas pendientes del club vinculado.');
        }
        $externalId = $existing?->club_code === $code ? $existing->external_id : (string) Str::uuid();
        $username = trim((string) ($credentials['username'] ?? ''));
        $grant = $this->api->request('POST', '/login', [...$credentials, 'username' => $username, 'tournament' => $externalId]);
        MarkerTournamentLink::query()->updateOrCreate(['tournament_id' => $tournamentId], [
            'external_id' => $externalId,
            'club_code' => $grant['code'],
            'club_name' => $grant['club'],
            'marker_username' => $grant['username'] ?? $username,
            'token' => $grant['token'],
            'expires_at' => Carbon::createFromTimestampMs($grant['expiresAt']),
        ]);
    }

    /** @return array{linked: list<int>, available: list<int>, unlinked: list<int>} */
    public function tableNumbers(MarkerTournamentLink $link): array
    {
        $response = $this->api->request('GET', '/tables', token: $link->token);
        if (! is_array($response['tables'] ?? null)) {
            throw new RuntimeException('El servidor de marcadores no informó las mesas.');
        }

        $numbers = ['linked' => [], 'available' => [], 'unlinked' => []];
        foreach ($response['tables'] as $table) {
            if (! is_int($table['number'] ?? null) || $table['number'] < 1 || $table['number'] > 1000) {
                throw new RuntimeException('El servidor de marcadores devolvió un número de mesa inválido.');
            }
            if (($table['linked'] ?? false) === true) {
                $numbers['linked'][] = $table['number'];
                if (($table['available'] ?? false) === true) {
                    $numbers['available'][] = $table['number'];
                }
            } else {
                $numbers['unlinked'][] = $table['number'];
            }
        }

        return $numbers;
    }

    public function callAndSend(MarkerTournamentLink $link, FiveQuillasMatch $match, int $tableNumber, string $revision): void
    {
        $tables = $this->tableNumbers($link)['available'];
        $failure = DB::transaction(function () use ($link, $match, $tableNumber, $revision, $tables): ?RuntimeException {
            $called = $this->competition->callMatch($match, $tableNumber, $revision, $tables);
            if ($this->outstanding($link, $called)) {
                throw ValidationException::withMessages([
                    'table_number' => 'Cancelá el envío a la tablet antes de cambiar la mesa.',
                ]);
            }

            return $this->sendLocked($link, $called);
        });

        if ($failure) {
            throw new RuntimeException('La mesa quedó asignada y el envío está pendiente. '.$failure->getMessage(), previous: $failure);
        }
    }

    public function send(MarkerTournamentLink $link, FiveQuillasMatch $match): void
    {
        $failure = DB::transaction(function () use ($link, $match): ?RuntimeException {
            $current = FiveQuillasMatch::query()->lockForUpdate()->findOrFail($match->id);
            return $this->sendLocked($link, $current);
        });
        if ($failure) {
            throw $failure;
        }
    }

    private function sendLocked(MarkerTournamentLink $link, FiveQuillasMatch $match): ?RuntimeException
    {
        $match->loadMissing(['phase.competition', 'playerOne.player.category', 'playerTwo.player.category']);
        $one = $match->playerOne?->player;
        $two = $match->playerTwo?->player;
        if ((int) $match->phase->competition->tournament_id !== (int) $link->tournament_id
            || $match->status !== 'called' || ! $match->table_number || $match->is_bye || ! $one || ! $two) {
            throw new RuntimeException('Llamá primero el partido y asignale una mesa.');
        }
        $bestOf = (int) $match->phase->sets_to_play;
        if ($bestOf < 1 || $bestOf > 9 || $bestOf % 2 === 0) {
            throw new RuntimeException('La fase debe estar configurada al mejor de 1, 3, 5, 7 o 9 sets.');
        }
        $settings = [
            'table_number' => $match->table_number, 'revision' => $this->markerRevision($match),
            'points_per_set' => $match->phase->points_per_set, 'sets_to_win' => intdiv($bestOf, 2) + 1,
        ];
        $assignment = $link->assignments()->firstOrCreate(['five_quillas_match_id' => $match->id], [
            'external_id' => (string) Str::uuid(),
            ...$settings,
            'status' => 'pending',
        ]);
        if ($assignment->status === 'imported' || $assignment->status === 'cancelled') {
            $assignment->update([
                'external_id' => (string) Str::uuid(), ...$settings, 'status' => 'pending',
            ]);
        }
        if (! $this->matchesAssignment($assignment, $match)) {
            throw new RuntimeException('El partido cambió después del envío. Revisá su estado en Marcadores.');
        }
        try {
            $this->api->request('POST', '/matches', [
                'id' => $assignment->external_id,
                'table' => (int) $assignment->table_number,
                'players' => [
                    ['name' => $one->full_name, 'category' => $one->category?->code ?: $one->category?->name],
                    ['name' => $two->full_name, 'category' => $two->category?->code ?: $two->category?->name],
                ],
                'pointsPerSet' => (int) $assignment->points_per_set,
                'setsToWin' => (int) $assignment->sets_to_win,
            ], $link->token);
        } catch (RuntimeException $exception) {
            // Conserva el identificador para reintentar aunque se pierda la respuesta.
            return $exception;
        }
        $assignment->update(['status' => 'sent']);

        return null;
    }

    private function markerRevision(FiveQuillasMatch $match): string
    {
        return hash('sha256', implode(':', [
            $match->id, $match->phase_id, $match->table_number,
            $match->player_one_entry_id, $match->player_two_entry_id,
        ]));
    }

    private function matchesAssignment(MarkerMatchAssignment $assignment, FiveQuillasMatch $match): bool
    {
        return in_array($match->status, ['called', 'in_progress'], true)
            && (int) $match->table_number === (int) $assignment->table_number
            && in_array($assignment->revision, [$this->markerRevision($match), $match->editRevision()], true);
    }

    public function outstanding(MarkerTournamentLink $link, FiveQuillasMatch $match): ?MarkerMatchAssignment
    {
        return $link->assignments()->where('five_quillas_match_id', $match->id)->whereIn('status', ['pending', 'sent'])->first();
    }

    public function cancel(MarkerTournamentLink $link, FiveQuillasMatch $match): void
    {
        $assignment = $this->outstanding($link, $match);
        if ($assignment) {
            $this->api->request('POST', '/cancel', ['id' => $assignment->external_id], $link->token);
            $assignment->update(['status' => 'cancelled']);
        }
    }

    public function live(MarkerTournamentLink $link, iterable $assignments, bool $importResults = true): array
    {
        $scores = [];
        foreach (collect($assignments)->whereIn('status', ['pending', 'sent'])->chunk(30) as $chunk) {
            $query = $chunk->map(fn (MarkerMatchAssignment $assignment) => 'id='.rawurlencode($assignment->external_id))->implode('&');
            $data = $this->api->request('GET', '/matches?'.$query, token: $link->token);
            $assignmentsById = $chunk->keyBy('external_id');
            foreach ($data['matches'] ?? [] as $score) {
                $assignment = $assignmentsById->get($score['id']);
                if (! $assignment) {
                    continue;
                }
                $scores[$assignment->id] = $score;
                $key = self::publicScoreKey($assignment->five_quillas_match_id);
                if ($score['status'] === 'in_progress') {
                    Cache::put($key, [
                        'revision' => $assignment->revision,
                        'points' => $score['points'],
                        'sets' => $score['sets'] ?? [],
                    ], now()->addSeconds(4));
                } else {
                    Cache::forget($key);
                }
                if ($importResults && $score['status'] === 'completed') {
                    $this->importResult($link, $assignment, $score['sets']);
                }
            }
        }

        return $scores;
    }

    public function publicScores(int $tournamentId, array $matches): array
    {
        $scores = [];
        if (! $this->api->availableFor($tournamentId)) {
            return $scores;
        }

        $link = MarkerTournamentLink::query()->where('tournament_id', $tournamentId)->where('expires_at', '>', now())->first();
        if (! $link) {
            return $scores;
        }

        $assignments = $link->assignments()->whereIn('five_quillas_match_id', $matches)
            ->whereIn('status', ['pending', 'sent'])->with('match')->get()
            ->filter(fn ($assignment): bool => $assignment->match
                && $this->matchesAssignment($assignment, $assignment->match));
        $cached = Cache::many($assignments->map(fn ($assignment): string => self::publicScoreKey($assignment->five_quillas_match_id))->all());
        $assignments = $assignments->reject(function ($assignment) use ($cached, &$scores): bool {
            $key = self::publicScoreKey($assignment->five_quillas_match_id);
            $score = $cached[$key] ?? null;
            if (($score['revision'] ?? null) !== $assignment->revision) {
                return false;
            }
            $scores[$key] = $score;

            return true;
        });
        try {
            $live = $this->live($link, $assignments, importResults: false);
            foreach ($assignments as $assignment) {
                $score = $live[$assignment->id] ?? null;
                if (($score['status'] ?? null) === 'completed') {
                    $this->importResult($link, $assignment, $score['sets']);
                    $assignment->refresh();
                    if ($assignment->status !== 'sent' && $assignment->status !== 'pending') {
                        Cache::forget(self::publicScoreKey($assignment->five_quillas_match_id));
                        continue;
                    }
                }
                if ($score && in_array($score['status'], ['in_progress', 'completed'], true)) {
                    $key = self::publicScoreKey($assignment->five_quillas_match_id);
                    $scores[$key] = [
                        'revision' => $assignment->revision,
                        'points' => $score['status'] === 'completed' ? null : $score['points'],
                        'sets' => $score['sets'] ?? [],
                    ];
                    Cache::put($key, $scores[$key], now()->addSeconds(4));
                }
            }
        } catch (RuntimeException) {
            // Una interrupción del marcador no impide publicar resultados guardados.
        }

        return $scores;
    }

    private function importResult(MarkerTournamentLink $link, MarkerMatchAssignment $assignment, array $sets): void
    {
        DB::transaction(function () use ($link, $assignment, $sets): void {
            $match = $assignment->match()->with('phase')->lockForUpdate()->first();
            $locked = $link->assignments()->lockForUpdate()->find($assignment->id);
            if (! $match || ! $locked || ! in_array($locked->status, ['pending', 'sent'], true) || $locked->external_id !== $assignment->external_id) {
                return;
            }
            $target = (int) $locked->points_per_set;
            if (! $this->matchesAssignment($locked, $match) || ! empty($match->set_scores) || $target !== (int) $match->phase->points_per_set || (int) $locked->sets_to_win !== intdiv((int) $match->phase->sets_to_play, 2) + 1) {
                $locked->update(['status' => 'conflict']);
                return;
            }
            $scores = array_map(fn (array $points): array => [
                'player_one_points' => min($points[0], $target),
                'player_two_points' => min($points[1], $target),
            ], $sets);
            try {
                $updated = $this->competition->recordMatchResult($match, $scores, $match->editRevision());
            } catch (ValidationException) {
                $locked->update(['status' => 'conflict']);

                return;
            }
            $locked->update(['status' => $updated->status === 'completed' ? 'imported' : 'conflict']);
            DB::afterCommit(fn () => Cache::put(CompetitionOrganizationUpdates::revisionKey($link->tournament_id), (string) Str::uuid(), now()->addDay()));
        });
    }
}
