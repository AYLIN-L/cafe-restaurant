<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cycle de vie des réservations.
 *
 * - `active_date` : journée consommée par le client. Elle est renseignée
 *   uniquement pour une réservation en attente ou confirmée, et vidée dès
 *   que la réservation est annulée, refusée ou expirée. L'index unique
 *   (user_id, active_date) est la protection finale en base de la règle
 *   « une réservation par client et par jour ».
 * - `attendance`  : présence vérifiée par l'administrateur, uniquement sur
 *   une réservation confirmée. Seule la valeur « absent » compte pour la
 *   limite d'absences.
 * - `expired_at`  : trace l'expiration automatique d'une demande restée en
 *   attente. Une réservation expirée n'a jamais été confirmée : elle ne
 *   compte donc jamais comme une absence.
 * - `hidden_at`   : masquage. Aucune réservation n'est jamais supprimée,
 *   l'historique et les statistiques restent exacts.
 *
 * La migration est NON destructive : le remplissage de `active_date` est
 * déterministe (la réservation la plus ancienne de chaque journée conserve
 * le jour) et l'index unique n'est créé qu'APRÈS ce remplissage, afin de
 * ne jamais échouer sur les doublons historiques.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->date('active_date')->nullable()->after('reservation_date');
            $table->string('attendance')->default('unknown')
                ->check("attendance in ('unknown','present','absent','excused')")
                ->after('status');
            $table->timestamp('expired_at')->nullable()->after('status');
            $table->timestamp('hidden_at')->nullable()->after('expired_at');
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete()->after('hidden_at');
        });

        // Remplissage déterministe : la plus ancienne réservation de chaque
        // journée (client + date) conserve la journée ; les doublons
        // historiques restent sans date active et n'immobilisent donc pas de
        // jour supplémentaire.
        $rows = DB::table('reservations')
            ->select('id', 'user_id', 'reservation_date')
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('user_id')
            ->orderBy('reservation_date')
            ->orderBy('id')
            ->get();

        $seen = [];
        $updates = [];

        foreach ($rows as $row) {
            $date = Carbon::parse($row->reservation_date)->toDateString();
            $key = $row->user_id.'|'.$date;

            if (isset($seen[$key])) {
                continue; // doublon : la journée est déjà consommée
            }

            $seen[$key] = true;
            // Format aligné sur le cast Eloquent (« date ») afin que la
            // colonne contienne exactement la même valeur que celle écrite
            // par l'application : l'index unique protège alors réellement
            // la règle « une réservation par jour » sur tous les moteurs.
            $updates[] = ['id' => $row->id, 'active_date' => $date.' 00:00:00'];
        }

        foreach (array_chunk($updates, 500) as $chunk) {
            $cases = [];
            $dates = [];

            foreach ($chunk as $update) {
                $cases[] = 'when '.$update['id'].' then ?';
                $dates[] = $update['active_date'];
            }

            $ids = implode(', ', array_map(fn ($update) => (int) $update['id'], $chunk));

            DB::update(
                'update reservations set active_date = case id '.implode(' ', $cases).' else active_date end where id in ('.$ids.')',
                $dates
            );
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->index('hidden_at', 'reservations_hidden_idx');
            $table->index(['user_id', 'attendance'], 'reservations_user_attendance_idx');
        });

        // Protection en base de la règle « une réservation par jour ».
        // Les lignes dont `active_date` est NULL ne se gênent pas entre elles
        // (les valeurs NULL sont distinctes dans un index unique).
        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['user_id', 'active_date'], 'reservations_user_active_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_user_active_date_unique');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_user_attendance_idx');
            $table->dropIndex('reservations_hidden_idx');
            $table->dropConstrainedForeignId('hidden_by');
            $table->dropColumn(['active_date', 'attendance', 'expired_at', 'hidden_at']);
        });
    }
};
