<script setup>
import MenuLayout from '@/Layouts/MenuLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { CheckIcon, ArrowLeftIcon, DocumentArrowUpIcon, PlusIcon } from '@heroicons/vue/24/solid';
import { useAlert } from '@/Composables/useAlert.js';
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';
import Swal from 'sweetalert2';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({ soal_id: [Number, String] });
const { success, error } = useAlert();
const page = usePage();

onMounted(() => {
    if (page.props.flash?.success) success(page.props.flash.success);
});

watch(
    () => page.props.flash?.success,
    (newVal) => { if (newVal) success(newVal); }
);

const form = useForm({
    soal_id: Number(props.soal_id),
    soal: '',
    tipe_soal: 'PG',
    jenis_lampiran: 'Tanpa Lampiran',
    lampiran_file: null,
    opsi_a: '',
    opsi_b: '',
    opsi_c: '',
    opsi_d: '',
    opsi_e: '',
    jawaban_benar: '',
    nilai: 0,
    excel: null,
});

// State untuk opsi jawaban
const opsiState = ref(['a']);
const opsiFiles = ref({});
const opsiPreviews = ref({});

function addOpsi() {
    if (opsiState.value.length < 5) {
        const nextOpsi = String.fromCharCode(97 + opsiState.value.length);
        opsiState.value.push(nextOpsi);
    }
}

function removeOpsi() {
    if (opsiState.value.length > 1) {
        const lastKey = opsiState.value.pop(); // hapus key terakhir
        form['opsi_' + lastKey] = '';         // kosongkan value di form
    }
}

function handleOpsiFile(event, key) {
    const file = event.target.files[0];
    if (!file) return;

    // Bebaskan URL lama supaya tidak memory leak
    if (opsiPreviews.value[key]) URL.revokeObjectURL(opsiPreviews.value[key]);

    opsiFiles.value[key] = file;
    opsiPreviews.value[key] = URL.createObjectURL(file);
}

// Upload file gambar
function handleFile(event) {
    form.lampiran_file = event.target.files[0] || null;
}

// Submit soal manual
function submitManual() {
    if (form.jenis_lampiran === 'Gambar' && !form.lampiran_file) {
        return Swal.fire('Error', 'Silakan upload file gambar terlebih dahulu!', 'error');
    }

    const data = new FormData();
    Object.keys(form).forEach(key => {
        if (key === 'lampiran_file') {
            if (form.jenis_lampiran === 'Gambar' && form.lampiran_file) {
                data.append('lampiran_file', form.lampiran_file);
            }
        } else {
            data.append(key, form[key]);
        }
    });

    // Lampirkan gambar opsi
    Object.entries(opsiFiles.value).forEach(([key, file]) => {
        data.append(`opsi_${key}_file`, file);
    });

    axios.post('/proktor/bank-soal', data)
        .then(res => {
            Swal.fire({
                icon: 'success', title: 'Berhasil!',
                text: res.data.success || 'Soal manual berhasil ditambahkan.',
                confirmButtonText: 'OKE', confirmButtonColor: '#3b82f6'
            }).then(result => {
                if (result.isConfirmed) Inertia.visit(res.data.redirect || `/proktor/soal/${props.soal_id}`);
            });
        })
        .catch(err => {
            Swal.fire('Error', err.response?.data?.message || 'Terjadi kesalahan saat submit soal manual.', 'error');
        });
}

// Import Excel
function importExcel(event) { form.excel = event.target.files[0] || null; }
function submitExcel() {
    if (!form.excel) return;
    form.processing = true;
    const data = new FormData();
    data.append('excel', form.excel);
    data.append('soal_id', props.soal_id);
    axios.post('/proktor/bank-soal/import', data)
        .then(res => {
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.data.success, confirmButtonText: 'OKE', confirmButtonColor: '#3b82f6' })
                .then((result) => { if (result.isConfirmed && res.data.redirect) Inertia.visit(res.data.redirect); });
        })
        .catch(err => Swal.fire('Error', 'Terjadi kesalahan saat import', 'error'))
        .finally(() => { form.processing = false; });
}

const isManualDisabled = computed(() => !!form.excel);
function downloadTemplate() { Inertia.visit('/proktor/bank-soal/template'); }
</script>

