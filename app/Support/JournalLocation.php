<?php

namespace App\Support;

/**
 * Aturan validasi lokasi pengisian jurnal mengajar guru.
 *
 * Dipakai oleh JournalController untuk memvalidasi ULANG di server posisi
 * GPS yang dikirim client — client tidak pernah dipercaya untuk menentukan
 * valid/tidaknya sendiri, hanya mengirim data mentah (lat, lng, accuracy).
 *
 * Catatan soal keamanan lokasi (baca sebelum mengubah threshold):
 * Tidak ada cara mendeteksi fake-GPS 100% akurat dari browser web. Validasi
 * di sini bersifat DETEREN berlapis (accuracy filter + radius + speed
 * check + audit trail), bukan jaminan mutlak. Untuk kepastian lebih tinggi
 * dibutuhkan native app dengan mock-location detection (mis. Play
 * Integrity API), di luar cakupan web app ini.
 */
class JournalLocation
{
    /**
     * Titik lokasi target (lokasi sekolah) beserta radius & threshold.
     * Diambil dari config supaya bisa diubah lewat .env tanpa deploy ulang.
     */
    public static function target(): array
    {
        return [
            'latitude'     => (float) config('journal.location.latitude'),
            'longitude'    => (float) config('journal.location.longitude'),
            'radius_meter' => (int) config('journal.location.radius_meter'),
        ];
    }

    public static function toleransiMeter(): float
    {
        return (float) config('journal.location.toleransi_meter', 15);
    }

    public static function maxAkurasiMeter(): float
    {
        return (float) config('journal.location.max_accuracy_meter', 50);
    }

    public static function maxKecepatanKmh(): float
    {
        return (float) config('journal.location.kecepatan_maksimum_kmh', 120);
    }

    /**
     * Hitung jarak antara dua koordinat pakai formula Haversine (meter).
     * Portable di semua versi MySQL, tidak butuh extension spasial.
     */
    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radiusBumi = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radiusBumi * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Validasi lokasi yang dikirim guru terhadap titik target.
     * Selalu dipanggil di server, tidak pernah percaya hasil client.
     *
     * @return array{jarak_meter: float, akurasi_ok: bool, dalam_radius: bool, valid: bool}
     */
    public static function validate(float $lat, float $lng, float $akurasiMeter): array
    {
        $target = self::target();

        $jarak = self::jarakMeter($lat, $lng, $target['latitude'], $target['longitude']);

        // Accuracy device terlalu buruk = tidak bisa dipercaya untuk radius
        // sekecil ini. Ini filter yang paling efektif menyaring aplikasi
        // fake-GPS gratisan, yang seringkali melaporkan accuracy tidak wajar.
        $akurasiOk = $akurasiMeter <= self::maxAkurasiMeter();

        $dalamRadius = $jarak <= ($target['radius_meter'] + self::toleransiMeter());

        return [
            'jarak_meter'  => round($jarak, 2),
            'akurasi_ok'   => $akurasiOk,
            'dalam_radius' => $dalamRadius,
            'valid'        => $akurasiOk && $dalamRadius,
        ];
    }

    /**
     * Cek kewajaran kecepatan pindah antara dua titik & dua waktu submit.
     * Dipakai untuk mendeteksi lompatan lokasi yang mustahil (indikasi
     * device lain / GPS spoofing) dibanding entri jurnal sebelumnya milik
     * guru yang sama.
     */
    public static function kecepatanWajar(
        float $lat1,
        float $lng1,
        \Carbon\Carbon $waktu1,
        float $lat2,
        float $lng2,
        \Carbon\Carbon $waktu2
    ): bool {
        $jarakMeter = self::jarakMeter($lat1, $lng1, $lat2, $lng2);
        $selisihJam = max(abs($waktu2->diffInSeconds($waktu1)) / 3600, 0.001); // hindari div/0

        $kecepatanKmh = ($jarakMeter / 1000) / $selisihJam;

        return $kecepatanKmh <= self::maxKecepatanKmh();
    }

    /**
     * Payload ringkas untuk dikirim sebagai prop Inertia ke front-end,
     * supaya front-end bisa kasih pratinjau jarak sebelum submit (server
     * tetap yang menentukan valid/tidak yang sebenarnya).
     */
    public static function toArray(): array
    {
        $target = self::target();

        return [
            'latitude'           => $target['latitude'],
            'longitude'          => $target['longitude'],
            'radiusMeter'        => $target['radius_meter'],
            'toleransiMeter'     => self::toleransiMeter(),
            'maxAkurasiMeter'    => self::maxAkurasiMeter(),
        ];
    }
}