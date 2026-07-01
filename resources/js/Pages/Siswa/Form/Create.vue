<script setup>
import { useForm, Head, router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeftOnRectangleIcon,
    CheckBadgeIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/solid'
import {
    AcademicCapIcon,
    IdentificationIcon,
    UserIcon,
    PhoneIcon,
    MapPinIcon,
} from '@heroicons/vue/24/outline'
import { ref, onMounted, computed } from 'vue'

/* ─── Props & Page ──────────────────────────────────────── */
const page = usePage()
const props = defineProps({ kelas: Array })

/* ─── Toast ─────────────────────────────────────────────── */
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
    success: { bg: 'bg-emerald-600', icon: CheckBadgeIcon },
    error: { bg: 'bg-red-600', icon: ExclamationTriangleIcon },
    info: { bg: 'bg-blue-600', icon: CheckCircleIcon },
}[toast.value.type] ?? { bg: 'bg-gray-700', icon: CheckCircleIcon }))

onMounted(() => {
    if (page.props.flash?.success) showToast('success', page.props.flash.success)
    if (page.props.flash?.error) showToast('error', page.props.flash.error)
    if (page.props.flash?.info) showToast('info', page.props.flash.info)
})

/* ─── Form ───────────────────────────────────────────────── */
const form = useForm({
    // Identitas
    nama_lengkap: '',
    nis: '',
    nisn: '',
    kelas_id: '',
    // Pribadi
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: '',
    agama: '',
    no_hp: '',
    no_hp_ortu: '',
    // Alamat
    alamat: '',
    kelurahan: '',
    kecamatan: '',
    kota: '',
    kode_pos: '',
})

/* ─── Capitalize helper ──────────────────────────────────── */
/**
 * Konversi string ke Title Case.
 * Dipanggil saat `input` event sehingga nilai form langsung berubah.
 * Ini memperbaiki masalah di HP: CSS `text-transform: capitalize` hanya
 * mengubah tampilan, bukan nilai sebenarnya yang dikirim ke server.
 */
const toTitleCase = (str) =>
    str.replace(/\w\S*/g, (word) =>
        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
    )

/**
 * Handler untuk field teks yang harus Title Case.
 * Ubah value langsung di model form supaya yang tersimpan juga sudah benar.
 */
const handleTitleCase = (field, event) => {
    const pos = event.target.selectionStart  // simpan posisi kursor
    const value = toTitleCase(event.target.value)
    form[field] = value
    // Kembalikan posisi kursor supaya tidak lompat ke akhir saat mengetik
    event.target.value = value
    event.target.setSelectionRange(pos, pos)
}

/* ─── Validasi client-side ───────────────────────────────── */
const localErrors = ref({})

const validateClient = () => {
    const errs = {}
    if (!form.nama_lengkap.trim())
        errs.nama_lengkap = 'Nama lengkap wajib diisi.'
    if (form.nis && form.nis.trim().length < 7)
        errs.nis = 'NIS minimal 7 karakter.'
    if (!form.nisn)
        errs.nisn = 'NISN wajib diisi.'
    else if (!/^\d{10}$/.test(form.nisn))
        errs.nisn = 'NISN harus tepat 10 digit angka.'
    if (!form.kelas_id)
        errs.kelas_id = 'Silakan pilih kelas.'
    if (form.tanggal_lahir && new Date(form.tanggal_lahir) >= new Date())
        errs.tanggal_lahir = 'Tanggal lahir tidak boleh hari ini atau masa depan.'
    if (form.kode_pos && !/^\d{5}$/.test(form.kode_pos))
        errs.kode_pos = 'Kode pos harus 5 digit angka.'
    if (form.no_hp && !/^[0-9+\-\s]{8,15}$/.test(form.no_hp))
        errs.no_hp = 'Format nomor HP tidak valid.'
    if (form.no_hp_ortu && !/^[0-9+\-\s]{8,15}$/.test(form.no_hp_ortu))
        errs.no_hp_ortu = 'Format nomor HP orang tua tidak valid.'

    localErrors.value = errs
    return Object.keys(errs).length === 0
}

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

/* ─── Step indicator ─────────────────────────────────────── */
const steps = ['Identitas', 'Pribadi', 'Alamat']
const activeStep = computed(() => {
    if (form.alamat || form.kota) return 2
    if (form.tempat_lahir || form.jenis_kelamin || form.agama) return 1
    return 0
})

