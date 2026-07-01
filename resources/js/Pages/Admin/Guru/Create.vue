<script setup>
import MenuLayout from '@/Layouts/MenuLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    users: Array,   // menerima data user role guru
})

const form = useForm({
    user_id: '',
    nama_lengkap: '',
})

const submit = () => {
    form.post(route('admin.guru.store'))
}
</script>

<template>

    <Head title="Add Teacher Data" />

    <MenuLayout>
        <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-md rounded-2xl shadow-xl p-6 transition">
            <h1 class="text-xl dark:text-gray-200 font-semibold mb-6">Add Teacher</h1>

            <form @submit.prevent="submit" class="space-y-4">

                <!-- Dropdown User -->
                <div>
                    <label class="block text-sm dark:text-gray-400 font-medium mb-1">Select User</label>
                    <select v-model="form.user_id" required
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white/60 dark:bg-gray-700/50 px-4 py-2 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <option value="">-- select user --</option>
                        <option v-for="u in props.users" :key="u.id" :value="u.id">
                            {{ u.name }}
                        </option>
                    </select>

                    <div v-if="form.errors.user_id" class="text-red-500 text-sm">
                        {{ form.errors.user_id }}
                    </div>
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium dark:text-gray-400 mb-1">Full Name + Title
                        (Optional)</label>
                    <input v-model="form.nama_lengkap" type="text" placeholder="e.g., Guru Pertama, S. Pd" required
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white/60 dark:bg-gray-700/50 px-4 py-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />

                    <div v-if="form.errors.nama_lengkap" class="text-red-500 text-sm">
                        {{ form.errors.nama_lengkap }}
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Link :href="route('admin.guru.index')"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        Cancel
                    </Link>
                    <button type="submit" class="px-4 py-2 rounded-lg hover:bg-blue-800 bg-blue-600 text-white"
                        :disabled="form.processing">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </MenuLayout>
</template>
