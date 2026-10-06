<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreeCushionRanking extends Model
{
    protected $fillable = [
        'season', 'category_id', 'player_id', 'position', 'total_caroms', 'total_innings',
        'high_run', 'high_run_achieved_at', 'general_average', 'best_match_average',
        'ranking_points', 'stages_played', 'total_stages',
    ];

    protected $casts = [
        'high_run_achieved_at' => 'date',
        'general_average' => 'decimal:6',
        'best_match_average' => 'decimal:6',
        'ranking_points' => 'decimal:2',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stageDetails()
    {
        return ThreeCushionStageResult::query()
            ->with(['registration.tournament', 'registration.tournamentInstance'])
            ->where('player_id', $this->player_id)
            ->where('category_id', $this->category_id)
            ->whereHas('registration.tournament', fn ($query) => $query->whereYear('end_date', $this->season))
            ->get()
            ->sortBy(fn (ThreeCushionStageResult $result) => $result->registration?->tournament?->end_date)
            ->values();
    }
}
