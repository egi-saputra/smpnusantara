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


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nis',
        'nisn',
        'kelas_id',
        'id_siswa',
        'status',
        'sekretaris',
        'bendahara',
        'osis',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'user_id', 'id');
    }

}


<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

// Models
use App\Models\Siswa;
use App\Models\Kelas;

class FormController extends Controller
{
    /**
     * Halaman form data siswa.
     * Guard: jika user sudah punya data siswa, redirect ke dashboard.
     */
    public function create(Request $request)
    {
        // Prevent re-submission jika data sudah ada
        if ($request->user()->siswa) {
            return redirect()->route('siswa.dashboard')
                ->with('info', 'Data kamu sudah tersimpan.');
        }

        return Inertia::render('Siswa/Form/Create', [
            'kelas'    => Kelas::select('id', 'kelas')->orderBy('kelas')->get(),
        ]);
    }

    /**
     * Simpan data siswa.
     */
    public function store(Request $request)
    {
        // Guard: satu user hanya boleh punya satu data siswa
        if ($request->user()->siswa) {
            return redirect()->route('siswa.dashboard')
                ->with('info', 'Data kamu sudah tersimpan sebelumnya.');
        }

        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nis'          => ['nullable', 'string', 'min:7', 'max:20', 'unique:siswa,nis'],
            'nisn'         => ['required', 'digits:10', 'unique:siswa,nisn'],
            'kelas_id'     => ['required', 'exists:kelas,id'],
        ], [
            // Pesan error yang ramah user (Bahasa Indonesia)
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap terlalu panjang (maksimal 255 karakter).',
            'nis.min'               => 'NIS minimal 7 karakter.',
            'nis.unique'            => 'NIS ini sudah terdaftar, hubungi admin jika ada kesalahan.',
            'nisn.required'         => 'NISN wajib diisi.',
            'nisn.digits'           => 'NISN harus tepat 10 digit angka.',
            'nisn.unique'           => 'NISN ini sudah terdaftar, hubungi admin jika ada kesalahan.',
            'kelas_id.required'     => 'Silakan pilih kelas terlebih dahulu.',
            'kelas_id.exists'       => 'Kelas yang dipilih tidak valid.',
        ]);

        try {
            Siswa::create([
                'nama_lengkap' => trim($request->nama_lengkap),
                'nis'          => $request->nis  ? trim($request->nis)  : null,
                'nisn'         => trim($request->nisn),
                'kelas_id'     => $request->kelas_id,
                'id_siswa'     => strtoupper(substr(uniqid(), 0, 7)),
                'status'       => 'Activated',
                'user_id'      => auth()->id(),
            ]);
        } catch (\Exception $e) {
            // Jangan expose detail error ke user — log saja
            \Log::error('FormController@store failed: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }

        return redirect()
            ->route('siswa.dashboard')
            ->with('success', 'Data berhasil disimpan! Selamat datang 🎉');
    }
}


<script setup>
import { useForm, Head, router, usePage } from '@inertiajs/vue3'
import { ArrowLeftOnRectangleIcon, CheckBadgeIcon, CheckCircleIcon, ExclamationTriangleIcon, XMarkIcon } from '@heroicons/vue/24/solid'
import { AcademicCapIcon, IdentificationIcon, UserIcon } from '@heroicons/vue/24/outline'
import { ref, onMounted, computed } from 'vue'

/* ─── Props & Page ───────────────────────────────────────── */
const page = usePage()

const props = defineProps({
    kelas: Array,
})

/* ─── Toast / Alert state ────────────────────────────────── */
// type: 'success' | 'error' | 'info'
const toast = ref({ show: false, type: 'success', message: '' })
let toastTimer = null

const showToast = (type, message) => {
    clearTimeout(toastTimer)
    toast.value = { show: true, type, message }
    toastTimer = setTimeout(() => { toast.value.show = false }, 5000)
}

const dismissToast = () => {
    clearTimeout(toastTimer)
    toast.value.show = false
}

const toastStyle = computed(() => ({
    success: {
        bg: 'bg-emerald-600',
        icon: CheckBadgeIcon,
    },
    error: {
        bg: 'bg-red-600',
        icon: ExclamationTriangleIcon,
    },
    info: {
        bg: 'bg-blue-600',
        icon: CheckCircleIcon,
    },
}[toast.value.type] ?? { bg: 'bg-gray-700', icon: CheckCircleIcon }))

onMounted(() => {
    if (page.props.flash?.success) showToast('success', page.props.flash.success)
    if (page.props.flash?.error) showToast('error', page.props.flash.error)
    if (page.props.flash?.info) showToast('info', page.props.flash.info)
})

