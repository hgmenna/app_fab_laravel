<?php

namespace App\Console\Commands;

use App\Services\AnnualMembershipService;
use Illuminate\Console\Command;

class ExpireAnnualMemberships extends Command
{
    protected $signature = 'memberships:expire-overdue';

    protected $description = 'Inhabilita jugadores que no renovaron su membresía antes del cierre de su disciplina';

    public function handle(AnnualMembershipService $service): int
    {
        $count = $service->expireOverdue();
        $this->info("Jugadores inhabilitados: {$count}");

        return self::SUCCESS;
    }
}
