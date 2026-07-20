<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lokasi Target Pengisian Jurnal
    |--------------------------------------------------------------------------
    |
    | Satu titik tetap (lokasi sekolah). Guru hanya bisa mengisi jurnal
    | mengajar bila posisi GPS-nya berada dalam radius_meter dari titik ini.
    |
    | Nilai diambil dari .env supaya bisa disesuaikan per deployment/tenant
    | tanpa perlu ubah kode & deploy ulang. Kalau nanti butuh multi-lokasi
    | atau diedit lewat panel admin, ini tinggal dipindah ke tabel DB —
    | struktur JournalLocation::target() sudah dibuat supaya gampang diganti
    | sumber datanya tanpa mengubah pemanggil di controller.
    |
    */
    'location' => [
        'latitude' => (float) env('JOURNAL_LOCATION_LAT', -6.445577),
        'longitude' => (float) env('JOURNAL_LOCATION_LNG', 106.784887),

        // Radius toleransi utama (meter) — jarak guru ke titik target
        'radius_meter' => (int) env('JOURNAL_LOCATION_RADIUS', 100),

        // Buffer tambahan di luar radius_meter untuk mengakomodasi drift GPS
        // normal (bukan celah keamanan, tapi standar praktik geofencing
        // supaya guru yang berdiri persis di pinggir radius tidak ditolak
        // gara-gara GPS meleset beberapa meter).
        'toleransi_meter' => (int) env('JOURNAL_LOCATION_TOLERANSI', 15),

        // Accuracy (meter) dari device dianggap tidak bisa dipercaya kalau
        // melebihi ini. Ini filter paling efektif menyaring GPS spoofing
        // app murahan, yang seringkali melaporkan accuracy tidak realistis.
        'max_accuracy_meter' => (int) env('JOURNAL_LOCATION_MAX_ACCURACY', 50),

        // Kecepatan (km/jam) maksimum yang masih masuk akal antara dua
        // titik submit jurnal berurutan milik guru yang sama. Dipakai
        // untuk mendeteksi lompatan lokasi yang mustahil (indikasi spoof).
        'kecepatan_maksimum_kmh' => (int) env('JOURNAL_LOCATION_MAX_SPEED_KMH', 120),
    ],

];