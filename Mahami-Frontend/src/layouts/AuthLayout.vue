<script setup>
import { usePreferencesStore } from '@/stores/preferencesStore'
import { useI18n } from '@/composables/useI18n'

const prefs = usePreferencesStore()
const { t } = useI18n()
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-slate-100 dark:bg-slate-900 transition-colors duration-200 px-4"
    :dir="prefs.dir"
  >
    <!-- Language & theme toggles (top-right corner) -->
    <div class="fixed top-3 end-3 sm:top-4 sm:end-4 flex items-center gap-2 z-10">
      <button
        class="px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs"
        @click="prefs.toggleLocale()"
      >
        {{ prefs.locale === 'ar' ? 'EN' : 'ع' }}
      </button>
      <button
        class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs"
        :title="prefs.isDark ? 'Light mode' : 'Dark mode'"
        @click="prefs.toggleTheme()"
      >
        <svg v-if="prefs.isDark" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
    </div>

    <!-- Centered card -->
    <div class="w-full max-w-md py-6 sm:py-8">
      <!-- Logo / brand -->
      <div class="text-center mb-6 sm:mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-600 mb-3 shadow-md shadow-blue-500/20">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="10" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="11" width="7" height="10" rx="1"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('appName') }}</h1>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-5 sm:p-8 border border-slate-200/80 dark:border-slate-700/80">
        <slot />
      </div>
    </div>
  </div>
</template>

