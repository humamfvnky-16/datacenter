<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan users punya is_aktif terlebih dahulu
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'is_aktif')) {
                $t->boolean('is_aktif')->default(true);
            }
        });

        // Tambah kolom proteksi ke users, guru, siswa
        foreach (['users', 'guru', 'siswa'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'account_status')) {
                    $t->string('account_status', 20)->default('active');
                }
                if (! Schema::hasColumn($table, 'last_seen_at')) {
                    $t->timestamp('last_seen_at')->nullable();
                }
                if (! Schema::hasColumn($table, 'failed_login_count')) {
                    $t->unsignedSmallInteger('failed_login_count')->default(0);
                }
                if (! Schema::hasColumn($table, 'locked_until')) {
                    $t->timestamp('locked_until')->nullable();
                }
                if (! Schema::hasColumn($table, 'otp_enabled')) {
                    $t->boolean('otp_enabled')->default(false);
                }
                if (! Schema::hasColumn($table, 'otp_method')) {
                    $t->string('otp_method', 20)->default('email')->nullable();
                }
            });
        }

        // Kode OTP (polymorphic)
        Schema::create('otp_codes', function (Blueprint $t) {
            $t->id();
            $t->string('authable_type');
            $t->unsignedBigInteger('authable_id');
            $t->string('code', 8);
            $t->string('purpose', 30)->default('login');
            $t->string('channel', 20)->default('email');
            $t->string('destination')->nullable();
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamps();
            $t->index(['authable_type', 'authable_id']);
        });

        // RBAC
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name', 50)->unique();
            $t->string('label', 100);
            $t->boolean('is_system')->default(false);
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('permission', 100)->unique();
            $t->string('label', 150)->nullable();
            $t->string('group', 50)->nullable();
            $t->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'role_id')) {
                $t->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'role_id')) $t->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('otp_codes');

        foreach (['users', 'guru', 'siswa'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['account_status', 'last_seen_at', 'failed_login_count', 'locked_until', 'otp_enabled', 'otp_method']);
            });
        }
    }
};