/* ─── Pilihan dropdown ───────────────────────────────────── */
const agamaOptions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']
</script>

<template>

    <Head title="Form Data Siswa" />

    <!-- Toast -->
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

    <!-- Page -->
    <div class="min-h-screen flex items-center justify-center px-4 py-10 sm:px-6
                bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100">

        <!-- Decorative blobs -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10">
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-blue-200/40 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-80 h-80 rounded-full bg-indigo-200/40 blur-3xl"></div>
        </div>

        <div class="w-full max-w-2xl">

            <!-- Card -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/60 overflow-hidden">

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

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-5" novalidate>

                        <!-- ══ SECTION: Identitas ══ -->
                        <div class="flex items-center gap-2 mb-1">
                            <UserIcon class="w-4 h-4 text-blue-500" />
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Data Identitas
                            </span>
                        </div>

                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Nama Lengkap <span class="text-red-500">*</span>
                                <span class="text-gray-400 font-normal ml-1 text-xs">(sesuai ijazah)</span>
                            </label>
                            <input :value="form.nama_lengkap" @input="handleTitleCase('nama_lengkap', $event)"
                                type="text" placeholder="Contoh: Budi Santoso" autocomplete="name" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                       bg-white text-gray-900 placeholder-gray-400
                                       transition focus:outline-none focus:ring-2" :class="fieldError('nama_lengkap')
                                        ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                        : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                            <p v-if="fieldError('nama_lengkap')"
                                class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                {{ fieldError('nama_lengkap') }}
                            </p>
                        </div>

                        <!-- NIS + NISN -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    NIS <span class="text-gray-400 font-normal ml-1 text-xs">(opsional)</span>
                                </label>
                                <input v-model="form.nis" type="text" placeholder="Min. 7 digit" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('nis')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <p v-if="fieldError('nis')" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                    {{ fieldError('nis') }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    NISN <span class="text-red-500">*</span>
                                    <span class="text-gray-400 font-normal ml-1 text-xs">(10 digit)</span>
                                </label>
                                <input v-model="form.nisn" type="text" placeholder="0000000000" maxlength="10"
                                    inputmode="numeric" @input="form.nisn = form.nisn.replace(/\D/g, '')" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2 tracking-widest" :class="fieldError('nisn')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <div class="flex items-center justify-between mt-1.5">
                                    <p v-if="fieldError('nisn')" class="text-xs text-red-500 flex items-center gap-1">
                                        <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                        {{ fieldError('nisn') }}
                                    </p>
                                    <p v-else class="text-xs text-gray-400"></p>
                                    <span class="text-xs tabular-nums shrink-0"
                                        :class="form.nisn.length === 10 ? 'text-emerald-500 font-semibold' : 'text-gray-400'">
                                        {{ form.nisn.length }}/10
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Kelas -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Unit Kelas <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.kelas_id" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                       bg-white text-gray-900 transition focus:outline-none
                                       focus:ring-2 appearance-none select-custom" :class="fieldError('kelas_id')
                                        ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                        : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'">
                                <option value="">-- Pilih Kelas --</option>
                                <option v-for="k in kelas" :key="k.id" :value="k.id">{{ k.kelas }}</option>
                            </select>
                            <p v-if="fieldError('kelas_id')"
                                class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                {{ fieldError('kelas_id') }}
                            </p>
                        </div>

                        <!-- ══ SECTION: Data Pribadi ══ -->
                        <div class="flex items-center gap-2 pt-3 mb-1">
                            <IdentificationIcon class="w-4 h-4 text-blue-500" />
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Data Pribadi
                            </span>
                        </div>

                        <!-- Tempat & Tanggal Lahir -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    Tempat Lahir
                                </label>
                                <input :value="form.tempat_lahir" @input="handleTitleCase('tempat_lahir', $event)"
                                    type="text" placeholder="Contoh: Jakarta" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('tempat_lahir')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    Tanggal Lahir
                                </label>
                                <input v-model="form.tanggal_lahir" type="date"
                                    :max="new Date().toISOString().split('T')[0]" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('tanggal_lahir')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <p v-if="fieldError('tanggal_lahir')"
                                    class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                    {{ fieldError('tanggal_lahir') }}
                                </p>
                            </div>
                        </div>

                        <!-- Jenis Kelamin & Agama -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    Jenis Kelamin
                                </label>
                                <select v-model="form.jenis_kelamin" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 transition focus:outline-none
                                           focus:ring-2 appearance-none select-custom" :class="fieldError('jenis_kelamin')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'">
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    Agama
                                </label>
                                <select v-model="form.agama" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 transition focus:outline-none
                                           focus:ring-2 appearance-none select-custom" :class="fieldError('agama')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'">
                                    <option value="">-- Pilih --</option>
                                    <option v-for="a in agamaOptions" :key="a" :value="a">{{ a }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- ══ SECTION: Kontak ══ -->
                        <div class="flex items-center gap-2 pt-3 mb-1">
                            <PhoneIcon class="w-4 h-4 text-blue-500" />
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Kontak
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    No. HP / WA Siswa
                                </label>
                                <input v-model="form.no_hp" type="tel" placeholder="08xxxxxxxxxx" inputmode="tel"
                                    @input="form.no_hp = form.no_hp.replace(/[^\d+\-\s]/g, '')" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('no_hp')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <p v-if="fieldError('no_hp')"
                                    class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                    {{ fieldError('no_hp') }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    No. HP Orang Tua / Wali
                                </label>
                                <input v-model="form.no_hp_ortu" type="tel" placeholder="08xxxxxxxxxx" inputmode="tel"
                                    @input="form.no_hp_ortu = form.no_hp_ortu.replace(/[^\d+\-\s]/g, '')" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('no_hp_ortu')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <p v-if="fieldError('no_hp_ortu')"
                                    class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                    {{ fieldError('no_hp_ortu') }}
                                </p>
                            </div>
                        </div>

                        <!-- ══ SECTION: Alamat ══ -->
                        <div class="flex items-center gap-2 pt-3 mb-1">
                            <MapPinIcon class="w-4 h-4 text-blue-500" />
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Alamat Tempat Tinggal
                            </span>
                        </div>

                        <!-- Alamat lengkap -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Alamat Lengkap
                            </label>
                            <textarea v-model="form.alamat" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, dll."
                                class="w-full rounded-xl border px-4 py-2.5 text-sm
                                       bg-white text-gray-900 placeholder-gray-400
                                       transition focus:outline-none focus:ring-2 resize-none" :class="fieldError('alamat')
                                        ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                        : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'"></textarea>
                        </div>

                        <!-- Kelurahan & Kecamatan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kelurahan / Desa</label>
                                <input :value="form.kelurahan" @input="handleTitleCase('kelurahan', $event)" type="text"
                                    placeholder="Contoh: Cempaka Putih" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('kelurahan')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kecamatan</label>
                                <input :value="form.kecamatan" @input="handleTitleCase('kecamatan', $event)" type="text"
                                    placeholder="Contoh: Tanah Abang" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('kecamatan')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                            </div>
                        </div>

                        <!-- Kota & Kode Pos -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div class="col-span-2 sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kota / Kabupaten</label>
                                <input :value="form.kota" @input="handleTitleCase('kota', $event)" type="text"
                                    placeholder="Contoh: Jakarta Pusat" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2" :class="fieldError('kota')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kode Pos</label>
                                <input v-model="form.kode_pos" type="text" placeholder="12345" maxlength="5"
                                    inputmode="numeric" @input="form.kode_pos = form.kode_pos.replace(/\D/g, '')" class="w-full rounded-xl border px-4 py-2.5 text-sm
                                           bg-white text-gray-900 placeholder-gray-400
                                           transition focus:outline-none focus:ring-2 tracking-widest" :class="fieldError('kode_pos')
                                            ? 'border-red-400 focus:ring-red-300 bg-red-50'
                                            : 'border-gray-200 focus:ring-blue-300 focus:border-blue-400'" />
                                <p v-if="fieldError('kode_pos')"
                                    class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
                                    {{ fieldError('kode_pos') }}
                                </p>
                            </div>
                        </div>

                        <!-- Submit -->
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

            <p class="text-center text-xs text-gray-400 mt-5">
                Data yang kamu isi akan digunakan untuk keperluan administrasi sekolah.
            </p>

        </div>
    </div>
</template>

<style scoped>
.select-custom {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.2em;
    padding-right: 2.5rem;
}
</style>