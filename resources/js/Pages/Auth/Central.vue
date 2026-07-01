<script setup>
import { useForm } from '@inertiajs/inertia-vue3'

const form = useForm({
    kode: '',
})

const submitLogin = () => {
    form.post(route('auth.central'))
}
</script>

<template>
    <div class="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-100 to-gray-300 p-4">

        <div
            class="backdrop-blur-md bg-white/70 shadow-xl border border-white/30 rounded-2xl p-8 w-full max-w-sm animate-fadeIn">

            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6 tracking-wide">
                Masukkan ID Peserta
            </h2>

            <form @submit.prevent="submitLogin" class="space-y-5">

                <!-- INPUT -->
                <div>
                    <input type="text" v-model="form.kode" placeholder="Hanya 7 Digit Angka" maxlength="7"
                        pattern="\d{7}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white shadow-sm focus:border-[#063970] transition duration-200 
                        outline-none text-center placeholder:text-center" />
                </div>

                <!-- BUTTON -->
                <button type="submit" :disabled="form.processing" class="w-full py-3 text-white bg-[#063970] rounded-lg shadow-md
                           hover:bg-[#042d57] hover:shadow-lg transition-all duration-200 active:scale-95
                           flex justify-center items-center gap-2">
                    <svg v-if="form.processing" class="w-5 h-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span class="font-orbitron tracking-widest text-lg font-base">{{ form.processing ? 'Processing...' :
                        'SIGN IN'
                    }}</span>
                </button>

            </form>
        </div>
    </div>
</template>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.6s ease-out forwards;
}
</style>
