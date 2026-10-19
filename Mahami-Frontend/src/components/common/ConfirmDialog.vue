<script setup>
import AppModal from './AppModal.vue'
import AppButton from './AppButton.vue'

defineProps({
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Delete' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <AppModal size="sm" :title="title" :close-on-backdrop="!loading" @close="emit('cancel')">
    <p class="text-sm text-slate-600 dark:text-slate-300">
      {{ message }}
    </p>

    <template #footer>
      <div class="flex justify-end gap-3">
        <AppButton variant="secondary" :disabled="loading" @click="emit('cancel')">
          Cancel
        </AppButton>
        <AppButton variant="danger" :loading="loading" @click="emit('confirm')">
          {{ confirmLabel }}
        </AppButton>
      </div>
    </template>
  </AppModal>
</template>

