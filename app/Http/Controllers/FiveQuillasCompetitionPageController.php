<?php

namespace App\Http\Controllers;

use App\Models\FiveQuillasCompetition;
use Illuminate\View\View;

class FiveQuillasCompetitionPageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $competition = FiveQuillasCompetition::query()
            ->where('public_slug', $slug)->where('is_public', true)
            ->with('tournament:id,name,start_date,end_date')->firstOrFail();

        return view('public.five-quillas-competition', [
            'competition' => $competition,
            'jsonUrl' => route('five-quillas-competition.public', ['slug' => $slug]),
        ]);
    }
}
