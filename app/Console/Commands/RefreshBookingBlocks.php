<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Applique le blocage automatique des clients en règle.
 *
 * Un client est bloqué dès qu'il atteint le nombre d'absences VÉRIFIÉES
 * autorisé (`reservations.absence_threshold`, 5 par défaut).
 *
 * - seules les réservations confirmées et marquées « absent » comptent ;
 * - une demande expirée ou annulée ne compte jamais ;
 * - un client explicitement débloqué par l'administrateur
 *   (`booking_block_override`) n'est jamais rebloqué automatiquement.
 *
 * Le déblocage reste une décision humaine : cette commande ne libère
 * jamais un client, elle ne fait que poser le blocage.
 */
class RefreshBookingBlocks extends Command
{
    protected $signature = 'reservations:refresh-blocks';

    protected $description = 'Bloque les clients qui atteignent le nombre d\'absences vérifiées autorisé';

    public function handle(): int
    {
        $threshold = (int) config('reservations.absence_threshold', 5);

        $blocked = 0;

        User::query()
            ->where('role', 'client')
            ->chunkById(200, function ($users) use (&$blocked) {
                foreach ($users as $user) {
                    if ($user->applyAutomaticBookingBlock()) {
                        $blocked++;
                    }
                }
            });

        $this->info($blocked.' client(s) bloqué(s) au titre des absences vérifiées (seuil : '.$threshold.').');

        return self::SUCCESS;
    }
}
