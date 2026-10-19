<script setup>
import { useI18n } from '@/composables/useI18n'
import AppButton from '@/components/common/AppButton.vue'

const { t } = useI18n()
</script>

<template>
  <div class="min-h-[70vh] flex flex-col items-center justify-center p-6 text-center">
    <div class="text-7xl font-extrabold text-blue-600 dark:text-blue-500 mb-4 tracking-tight">
      404
    </div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
      Page Not Found
    </h1>
    <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-sm">
      The page you are looking for does not exist or has been moved.
    </p>
    <router-link :to="{ name: 'boards' }">
      <AppButton variant="primary">
        {{ t('boards') }}
      </AppButton>
    </router-link>
  </div>
</template>
