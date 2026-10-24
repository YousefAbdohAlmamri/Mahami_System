<script setup>
import { usePreferencesStore } from "@/stores/preferencesStore";
import { useI18n } from "@/composables/useI18n";

const prefs = usePreferencesStore();
const { t } = useI18n();
</script>

<template>
  <div
    class="min-h-dvh flex items-center justify-center transition-colors duration-200 px-4 pt-20 pb-8 sm:py-12 relative overflow-hidden"
    :dir="prefs.dir"
  >
    <!-- Background glow spheres -->
    <div
      class="pointer-events-none absolute -top-40 -start-40 w-72 h-72 sm:w-96 sm:h-96 bg-blue-500/10 dark:bg-blue-600/10 rounded-full blur-3xl"
    />
    <div
      class="pointer-events-none absolute -bottom-40 -end-40 w-72 h-72 sm:w-96 sm:h-96 bg-indigo-500/10 dark:bg-indigo-600/10 rounded-full blur-3xl"
    />

    <!-- Language & theme toggles (top corner) -->
    <div
      class="fixed top-3 end-3 sm:top-5 sm:end-6 flex items-center gap-2 z-10"
    >
      <button
        class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border border-slate-200/80 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition-all shadow-xs cursor-pointer active:scale-95"
        :title="t('language')"
        @click="prefs.toggleLocale()"
      >
        <span class="inline-flex items-center gap-1.5">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-3.5 h-3.5 opacity-60"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="2" y1="12" x2="22" y2="12" />
            <path
              d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
            />
          </svg>
          {{ prefs.locale === "ar" ? "English" : "العربية" }}
        </span>
      </button>

      <button
        class="p-2 rounded-xl bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border border-slate-200/80 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition-all shadow-xs cursor-pointer active:scale-95"
        :title="prefs.isDark ? t('lightMode') : t('darkMode')"
        @click="prefs.toggleTheme()"
      >
        <svg
          v-if="prefs.isDark"
          xmlns="http://www.w3.org/2000/svg"
          class="w-4 h-4 text-amber-400"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <circle cx="12" cy="12" r="5" />
          <path
            d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
          />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          class="w-4 h-4 text-slate-600"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
      </button>
    </div>

    <!-- Centered card -->
    <div class="w-full max-w-md relative z-10 min-w-0">
      <!-- Logo / brand -->
      <div class="text-center mb-6 sm:mb-8">
        <div
          class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-blue-500 mb-3.5 shadow-lg shadow-blue-500/25 ring-4 ring-white dark:ring-slate-800"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-7 h-7 text-white"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.25"
          >
            <rect x="3" y="3" width="7" height="11" rx="1.5" />
            <rect x="3" y="17" width="7" height="4" rx="1" />
            <rect x="14" y="3" width="7" height="5" rx="1" />
            <rect x="14" y="11" width="7" height="10" rx="1.5" />
          </svg>
        </div>
        <h1
          class="text-2xl sm:text-3xl font-extrabold tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 dark:from-blue-400 dark:via-indigo-300 dark:to-blue-300 bg-clip-text text-transparent"
        >
          {{ t("appName") }}
        </h1>
        <p
          class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto"
        >
          {{ t("tagline") }}
        </p>
      </div>

      <div
        class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-3xl shadow-xl p-5 min-[400px]:p-6 sm:p-8 border border-slate-200/80 dark:border-slate-700/80"
      >
        <slot />
      </div>
    </div>
  </div>
</template>
