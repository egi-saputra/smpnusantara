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
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();

            // Siswa/pengirim tugas
            $table->unsignedBigInteger('user_id');

            // Guru penerima tugas
            $table->unsignedBigInteger('guru_id');

            // Mata pelajaran
            $table->unsignedBigInteger('mapel_id');

            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_path')->nullable();

            // Status tugas
            $table->boolean('is_read')->default(false);
            $table->boolean('is_updated')->default(false);
            $table->tinyInteger('revision_count')->default(0);

            $table->timestamps();

            // Foreign Key
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('guru_id')
                  ->references('id')
                  ->on('guru')
                  ->onDelete('cascade');

            $table->foreign('mapel_id')
                  ->references('id')
                  ->on('mapel')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};