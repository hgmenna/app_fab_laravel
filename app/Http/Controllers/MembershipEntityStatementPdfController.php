<?php

namespace App\Http\Controllers;

use App\Helpers\FabPath;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\Federation;
use App\Models\PlayerMembership;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MembershipEntityStatementPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'entity_type' => ['required', 'in:club,federation'],
            'entity_id' => ['required', 'integer'],
            'discipline_id' => ['required', 'integer', 'exists:disciplines,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);
        $discipline = Discipline::with('directFederation')->findOrFail($data['discipline_id']);
        $entity = $data['entity_type'] === 'club'
            ? Club::findOrFail($data['entity_id'])
            : Federation::findOrFail($data['entity_id']);

        $accounts = PlayerMembership::query()
            ->with(['player.club', 'membership.discipline', 'club', 'payments.payer', 'payments.recorder'])
            ->whereHas('membership', fn (Builder $query) => $query
                ->where('discipline_id', $discipline->id)->where('year', $data['year']))
            ->when($data['entity_type'] === 'club', fn (Builder $query) => $query->where('club_id', $entity->id))
            ->when($data['entity_type'] === 'federation', function (Builder $query) use ($discipline, $entity): void {
                if ($discipline->affiliation_mode === 'direct') {
                    $query->whereRaw((int) $discipline->direct_federation_id === (int) $entity->id ? '1 = 1' : '1 = 0');
                } else {
                    $query->whereHas('player.club.city.state', fn (Builder $state) => $state->where('federation_id', $entity->id));
                }
            })
            ->get()->sortBy(fn (PlayerMembership $account): string => $account->player?->full_name ?? '');

        return Pdf::loadView('pdf.membership-entity-statement', [
            'accounts' => $accounts,
            'entity' => $entity,
            'entityType' => $data['entity_type'],
            'discipline' => $discipline,
            'year' => $data['year'],
            'logo' => is_file(FabPath::logo()) ? FabPath::logo() : null,
            'footerImage' => is_file(FabPath::footer()) ? FabPath::footer() : null,
        ])->setPaper('a4', 'landscape')->stream('estado-cuenta-'.strtolower($entity->short_name ?: $entity->name).'-'.$data['year'].'.pdf');
    }
}
