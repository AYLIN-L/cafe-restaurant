<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Donne aux messages de contact un état de traitement côté
     * administration : « unread » (non lu), « read » (lu, non traité),
     * « processed » (traité). Les messages historiques, jamais ouverts,
     * deviennent « unread » afin d'apparaître dans la file à traiter.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('status')->default('unread')->after('message')->index();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
