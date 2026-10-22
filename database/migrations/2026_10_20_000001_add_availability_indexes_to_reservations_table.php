<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index de performance uniquement : aucune donnée n'est modifiée, supprimée
 * ou réinitialisée. Le moteur de disponibilité interroge ces tables à chaque
 * vérification de créneau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['table_id', 'status', 'reservation_date'], 'reservations_availability_idx');
        });

        Schema::table('walk_ins', function (Blueprint $table) {
            $table->index(['table_id', 'released_at'], 'walk_ins_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_availability_idx');
        });

        Schema::table('walk_ins', function (Blueprint $table) {
            $table->dropIndex('walk_ins_active_idx');
        });
    }
};
