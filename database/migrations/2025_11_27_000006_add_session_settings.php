<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Créer la table settings si elle n'existe pas
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string'); // string, integer, boolean, json
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Insérer les paramètres de session par défaut
        $settings = [
            [
                'key' => 'session_max_duration_minutes',
                'value' => '120',
                'type' => 'integer',
                'description' => 'Durée maximale d\'une session de visioconférence en minutes',
            ],
            [
                'key' => 'session_max_participants',
                'value' => '50',
                'type' => 'integer',
                'description' => 'Nombre maximum de participants par session',
            ],
            [
                'key' => 'session_recording_enabled_default',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enregistrement automatique activé par défaut',
            ],
            [
                'key' => 'session_empty_timeout_minutes',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Temps d\'attente avant fermeture automatique d\'une session vide (en minutes)',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les paramètres de session
        DB::table('settings')->whereIn('key', [
            'session_max_duration_minutes',
            'session_max_participants',
            'session_recording_enabled_default',
            'session_empty_timeout_minutes',
        ])->delete();

        // Note: On ne supprime pas la table settings car elle peut contenir d'autres paramètres
    }
};
