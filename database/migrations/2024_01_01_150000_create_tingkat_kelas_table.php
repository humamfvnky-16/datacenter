<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tingkat_kelas', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 10)->unique();                  // "7", "10", "X", dst
            $t->string('nama', 50);                            // "Kelas 7", "Kelas X / 10", dst
            $t->unsignedTinyInteger('nomor');                  // 7..12
            $t->string('jenjang', 10)->nullable();             // SD/SMP/SMA/SMK
            $t->unsignedTinyInteger('urutan')->default(0);
            $t->boolean('is_aktif')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tingkat_kelas');
    }
};
