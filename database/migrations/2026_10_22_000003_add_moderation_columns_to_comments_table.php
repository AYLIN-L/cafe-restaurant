<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Traçabilité de la modération des avis.
 *
 * - `moderated_at`    : date de la dernière action de modération.
 * - `moderated_by`    : administrateur ayant modéré l'avis.
 * - `moderation_note` : motif du rejet (facultatif, 500 caractères max).
 *
 * `is_approved` reste la seule source de vérité de la visibilité publique :
 * un avis rejeté n'est jamais affiché publiquement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('moderated_at')->nullable()->after('is_approved');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete()->after('moderated_at');
            $table->string('moderation_note')->nullable()->after('moderated_by');
        });

        // Les avis déjà approuvés sont considérés comme modérés à la date de
        // leur dernière mise à jour ; les autres restent « en attente ».
        DB::table('comments')
            ->where('is_approved', true)
            ->whereNull('moderated_at')
            ->update(['moderated_at' => DB::raw('updated_at')]);

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['is_approved', 'created_at'], 'comments_moderation_idx');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_moderation_idx');
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['moderated_at', 'moderation_note']);
        });
    }
};
