<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>

        <Head title="Forgot Password" />

        <div class="mt-6 md:mt-0 bg-white border rounded-lg shadow md:p-0 md:shadow-none md:border-none p-6">
            <div class="mb-4 text-sm text-gray-600">
                Forgot your password? No problem. Just let us know your email address and we will email you a password
                reset
                link that will allow you to choose a new one.
            </div>

            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <InputLabel for="email" value="Email Address" />

                    <TextInput id="email" type="email" class="mt-1 rounded-lg block w-full" v-model="form.email"
                        required autofocus autocomplete="username" />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="flex flex-col gap-3 mt-4">


                    <!-- Submit Button -->
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                        class="w-full flex justify-center text-center px-4 py-3">
                        Email Password Reset Link
                    </PrimaryButton>

                    <!-- Back Button -->
                    <button type="button" @click="$inertia.visit(route('login'))"
                        class="flex items-center justify-center w-full  px-4 py-3 font-semibold text-gray-600 hover:text-gray-800 bg-white border border-gray-400 rounded-lg hover:border-gray-800 transition">
                        <!-- Icon panah kiri -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Sign In
                    </button>
                </div>

            </form>
        </div>
    </GuestLayout>
</template>
