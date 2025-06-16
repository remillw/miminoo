<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Vérifier et ajouter les champs pour les connexions sociales
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'apple_id')) {
                $table->string('apple_id')->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable()->after('apple_id'); // 'google', 'apple', 'local'
            }
            if (!Schema::hasColumn('users', 'is_social_account')) {
                $table->boolean('is_social_account')->default(false)->after('provider');
            }
            
            // Champs pour les photos de profil
            if (!Schema::hasColumn('users', 'profile_photos')) {
                $table->json('profile_photos')->nullable()->after('avatar'); // Pour les babysitters
            }
            
            // Champ pour indiquer si les infos viennent d'un provider social
            if (!Schema::hasColumn('users', 'social_data_locked')) {
                $table->boolean('social_data_locked')->default(false)->after('profile_photos');
            }
            
            // Champs de notifications s'ils n'existent pas
            if (!Schema::hasColumn('users', 'email_notifications')) {
                $table->boolean('email_notifications')->default(true)->after('social_data_locked');
            }
            if (!Schema::hasColumn('users', 'push_notifications')) {
                $table->boolean('push_notifications')->default(true)->after('email_notifications');
            }
            if (!Schema::hasColumn('users', 'sms_notifications')) {
                $table->boolean('sms_notifications')->default(false)->after('push_notifications');
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language')->default('fr')->after('sms_notifications');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToCheck = [
                'apple_id', 
                'provider',
                'is_social_account',
                'profile_photos',
                'social_data_locked',
                'email_notifications',
                'push_notifications',
                'sms_notifications',
                'language'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
