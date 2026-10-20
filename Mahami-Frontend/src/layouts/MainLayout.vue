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
    class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-900 transition-colors duration-200"
    :dir="prefs.dir"
  >
    <!-- ── Top Navbar ──────────────────────────────────────── -->
    <header class="h-14 shrink-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center px-4 gap-3 z-20">
      <!-- Brand -->
      <router-link
        :to="{ name: 'boards' }"
        class="flex items-center gap-2 text-blue-600 dark:text-blue-400 font-bold text-lg select-none"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="10" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="11" width="7" height="10" rx="1"/></svg>
        <span>{{ t('appName') }}</span>
      </router-link>

      <div class="flex-1" />

      <!-- Controls -->
      <div class="flex items-center gap-2">
        <!-- Language toggle -->
        <button
          class="px-2.5 py-1 text-sm rounded-lg border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
          @click="prefs.toggleLocale()"
        >
          {{ prefs.locale === 'ar' ? 'EN' : 'ع' }}
        </button>

        <!-- Theme toggle -->
        <button
          class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
          @click="prefs.toggleTheme()"
        >
          <svg v-if="prefs.isDark" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>

        <!-- User menu -->
        <div class="relative">
          <button
            class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-sm text-slate-700 dark:text-slate-200"
            @click="userMenuOpen = !userMenuOpen"
          >
            <span class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold uppercase">
              {{ authStore.user?.name?.charAt(0) ?? '?' }}
            </span>
            <span class="hidden sm:block max-w-32 truncate">{{ authStore.user?.name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
          </button>

          <!-- Dropdown -->
          <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="userMenuOpen"
              class="absolute end-0 top-full mt-1 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg py-1 z-50"
            >
              <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700">
                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ authStore.user?.name }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ authStore.user?.email }}</p>
              </div>
              <button
                class="w-full text-start px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                @click="handleLogout"
              >
                {{ t('logout') }}
              </button>
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
    class="fixed inset-0 z-10"
    @click="userMenuOpen = false"
  />
</template>

