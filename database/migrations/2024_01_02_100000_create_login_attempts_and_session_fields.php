<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Login attempt log untuk monitoring (login admin panel Data Center)
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('guard')->default('admin');
            $table->boolean('success')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('network', 100)->nullable();
            $table->unsignedSmallInteger('attempt_no')->default(1);
            $table->timestamps();
        });

        // Current session ID untuk SSO (single device login)
        foreach (['users', 'guru', 'siswa'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'current_session_id')) {
                    $t->string('current_session_id', 100)->nullable();
                }
                if (! Schema::hasColumn($table, 'current_device')) {
                    $t->string('current_device', 100)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        foreach (['users', 'guru', 'siswa'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['current_session_id', 'current_device']);
            });
        }
    }
};