/* ─── Form ───────────────────────────────────────────────── */
const form = useForm({
    nama_lengkap: '',
    nis: '',
    nisn: '',
    kelas_id: '',
})

// Validasi client-side tambahan
const localErrors = ref({})

const validateClient = () => {
    const errs = {}
    if (!form.nama_lengkap.trim()) errs.nama_lengkap = 'Nama lengkap wajib diisi.'
    if (form.nis && form.nis.trim().length < 7) errs.nis = 'NIS minimal 7 karakter.'
    if (!form.nisn) errs.nisn = 'NISN wajib diisi.'
    else if (!/^\d{10}$/.test(form.nisn)) errs.nisn = 'NISN harus tepat 10 digit angka.'
    if (!form.kelas_id) errs.kelas_id = 'Silakan pilih kelas.'
    localErrors.value = errs
    return Object.keys(errs).length === 0
}

// Helper: gabung client + server errors
const fieldError = (field) => localErrors.value[field] || form.errors[field]

const submit = () => {
    if (!validateClient()) {
        showToast('error', 'Mohon periksa kembali isian form.')
        return
    }

    form.post(route('siswa.form.store'), {
        preserveScroll: true,
        onError: () => showToast('error', 'Gagal menyimpan data. Periksa isian kamu.'),
    })
}

const logout = () => router.post(route('logout'))

/* ─── Step indicator (visual only) ──────────────────────── */
const steps = ['Identitas', 'Akademik']
const activeStep = computed(() => {
    if (form.kelas_id || form.kejuruan_id) return 1
    return 0
})
</script>

