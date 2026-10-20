<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { usePreferencesStore } from '@/stores/preferencesStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const authStore = useAuthStore()
const prefs = usePreferencesStore()
const { t } = useI18n()
const toast = useToast()

const userMenuOpen = ref(false)

async function handleLogout() {
  userMenuOpen.value = false
  await authStore.logout()
  toast.success(t('loggedOut'))
  router.push({ name: 'login' })
}
</script>

<template>
  <div
    class="min-h-screen flex flex-col transition-colors duration-200"
    :dir="prefs.dir"
  >
    <!-- ── Top Navbar ──────────────────────────────────────── -->
    <header class="h-16 shrink-0 sticky top-0 z-30 glass-nav bg-white/80 dark:bg-slate-900/80 border-b border-slate-200/80 dark:border-slate-800/80 flex items-center px-4 sm:px-6 gap-3 select-none">
      <!-- Brand -->
      <router-link
        :to="{ name: 'boards' }"
        class="flex items-center gap-2.5 group transition-transform active:scale-95"
      >
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-blue-500 flex items-center justify-center text-white shadow-sm shadow-blue-500/25 group-hover:shadow-md group-hover:shadow-blue-500/35 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25">
            <rect x="3" y="3" width="7" height="11" rx="1.5" />
            <rect x="3" y="17" width="7" height="4" rx="1" />
            <rect x="14" y="3" width="7" height="5" rx="1" />
            <rect x="14" y="11" width="7" height="10" rx="1.5" />
          </svg>
        </div>
        <span class="font-extrabold text-lg sm:text-xl tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 dark:from-blue-400 dark:via-indigo-300 dark:to-blue-300 bg-clip-text text-transparent">
          {{ t('appName') }}
        </span>
      </router-link>

      <div class="flex-1" />

      <!-- Controls -->
      <div class="flex items-center gap-2">
        <!-- Language toggle -->
        <button
          class="px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-750 transition-all shadow-2xs hover:shadow-xs cursor-pointer active:scale-95"
          :title="t('language')"
          @click="prefs.toggleLocale()"
        >
          <span class="inline-flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" />
              <line x1="2" y1="12" x2="22" y2="12" />
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
            </svg>
            {{ prefs.locale === 'ar' ? 'English' : 'العربية' }}
          </span>
        </button>

        <!-- Theme toggle -->
        <button
          class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-750 transition-all shadow-2xs hover:shadow-xs cursor-pointer active:scale-95"
          :title="prefs.isDark ? t('lightMode') : t('darkMode')"
          @click="prefs.toggleTheme()"
        >
          <svg v-if="prefs.isDark" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5" />
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
          </svg>
        </button>

        <!-- User menu -->
        <div class="relative ms-1">
          <button
            class="flex items-center gap-2 p-1.5 pe-2.5 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-700 hover:bg-white dark:hover:bg-slate-800 transition-all text-sm text-slate-700 dark:text-slate-200 cursor-pointer"
            @click="userMenuOpen = !userMenuOpen"
          >
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-xs font-bold uppercase shadow-xs">
              {{ authStore.user?.name?.charAt(0) ?? 'U' }}
            </div>
            <span class="hidden sm:block max-w-28 truncate font-medium text-xs">{{ authStore.user?.name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-60 transition-transform duration-150" :class="{ 'rotate-180': userMenuOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </button>

          <!-- Dropdown -->
          <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
          >
            <div
              v-if="userMenuOpen"
              class="absolute end-0 top-full mt-2 w-56 bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700/90 rounded-2xl shadow-xl py-1.5 z-50 divide-y divide-slate-100 dark:divide-slate-700/60"
            >
              <div class="px-4 py-3">
                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">{{ authStore.user?.name }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ authStore.user?.email }}</p>
              </div>

              <div class="p-1">
                <button
                  class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-xl transition-colors cursor-pointer"
                  @click="handleLogout"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                  </svg>
                  <span>{{ t('logout') }}</span>
                </button>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </header>

    <!-- ── Page content ────────────────────────────────────── -->
    <main class="flex-1 overflow-hidden">
      <slot />
    </main>
  </div>

  <!-- Click-outside overlay for menus -->
  <div
    v-if="userMenuOpen"
    class="fixed inset-0 z-20"
    @click="userMenuOpen = false"
  />
</template>

