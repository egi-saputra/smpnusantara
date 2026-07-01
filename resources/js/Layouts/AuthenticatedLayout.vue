<script setup>
import { onMounted, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { useAlert } from '@/Composables/useAlert.js'
import { PowerIcon } from '@heroicons/vue/24/solid'

// Props
const props = defineProps({
    disableSwal: { type: Boolean, default: false }
})

const page = usePage()
const { success, error } = useAlert()

onMounted(() => {
    if (props.disableSwal) return
    if (page.props.flash?.success) success(page.props.flash.success)
    if (page.props.flash?.error) error(page.props.flash.error)
})

watch(
    () => page.props.flash,
    (flash) => {
        if (props.disableSwal) return
        if (flash?.success) success(flash.success)
        if (flash?.error) error(flash.error)
    }
)
</script>

<template>
    <div class="min-h-screen bg-slate-50 relative">

        <!-- LOGOUT BUTTON ONLY -->
        <Link :href="route('logout')" method="post" as="button" class="fixed top-4 right-4 z-50
                   flex items-center justify-center
                   w-10 h-10 rounded-full
                   bg-white border border-gray-300
                   hover:bg-red-50 hover:border-red-400
                   transition" title="Logout">
            <PowerIcon class="w-5 h-5 text-gray-700 hover:text-red-600" />
        </Link>

        <!-- PAGE CONTENT -->
        <main class="p-6">
            <slot />
        </main>

    </div>
</template>
