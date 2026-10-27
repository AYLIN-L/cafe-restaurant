<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permet les messages envoyés par un visiteur non connecté tout en
     * conservant l'association au compte pour les utilisateurs authentifiés.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        // Les messages invités n'ont pas de compte associé : ils ne peuvent
        // pas survivre au retour à une colonne user_id obligatoire.
        DB::table('contact_messages')->whereNull('user_id')->delete();

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['name', 'email']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
