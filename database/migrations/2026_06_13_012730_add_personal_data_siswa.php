<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            // Tambahkan setelah kolom 'nama_lengkap'
            $table->string('tempat_lahir', 100)->nullable()->after('nama_lengkap');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('tanggal_lahir');
            $table->enum('agama', [
                'Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'
            ])->nullable()->after('jenis_kelamin');
            $table->string('no_hp', 15)->nullable()->after('agama');  // nomor siswa / ortu
            $table->string('no_hp_ortu', 15)->nullable()->after('no_hp');
            $table->text('alamat')->nullable()->after('no_hp_ortu');
            $table->string('kelurahan', 100)->nullable()->after('alamat');
            $table->string('kecamatan', 100)->nullable()->after('kelurahan');
            $table->string('kota', 100)->nullable()->after('kecamatan');
            $table->string('kode_pos', 10)->nullable()->after('kota');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn([
                'tempat_lahir',
                'tanggal_lahir',
                'jenis_kelamin',
                'agama',
                'no_hp',
                'no_hp_ortu',
                'alamat',
                'kelurahan',
                'kecamatan',
                'kota',
                'kode_pos',
            ]);
        });
    }
};