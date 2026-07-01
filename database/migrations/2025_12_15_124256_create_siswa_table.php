<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Identitas siswa
            $table->string('nama_lengkap');

            // NIS & NISN (HARUS 10 DIGIT)
            $table->string('nis', 10)->nullable()->unique();
            $table->string('nisn', 10)->nullable()->unique();

            // Relasi
            $table->string('kelas_id');

            // ID internal siswa
            $table->string('id_siswa', 7)->unique();

            // Status
            $table->enum('status', ['Activated', 'Deactivated'])->default('Activated');
            $table->enum('sekretaris', ['yes', 'no'])->default('no');
            $table->enum('bendahara', ['yes', 'no'])->default('no');
            $table->enum('osis', ['yes', 'no'])->default('no');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('siswa');
    }
};
