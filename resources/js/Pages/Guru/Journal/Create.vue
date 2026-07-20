<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeftIcon } from '@heroicons/vue/24/solid'
import Form from './Form.vue'

const props = defineProps({
    kelasList: Array,
    mapelList: Array,
    serverTime: Object, // { tanggal, jam_mulai, jam_selesai } — ditentukan oleh server
})

// Tanggal & jam hanya untuk ditampilkan (read-only), nilai final tetap dihitung ulang di server saat submit
const form = useForm({
    kelas_id: '',
    mapel_id: '',
    tanggal: props.serverTime.tanggal,
    jam_mulai: props.serverTime.jam_mulai,
    jam_selesai: props.serverTime.jam_selesai,
    materi: '',
})

const submit = () => {
    form.post(route('guru.journal.store'))
}
</script>

<template>

    <Head title="Isi Jurnal Mengajar" />

    <UserLayout>
        <div class="mx-auto">
            <Link :href="route('guru.journal.index')"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mb-5">
                <ArrowLeftIcon class="w-4 h-4" />
                Kembali ke Jurnal
            </Link>

            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Isi Jurnal Mengajar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Catat kehadiran & materi sebelum memulai pelajaran.
                </p>
            </div>

            <div
                class="p-6 rounded-2xl border bg-white border-gray-100 shadow-sm dark:bg-gray-900/60 dark:border-gray-800">
                <Form :form="form" :kelas-list="kelasList" :mapel-list="mapelList" :lock-time="true"
                    submit-label="Simpan Jurnal" @submit="submit" />
            </div>
        </div>
    </UserLayout>
</template>