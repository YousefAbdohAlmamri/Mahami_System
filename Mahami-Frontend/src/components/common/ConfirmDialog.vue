<script setup>
import { useI18n } from '@/composables/useI18n'
import AppModal from './AppModal.vue'
import AppButton from './AppButton.vue'

defineProps({
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Delete' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
const { t } = useI18n()
</script>

<template>
  <AppModal size="sm" :title="title" :close-on-backdrop="!loading" @close="emit('cancel')">
    <div class="flex items-start gap-3">
      <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
      </div>
      <p class="text-sm text-slate-600 dark:text-slate-300 pt-0.5 leading-relaxed">
        {{ message }}
      </p>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2.5">
        <AppButton variant="secondary" :disabled="loading" @click="emit('cancel')">
          {{ t('cancel') }}
        </AppButton>
        <AppButton variant="danger" :loading="loading" @click="emit('confirm')">
          {{ confirmLabel }}
        </AppButton>
      </div>
    </template>
  </AppModal>
</template>

