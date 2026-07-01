<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { ToastAlert } from '@/Composables/ToastAlert.js'
import { onClickOutside } from '@vueuse/core'

import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'

import { ArrowLeftIcon, ChevronRightIcon, UserIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/solid'
import { Cog6ToothIcon, MoonIcon, SunIcon, BellIcon } from '@heroicons/vue/24/outline'

import AdminSidebar from '@/Components/Admin/Sidebar.vue'
import ProktorSidebar from '@/Components/Proktor/Sidebar.vue'
import GuruSidebar from '@/Components/Guru/Sidebar.vue'
import SiswaSidebar from '@/Components/Siswa/Sidebar.vue'

/* ================= PROPS ================= */
const props = defineProps({
    disableSwal: { type: Boolean, default: false },
    logoUrl: { type: String, default: '/storage/logo_app/logo.png' }
})

const page = usePage()
const { success, error } = ToastAlert()

/* ================= NOTIFICATION ================= */
const showingNotifDropdown = ref(false)
const notifDropdownRef = ref(null)
const bellButtonRef = ref(null)

const readNotifications = ref(
    JSON.parse(localStorage.getItem('readNotifications') || '[]')
)

const role = page.props.auth.role.toLowerCase()

const notifications = computed(() => {
    const anns = page.props.announcements || []

    return anns
        .filter(item => {
            if (item.penerima === 'semua') return true
            if (item.penerima === role) return true
            if (role === 'siswa' && item.kelas_id) {
                return Number(item.kelas_id) === Number(page.props.kelasId)
            }
            return false
        })
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const toggleNotifDropdown = () => {
    showingNotifDropdown.value = !showingNotifDropdown.value

    if (showingNotifDropdown.value) {
        notifications.value.forEach(n => {
            if (!readNotifications.value.includes(n.id)) {
                readNotifications.value.push(n.id)
            }
        })
        localStorage.setItem(
            'readNotifications',
            JSON.stringify(readNotifications.value)
        )
    }
}

const hasUnread = computed(() =>
    notifications.value.some(n => !readNotifications.value.includes(n.id))
)

const goToIndex = id => {
    router.visit(route('pengumuman.index', { id }))
}

/* 👉 CLICK OUTSIDE NOTIF */
onClickOutside(
    notifDropdownRef,
    () => (showingNotifDropdown.value = false),
    { ignore: [bellButtonRef] }
)

/* ================= RESPONSIVE NAV ================= */
const showingNavigationDropdown = ref(false)
const navDropdownRef = ref(null)
const navButtonRef = ref(null)

/* CLICK OUTSIDE NAV (vueuse) */
onClickOutside(
    navDropdownRef,
    () => (showingNavigationDropdown.value = false),
    { ignore: [navButtonRef] }
)

/* ================= SIDEBAR ================= */
const SidebarComponent = computed(() => {
    switch (page.props.auth.role) {
        case 'admin': return AdminSidebar
        case 'proktor': return ProktorSidebar
        case 'guru': return GuruSidebar
        case 'siswa': return SiswaSidebar
        default: return null
    }
})

/* ================= DASHBOARD ================= */
const dashboardHref = computed(() => {
    switch (page.props.auth.role) {
        case 'admin': return route('admin.dashboard')
        case 'proktor': return route('proktor.dashboard')
        case 'guru': return route('guru.dashboard')
        case 'siswa': return route('siswa.dashboard')
        default: return route('dashboard')
    }
})

const navLinks = computed(() => {
    const role = page.props.auth.role
    const links = {
        admin: [
            { name: 'Dashboard', href: route('admin.dashboard') },
        ],
        proktor: [
            { name: 'Dashboard', href: route('proktor.dashboard') },
        ],
        guru: [
            { name: 'Dashboard', href: route('guru.dashboard') },
        ],
        siswa: [
            { name: 'Dashboard', href: route('siswa.dashboard') },
        ]
    }

    return links[role] || []
})

/* ================= BACK ================= */
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back()
    } else {
        router.visit(route(`${page.props.auth.role}.dashboard`))
    }
}

/* ================= DARK MODE ================= */
const isDark = ref(false)

const applyTheme = dark => {
    const html = document.documentElement
    html.classList.toggle('dark', dark)
    localStorage.setItem('theme', dark ? 'dark' : 'light')
}

const toggleDarkMode = () => {
    const html = document.documentElement
    const layer = document.getElementById('theme-transition-layer')

    layer.classList.remove('reveal-in', 'reveal-out')
    layer.classList.add('reveal-in')

    setTimeout(() => {
        isDark.value = !isDark.value
        html.classList.toggle('dark', isDark.value)
        localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
    }, 600)

    setTimeout(() => {
        layer.classList.remove('reveal-in')
        layer.classList.add('reveal-out')
    }, 600)

    setTimeout(() => {
        layer.classList.remove('reveal-out')
        layer.style.clipPath = 'circle(0% at 0% 0%)'
    }, 1200)
}

onMounted(() => {
    const theme = localStorage.getItem('theme')
    isDark.value = theme === 'dark'
    applyTheme(isDark.value)
})
</script>


<template>
    <div id="theme-transition-layer"></div>

    <div class="h-screen bg-white sm:bg-gray-100 dark:bg-[#0B1F3A] flex flex-col overflow-hidden">
        <!-- Navbar SPA -->
        <nav
            class="bg-white dark:bg-[#041C32] sm:dark:bg-[#0F172A] border-b border-gray-300 dark:sm:border-gray-600 dark:border-[#1e1b4b] sticky top-0 z-50 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto sm:px-0 px-2">
                <div class="flex justify-between h-16">

                    <div class="flex items-center">
                        <div class="shrink-0 flex items-center">
                            <!-- Nama Sekolah -->
                            <div class="sm:flex hidden items-center text-[#063970]">
                                <!-- Logo Sekolah SPA-friendly -->
                                <img :src="props.logoUrl" class="h-16 -ml-10 sm:block hidden object-contain"
                                    alt="Logo Sekolah" loading="lazy" />
                                <span
                                    class="sm:text-lg text-base sm:-ml-6 ml-4 font-raleway font-extrabold dark:text-white text-[#063970]">
                                    SMK NUSANTARA
                                </span>
                            </div>

                            <button @click="goBack"
                                class="p-1 mr-2 sm:hidden block rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200">
                                <ArrowLeftIcon class="h-5 w-5 text-[#063970] dark:text-white" />
                            </button>

                            <span class="text-lg font-bold sm:hidden block dark:text-white text-[#063970]">
                                {{ page.props.title || '' }}
                            </span>
                        </div>
                    </div>

                    <!-- Dark Mode Toggle Desktop -->
                    <button @click="toggleDarkMode" title="Toggle Dark Mode"
                        class="relative my-auto mr-3 sm:flex hidden w-16 h-8 rounded-full backdrop-blur-xl bg-white/60 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 shadow-inner shadow-black/10 dark:shadow-black/40 transition-all duration-300">
                        <!-- ICONS -->
                        <SunIcon
                            class="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-yellow-400 transition-opacity duration-300"
                            :class="isDark ? 'opacity-90' : 'opacity-100'" />
                        <MoonIcon
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-400 transition-opacity duration-300"
                            :class="isDark ? 'opacity-100' : 'opacity-60'" />

                        <!-- TOGGLE KNOB -->
                        <span
                            class="absolute top-1/2 -translate-y-1/2 w-6 h-6 rounded-full backdrop-blur-md bg-white/80 dark:bg-slate-800 shadow-md shadow-black/20 transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]"
                            :class="isDark ? 'translate-x-8' : 'translate-x-1'" />
                    </button>

                    <div class="flex">
                        <!-- Dark Mode Toggle (Glassmorphism) Mobile -->
                        <button @click="toggleDarkMode" title="Toggle Dark Mode"
                            class="relative my-auto mr-3 sm:hidden w-14 h-8 rounded-full backdrop-blur-xl bg-white/60 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 shadow-inner shadow-black/10 dark:shadow-black/40 transition-all duration-300">
                            <!-- ICONS -->
                            <SunIcon
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-yellow-400 transition-opacity duration-300"
                                :class="isDark ? 'opacity-30' : 'opacity-100'" />
                            <MoonIcon
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-400 transition-opacity duration-300"
                                :class="isDark ? 'opacity-100' : 'opacity-30'" />

                            <!-- TOGGLE KNOB -->
                            <span
                                class="absolute top-1/2 -translate-y-1/2 w-6 h-6 rounded-full backdrop-blur-md bg-white/80 dark:bg-slate-800/80 shadow-md shadow-black/20 transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]"
                                :class="isDark ? '-translate-x-6' : 'translate-x-0'" />
                        </button>

                        <!-- Bell Notification -->
                        <div class="relative">

                            <!-- Icon Bell -->
                            <button ref="bellButtonRef" @click="toggleNotifDropdown"
                                class="relative sm:block hidden p-2 rounded-full transition">
                                <BellIcon
                                    class="w-6 h-6 mt-3 sm:-mr-4 hover:text-gray-900 text-gray-500 dark:text-white dark:hover:text-gray-300" />
                                <span v-if="hasUnread"
                                    class="absolute top-2 right-2 w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                            </button>

                            <!-- Dropdown Notifikasi (Glassmorphism) -->
                            <transition enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 translate-y-2 scale-95"
                                enter-to-class="opacity-100 translate-y-0 scale-100"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 translate-y-0 scale-100"
                                leave-to-class="opacity-0 translate-y-2 scale-95">
                                <div v-if="showingNotifDropdown" ref="notifDropdownRef"
                                    class="absolute -right-6 mt-2 z-30 w-80 rounded-xl backdrop-blur-xl bg-white/70 dark:bg-slate-900/70 border border-white/40 dark:border-white/10 shadow-xl shadow-black/10 dark:shadow-black/40">

                                    <!-- Header -->
                                    <h3
                                        class="px-4 py-2 flex items-center gap-2 font-semibold border-b border-white/30 dark:border-white/10 text-gray-700 dark:text-white">
                                        <i class="bi bi-megaphone text-blue-600 dark:text-blue-400"></i>
                                        Notifikasi Terbaru
                                    </h3>

                                    <!-- List Notifikasi -->
                                    <ul class="divide-y divide-white/30 dark:divide-white/10 max-h-80 overflow-auto">
                                        <li v-for="notif in notifications" :key="notif.id"
                                            @click.prevent="goToIndex(notif.id)"
                                            class="px-4 py-3 cursor-pointer transition hover:bg-white/50 dark:hover:bg-white/5 flex justify-between items-center">
                                            <div class="flex flex-col overflow-hidden">
                                                <p class="font-medium text-gray-700 dark:text-white max-w-60 truncate">
                                                    {{ notif.judul }}
                                                </p>
                                                <div v-html="notif.pengumuman"
                                                    class="prose dark:prose-invert max-w-60 max-h-8 truncate">
                                                </div>
                                                <!-- <p class="text-sm text-gray-500 dark:text-gray-400 max-w-60 truncate">
                                                    {{ notif.pengumuman }}
                                                </p> -->
                                            </div>
                                            <ChevronRightIcon class="w-5 h-5 text-gray-400 dark:text-gray-300" />
                                        </li>

                                        <!-- Fallback jika kosong -->
                                        <li v-if="notifications.length === 0"
                                            class="px-4 py-3 text-center italic text-gray-400 dark:text-gray-500">
                                            Tidak ada pemberitahuan.
                                        </li>
                                    </ul>
                                </div>
                            </transition>
                        </div>

                        <!-- User Dropdown -->
                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        class="inline-flex items-center px-3 py-2 border border-transparent font-medium rounded-md text-gray-500 bg-white dark:bg-[#0F172A] dark:text-white dark:hover:text-gray-300 hover:text-gray-700 text-sm">
                                        {{ $page.props.auth.user.name }}
                                        <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Gear Icon responsive -->
                        <div class="flex items-center sm:hidden">
                            <button ref="navButtonRef" @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-[#063970] dark:text-white transition-transform duration-150 ease-in-out transform responsive-toggle-button">
                                <Cog6ToothIcon :class="showingNavigationDropdown ? 'rotate-90' : 'rotate-0'"
                                    class="h-6 w-6 transform transition-transform duration-300 ease-in-out text-gray-700 dark:text-white" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responsive Dropend Navigation Menu -->
            <transition enter-active-class="transition-all ease-out duration-300"
                enter-from-class="opacity-0 translate-x-6" enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition-all ease-in duration-200" leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-6">
                <div v-if="showingNavigationDropdown" ref="navDropdownRef" class="absolute sm:top-12 top-14 right-0 mr-3 z-50 w-52 rounded-2xl
               backdrop-blur-xl
               bg-white/70 dark:bg-slate-900/70
               shadow-xl shadow-black/10 dark:shadow-black/40
               border border-white/40 dark:border-white/10">
                    <div class="py-2 flex flex-col gap-1">
                        <ResponsiveNavLink :href="route('dashboard')" prefetch preserve-state preserve-scroll class="flex items-center gap-2 px-4 py-2
                       text-gray-700 dark:text-gray-200
                       hover:bg-white/60 dark:hover:bg-white/10
                       rounded-lg transition" @click="showingNavigationDropdown = false">
                            <UserIcon class="w-5 h-5 text-blue-500" />
                            Dashboard
                        </ResponsiveNavLink>

                        <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="flex items-center gap-2 px-4 py-2
                       text-red-600 dark:text-red-400
                       hover:bg-red-500/10
                       rounded-lg transition" @click="showingNavigationDropdown = false">
                            <ArrowRightOnRectangleIcon class="w-5 h-5" />
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
            </transition>
        </nav>

        <!-- Main area: Sidebar + Content -->
        <div class="flex flex-1 min-h-0">

            <!-- SIDEBAR (auto width + scrollable) -->
            <component :is="SidebarComponent" class="hidden md:block bg-white dark:bg-[#0F172A] dark:border-gray-600 border-r border-gray-300 pt-4
               overflow-y-auto overflow-x-hidden" />

            <!-- MAIN CONTENT (otomatis menyesuaikan) -->
            <div class="flex-1 sm:px-8 sm:py-6 bg-gray-100 dark:bg-[#020617] overflow-auto">
                <slot />
            </div>

        </div>
    </div>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
