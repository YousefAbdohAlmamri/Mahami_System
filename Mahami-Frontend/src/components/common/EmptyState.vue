<script setup>
import AppButton from './AppButton.vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  actionText: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['action'])
</script>

<template>
  <div class="flex flex-col items-center justify-center p-8 text-center max-w-md mx-auto">
    <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-500 mb-4">
      <slot name="icon">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
          <rect x="3" y="3" width="7" height="9" rx="1" />
          <rect x="14" y="3" width="7" height="5" rx="1" />
          <rect x="14" y="12" width="7" height="9" rx="1" />
          <rect x="3" y="16" width="7" height="5" rx="1" />
        </svg>
      </slot>
    </div>

    <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1">
      {{ title }}
    </h3>

    <p v-if="description" class="text-sm text-slate-500 dark:text-slate-400 mb-5">
      {{ description }}
    </p>

    <AppButton
      v-if="actionText"
      variant="primary"
      size="sm"
      @click="emit('action')"
    >
      <slot name="action-icon">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14" />
        </svg>
      </slot>
      {{ actionText }}
    </AppButton>
  </div>
</template>
