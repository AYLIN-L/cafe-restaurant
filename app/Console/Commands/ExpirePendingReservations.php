<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

/**
 * Expire les demandes de réservation restées en attente.
 *
 * Une demande expirée :
 * - est conservée (jamais de suppression automatique) ;
 * - libère la journée du client (elle peut donc réserver à nouveau) ;
 * - n'a jamais été confirmée : elle ne compte donc JAMAIS comme une absence
 *   et ne peut pas être marquée « absent » par l'administrateur.
 *
 * Le délai est configurable via `reservations.pending_expiration_hours`
 * (24 heures par défaut).
 */
class ExpirePendingReservations extends Command
{
    protected $signature = 'reservations:expire-pending';

    protected $description = 'Expire les demandes de réservation restées en attente au-delà du délai configuré';

    public function handle(): int
    {
        $hours = (int) config('reservations.pending_expiration_hours', 24);
        $limit = now()->subHours($hours);

        $expired = 0;

        Reservation::query()
            ->where('status', Reservation::STATUS_PENDING)
            ->whereNull('expired_at')
            ->where('created_at', '<=', $limit)
            ->chunkById(200, function ($reservations) use (&$expired) {
                foreach ($reservations as $reservation) {
                    $reservation->forceFill([
                        'status' => Reservation::STATUS_CANCELLED,
                        'expired_at' => now(),
                        // Aucune table n'était attribuée à une demande en
                        // attente, mais on garantit la cohérence.
                        'table_id' => null,
                        // La journée est libérée : le client peut réserver
                        // à nouveau pour cette date.
                        'active_date' => null,
                    ])->save();

                    $expired++;
                }
            });

        $this->info($expired.' demande(s) en attente expirée(s) après '.$hours.' h.');

        return self::SUCCESS;
    }
}
