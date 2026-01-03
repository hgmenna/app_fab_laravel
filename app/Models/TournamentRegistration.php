<class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'tournament_slot_id',
        'player_id',
        'status',
        'price',
        'payment_status',
        'checked_in',
        'source',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'checked_in' => 'boolean',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function slot()
    {
        return $this->belongsTo(TournamentSlot::class, 'tournament_slot_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    protected static function booted()
    {
        static::creating(function ($registration) {

            $player = $registration->player;
            $tournament = $registration->tournament;

            if ($player && $tournament) {

                // Obtiene el precio según categoría del jugador
                $price = $tournament->categoryPrices()
                    ->where('category_id', $player->category_id)
                    ->value('price');

                $registration->price = $price ?? 0;
            }
        });
    }
}
p
