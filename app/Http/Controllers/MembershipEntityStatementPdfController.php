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

/**
 * Genera el estado de afiliaciones a cargo de una institución.
 */
class MembershipEntityStatementPdfController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
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
            ->where('debtor_type', $data['entity_type'])
            ->where('debtor_id', $entity->id)
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