<template>

    <Head title="Form Data Siswa" />

    <!-- ── Toast ─────────────────────────────────────────── -->
    <Transition enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4 md:translate-y-0 md:translate-x-4"
        enter-to-class="opacity-100 translate-y-0 md:translate-x-0" leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="toast.show" :class="[toastStyle.bg,
            'fixed z-50 flex items-center gap-3 px-5 py-3.5 shadow-2xl text-white',
            'bottom-4 left-4 right-4 rounded-xl',
            'md:top-5 md:right-5 md:bottom-auto md:left-auto md:rounded-xl md:w-auto md:max-w-sm']">
            <component :is="toastStyle.icon" class="w-5 h-5 shrink-0" />
            <span class="text-sm font-medium flex-1 leading-snug">{{ toast.message }}</span>
            <button @click="dismissToast" class="ml-1 opacity-70 hover:opacity-100 transition">
                <XMarkIcon class="w-4 h-4" />
            </button>
        </div>
    </Transition>

    <!-- ── Page ──────────────────────────────────────────── -->
    <div class="min-h-screen flex items-center justify-center px-4 py-10 sm:px-6
                bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100">

        <!-- Decorative blobs -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10">
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full
                        bg-blue-200/40 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-80 h-80 rounded-full
                        bg-indigo-200/40 blur-3xl"></div>
        </div>

        <div class="w-full max-w-2xl">

            <!-- ── Card ──────────────────────────────────── -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl
                        border border-white/60 overflow-hidden">

                <!-- Card top accent bar -->
                <div class="h-1.5 w-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>

                <div class="px-8 pt-8 pb-10 sm:px-10">

                    <!-- Header -->
                    <div class="flex items-start justify-between mb-8">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                                        bg-blue-50 border border-blue-100 text-blue-600
                                        text-xs font-semibold tracking-wide mb-3">
                                <AcademicCapIcon class="w-3.5 h-3.5" />
                                PENDAFTARAN SISWA
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                                Lengkapi Data Diri
                            </h1>
                            <p class="mt-1.5 text-gray-500 text-sm">
                                Isi dengan data yang sesuai ijazah terakhir kamu.
                            </p>
                        </div>

                        <!-- Logout -->
                        <button type="button" @click="logout" class="flex items-center gap-1.5 text-xs font-medium text-gray-400
                                   hover:text-red-500 transition-colors mt-1 shrink-0">
                            <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </div>

                    <!-- Step pills -->
                    <div class="flex items-center gap-2 mb-8">
                        <div v-for="(step, i) in steps" :key="i" class="flex items-center gap-2">
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                                :class="i <= activeStep
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-200'
                                    : 'bg-gray-100 text-gray-400'">
                                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                                    :class="i <= activeStep ? 'bg-white/30' : 'bg-gray-200'">{{ i + 1 }}</span>
                                {{ step }}
                            </div>
                            <div v-if="i < steps.length - 1" class="w-6 h-px"
                                :class="i < activeStep ? 'bg-blue-400' : 'bg-gray-200'"></div>
                        </div>
                    </div>

                    <!-- ── Form ───────────────────────────── -->
                    <form @submit.prevent="submit" class="space-y-5" novalidate>

                        <!-- Section: Identitas -->
                        <div class="flex items-center gap-2 mb-1">
                            <UserIcon class="w-4 h-4 text-blue-500" />
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Data Identitas
                            </span>
                        </div>

                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Nama Lengkap
                                <span class="text-red-500 ml-0.5">*</span>
                                <span class="text-gray-400 font-normal ml-1 text-xs">(sesuai ijazah)</span>
                            </label>
                            <input v-model="form.nama_lengkap" type="text" placeholder="Contoh: Budi Santoso"
                                autocomplete="name" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                       bg-white text-gray-900 placeholder-gray-400
                                       transition focus:outline-none focus:ring-2 capitalize" :class="fieldError('nama_lengkap')
                                        ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                        : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                            <p v-if="fieldError('nama_lengkap')"
                                class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                {{ fieldError('nama_lengkap') }}
                            </p>
                        </div>

                        <!-- NIS + NISN (grid) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <!-- NIS -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    NIS
                                    <span class="text-gray-400 font-normal ml-1 text-xs">(opsional)</span>
                                </label>
                                <input v-model="form.nis" type="text" placeholder="Min. 7 digit angka" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('nis')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <p v-if="fieldError('nis')" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                    {{ fieldError('nis') }}
                                </p>
                            </div>

                            <!-- NISN -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    NISN
                                    <span class="text-red-500 ml-0.5">*</span>
                                    <span class="text-gray-400 font-normal ml-1 text-xs">(10 digit)</span>
                                </label>
                                <input v-model="form.nisn" type="text" placeholder="0000000000" maxlength="10"
                                    inputmode="numeric" @input="form.nisn = form.nisn.replace(/\D/g, '')" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2 tracking-widest" :class="fieldError('nisn')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <!-- Progress digit indicator -->
                                <div class="flex items-center justify-between mt-1.5">
                                    <p v-if="fieldError('nisn')" class="text-xs text-red-500 flex items-center gap-1">
                                        <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                        {{ fieldError('nisn') }}
                                    </p>
                                    <p v-else class="text-xs text-gray-400"></p>
                                    <span class="text-xs tabular-nums shrink-0" :class="form.nisn.length === 10
                                        ? 'text-emerald-500 font-semibold'
                                        : 'text-gray-400'">
                                        {{ form.nisn.length }}/10
                                    </span>
                                </div>
                            </div>

                        </div>

                        <!-- Section: Akademik -->
                        <div class="flex items-center gap-2 pt-2 mb-1">
                            <IdentificationIcon class="w-4 h-4 text-blue-500" />
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Data Akademik
                            </span>
                        </div>

                        <!-- Kelas + Kejuruan (grid) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <!-- Kelas -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    Unit Kelas
                                    <span class="text-red-500 ml-0.5">*</span>
                                </label>
                                <select v-model="form.kelas_id" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900
                                           transition focus:outline-none focus:ring-2 appearance-none" :class="fieldError('kelas_id')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'">
                                    <option value="">-- Pilih Kelas --</option>
                                    <option v-for="k in kelas" :key="k.id" :value="k.id">
                                        {{ k.kelas }}
                                    </option>
                                </select>
                                <p v-if="fieldError('kelas_id')"
                                    class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                    {{ fieldError('kelas_id') }}
                                </p>
                            </div>
                        </div>

                        <!-- ── Submit ──────────────────────── -->
                        <div class="pt-4">
                            <button type="submit" :disabled="form.processing" class="w-full flex items-center justify-center gap-2.5
                                       px-6 py-3 rounded-xl text-sm font-bold text-white
                                       bg-gradient-to-r from-blue-600 to-indigo-600
                                       hover:from-blue-700 hover:to-indigo-700
                                       active:scale-[0.98]
                                       disabled:opacity-60 disabled:cursor-not-allowed
                                       shadow-lg shadow-blue-200
                                       transition-all duration-200">
                                <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <CheckBadgeIcon v-else class="w-4 h-4" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                            </button>

                            <!-- Logout mobile -->
                            <button type="button" @click="logout" class="w-full mt-3 flex items-center justify-center gap-2
                                       px-6 py-2.5 rounded-xl text-sm font-medium
                                       text-gray-500 hover:text-red-500
                                       border border-gray-200 hover:border-red-200
                                       transition-colors sm:hidden">
                                <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                                Keluar dari Akun
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Footer note -->
            <p class="text-center text-xs text-gray-400 mt-5">
                Data yang kamu isi akan digunakan untuk keperluan administrasi sekolah.
            </p>

        </div>
    </div>
</template>

<style scoped>
/* Override default select arrow for custom styling */
select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.2em;
    padding-right: 2.5rem;
}
</style>