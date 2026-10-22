<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blocage des réservations pour les clients en règle.
 *
 * - `booking_blocked_at`     : blocage effectif. Il est posé automatiquement
 *   lorsque le client atteint le nombre d'absences vérisées autorisé, et
 *   peut aussi l'être manuellement par un administrateur.
 * - `booking_block_override` : le client est explicitement exempté. Un
 *   déblocage administrateur pose ce drapeau afin que le seuil automatique
 *   ne le rebloque pas immédiatement.
 *
 * La migration est non destructive : les deux colonnes sont ajoutées avec
 * une valeur par défaut neutre (aucun client n'est bloqué au moment de la
 * migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('booking_blocked_at')->nullable()->after('remember_token');
            $table->boolean('booking_block_override')->default(false)->after('booking_blocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['booking_blocked_at', 'booking_block_override']);
        });
    }
};