<template>
    <MenuLayout>
        <div class="mx-auto bg-gray-100 dark:bg-slate-950">
            <form @submit.prevent="submitManual" class="mx-auto space-y-5 sm:p-6
             sm:bg-white sm:border sm:border-gray-300 sm:rounded-2xl sm:shadow
             sm:dark:bg-slate-900 sm:dark:border-slate-800">

                <!-- TITLE -->
                <h1 class="text-2xl font-extrabold mb-6 text-gray-800 dark:text-slate-100">
                    <span class="text-3xl">+</span> Tambahkan Soal Quiz
                </h1>

                <!-- IMPORT EXCEL -->
                <div class="border border-dashed p-4 rounded-lg text-center space-y-2
               bg-gray-50 border-gray-300
               dark:bg-slate-800/60 dark:border-slate-700">

                    <label class="flex flex-col items-center justify-center cursor-pointer">
                        <DocumentArrowUpIcon class="w-10 h-10 text-blue-500 mb-2" />
                        <span class="font-semibold mb-1 text-gray-600 dark:text-slate-200">
                            Upload File Soal
                        </span>
                        <span class="text-sm text-gray-400 dark:text-slate-400">
                            (.xlsx / .xls)
                        </span>
                        <input type="file" accept=".xlsx,.xls" @change="importExcel" class="hidden" />
                    </label>

                    <p v-if="form.excel" class="mt-2 font-medium text-green-600 dark:text-green-400">
                        {{ form.excel.name }}
                    </p>

                    <div class="flex justify-center gap-2 mt-2">
                        <button type="button" @click="submitExcel" :disabled="!form.excel || form.processing" :class="[
                            'px-4 py-2 rounded-lg transition font-medium flex items-center gap-2 text-white',
                            form.processing
                                ? 'bg-gray-400 cursor-not-allowed'
                                : 'bg-green-600 hover:bg-green-700'
                        ]">
                            <svg v-if="form.processing" class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            <span>{{ form.processing ? 'Importing...' : 'Import Excel' }}</span>
                        </button>

                        <button type="button" @click="downloadTemplate" class="px-4 py-2 rounded-lg font-medium transition
                   bg-blue-600 text-white hover:bg-blue-700">
                            Unduh Template
                        </button>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <!-- TIPE SOAL -->
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-slate-200">
                            <span class="text-red-600">*</span> Tipe Soal
                        </label>
                        <select v-model="form.tipe_soal" :disabled="isManualDisabled"
                            class="w-full p-3 rounded-lg border transition border-gray-300 focus:ring-2 focus:ring-blue-400 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                            <option value="PG">Pilihan Ganda</option>
                            <option value="Essay">Essay</option>
                        </select>
                    </div>

                    <!-- JENIS LAMPIRAN -->
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-slate-200">
                            Jenis Lampiran
                        </label>
                        <select v-model="form.jenis_lampiran" :disabled="isManualDisabled"
                            class="w-full p-3 rounded-lg border border-gray-300 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                            <option value="Tanpa Lampiran">Tanpa Lampiran</option>
                            <option value="Gambar">Gambar</option>
                        </select>
                    </div>
                </div>

                <!-- UPLOAD GAMBAR -->
                <div v-if="form.jenis_lampiran === 'Gambar'">
                    <label class="block font-semibold mb-1 text-gray-700 dark:text-slate-200">
                        Upload Gambar
                    </label>
                    <input type="file" @change="handleFile" class="w-full p-2 rounded-lg border
                 border-gray-300
                 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200" />
                    <p v-if="form.lampiran_file" class="mt-1 text-green-600 dark:text-green-400">
                        {{ form.lampiran_file.name }}
                    </p>
                </div>

                <!-- SOAL -->
                <div>
                    <label class="block font-semibold mb-1 text-gray-700 dark:text-slate-200">
                        <span class="text-red-600">*</span> Soal / Pertanyaan
                    </label>

                    <div class="rounded-xl overflow-hidden border shadow-sm
                 border-gray-300 bg-white
                 dark:border-slate-700 dark:bg-slate-900">

                        <QuillEditor v-model:content="form.soal" placeholder="Type the question here..."
                            content-type="html" theme="snow" class="announcement-editor" :toolbar="[
                                ['bold', 'italic', 'underline'],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                [{ align: [] }],
                                ['clean']
                            ]" />

                        <div class="flex justify-end border-t border-gray-300 dark:border-slate-700">
                            <span class="px-3 py-2 text-xs text-gray-500 dark:text-slate-400">
                                Powered by
                                <strong class="pl-1 tracking-widest text-gray-700 dark:text-slate-200">
                                    KreatiCraft
                                </strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- OPTIONS -->
                <div v-if="form.tipe_soal === 'PG'" class="space-y-4 pt-4">
                    <div class="flex sm:flex-row flex-col gap-3 justify-start sm:justify-between">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-200">Answer Options</h3>
                        <div class="flex gap-2">
                            <button v-if="opsiState.length > 1" type="button" @click="removeOpsi"
                                class="text-red-600 btn-primary !py-1 !px-3 font-semibold flex items-center gap-1">
                                Remove
                            </button>
                            <button v-if="opsiState.length < 5" type="button" @click="addOpsi"
                                class="text-indigo-600 font-semibold btn-primary !py-1 !px-3 flex items-center gap-1">
                                <PlusIcon class="w-4 h-4" /> Add
                            </button>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div v-for="key in opsiState" :key="key" class="space-y-2">
                            <label class="text-sm font-medium dark:text-gray-300">
                                Option {{ key.toUpperCase() }}
                            </label>

                            <!-- Teks opsi -->
                            <input v-model="form['opsi_' + key]" class="form-input dark:text-gray-400 w-full"
                                placeholder="Enter Optional Answer" />

                            <!-- Upload gambar opsi -->
                            <div class="flex items-center gap-2">
                                <label :for="`opsi_${key}_file`" class="cursor-pointer text-xs px-3 py-1.5 rounded-lg border
                           border-gray-300 dark:border-slate-600
                           bg-gray-50 dark:bg-slate-800
                           text-gray-600 dark:text-slate-300
                           hover:bg-gray-100 dark:hover:bg-slate-700 transition">
                                    📷 Gambar (opsional)
                                </label>
                                <input :id="`opsi_${key}_file`" type="file" accept="image/*"
                                    @change="handleOpsiFile($event, key)" class="hidden" />

                                <!-- Preview -->
                                <span v-if="opsiFiles[key]"
                                    class="text-xs text-green-600 dark:text-green-400 truncate max-w-[120px]">
                                    {{ opsiFiles[key].name }}
                                </span>
                            </div>

                            <!-- Preview thumbnail -->
                            <img v-if="opsiPreviews[key]" :src="opsiPreviews[key]"
                                class="mt-1 h-20 rounded-lg object-cover border border-gray-200 dark:border-slate-700" />
                        </div>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <!-- JAWABAN BENAR -->
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-slate-200">
                            Jawaban Benar
                        </label>

                        <input v-if="form.tipe_soal === 'Essay'" v-model="form.jawaban_benar" type="text"
                            placeholder="Jawaban Essay" :disabled="isManualDisabled" class="w-full p-3 rounded-lg border
                 border-gray-300
                 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" />

                        <select v-else v-model="form.jawaban_benar" :disabled="isManualDisabled" class="w-full p-3 rounded-lg border
                 border-gray-300
                 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                            <option value="opsi_a">A. {{ form.opsi_a }}</option>
                            <option value="opsi_b">B. {{ form.opsi_b }}</option>
                            <option value="opsi_c">C. {{ form.opsi_c }}</option>
                            <option value="opsi_d">D. {{ form.opsi_d }}</option>
                            <option value="opsi_e">E. {{ form.opsi_e }}</option>
                        </select>
                    </div>

                    <!-- NILAI -->
                    <div>
                        <label class="block font-semibold mb-1 text-gray-700 dark:text-slate-200">
                            Bobot Nilai
                        </label>
                        <input v-model="form.nilai" type="number" min="0" :disabled="isManualDisabled"
                            class="w-full p-3 rounded-lg border border-gray-300 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" />
                    </div>
                </div>

                <!-- BUTTON -->
                <div class="flex flex-col md:flex-row gap-4 mt-4">
                    <button type="submit" :disabled="form.processing" class="flex-1 flex items-center justify-center gap-2 px-6 py-3
                 bg-gradient-to-r from-blue-500 to-blue-700
                 hover:from-blue-600 hover:to-blue-800
                 text-white font-semibold rounded-lg shadow transition">
                        <CheckIcon class="w-5 h-5" />
                        Create Quest
                    </button>

                    <Link :href="`/proktor/soal/${props.soal_id}`" class="flex-1 flex items-center justify-center gap-2 px-6 py-3
                 bg-gray-600 hover:bg-gray-700
                 text-white font-semibold rounded-lg shadow transition">
                        <ArrowLeftIcon class="w-5 h-5" />
                        Back to Quiz List
                    </Link>
                </div>

            </form>
        </div>
    </MenuLayout>
</template>
