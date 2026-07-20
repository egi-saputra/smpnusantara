<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Aturan jendela waktu pengisian jurnal mengajar guru.
 * Dipakai bareng oleh JournalController (validasi) dan
 * HandleInertiaRequests (status badge di dashboard),
 * supaya aturan jamnya cuma didefinisikan di satu tempat.
 */
class JournalWindow
{
    /** Timezone lokal sekolah — jangan andalkan config('app.timezone') karena server bisa di-set UTC */
    public const TIMEZONE = 'Asia/Jakarta';

    /** Durasi satu sesi jurnal (menit) */
    public const DURASI_SESI_MENIT = 90;

    /** Jam mulai jendela pengisian */
    public const BATAS_JAM_BUKA = '13:00';

    /** Jam tutup jendela pengisian */
    public const BATAS_JAM_TUTUP = '18:00';

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public static function isOpen(?Carbon $now = null): bool
    {
        $now = $now ?? self::now();
        $jam = $now->format('H:i');

        return $jam >= self::BATAS_JAM_BUKA && $jam < self::BATAS_JAM_TUTUP;
    }

    /**
     * 'before'  = belum jam buka
     * 'open'    = sedang dalam jendela pengisian
     * 'after'   = sudah lewat jam tutup
     */
    public static function phase(?Carbon $now = null): string
    {
        $now = $now ?? self::now();
        $jam = $now->format('H:i');

        if ($jam < self::BATAS_JAM_BUKA) {
            return 'before';
        }

        if ($jam >= self::BATAS_JAM_TUTUP) {
            return 'after';
        }

        return 'open';
    }

    public static function pesanDiLuarJendela(?Carbon $now = null): string
    {
        $now = $now ?? self::now();

        if (self::phase($now) === 'before') {
            return 'Pengisian jurnal belum dibuka. Jurnal baru bisa diisi mulai pukul ' . self::BATAS_JAM_BUKA . '.';
        }

        return 'Pengisian jurnal sudah ditutup. Batas waktu pengisian adalah pukul ' . self::BATAS_JAM_TUTUP . '.';
    }

    /**
     * Payload ringkas untuk dikirim sebagai shared prop Inertia ke front-end.
     */
    public static function toArray(?Carbon $now = null): array
    {
        $now = $now ?? self::now();

        return [
            'isOpen'   => self::isOpen($now),
            'phase'    => self::phase($now),
            'opensAt'  => self::BATAS_JAM_BUKA,
            'closesAt' => self::BATAS_JAM_TUTUP,
        ];
    }
}