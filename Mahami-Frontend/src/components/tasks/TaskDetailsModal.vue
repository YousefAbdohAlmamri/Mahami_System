<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'edit', 'delete'])
const { t } = useI18n()

const priorityColors = {
  low: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
  medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800',
  high: 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border-red-200 dark:border-red-800',
}

const isOverdue = computed(() => {
  if (!props.task.due_date) return false
  const today = new Date().toISOString().slice(0, 10)
  return props.task.due_date < today
})
</script>

<template>
  <AppModal size="md" :title="t('taskDetails')" @close="emit('close')">
    <div class="space-y-4">
      <!-- Title -->
      <div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-relaxed">
          {{ task.title }}
        </h3>
      </div>

      <!-- Metadata Badges -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- Priority -->
        <span
          :class="[
            'text-xs font-semibold px-2.5 py-1 rounded-md border',
            priorityColors[task.priority] || priorityColors.medium,
          ]"
        >
          {{ t('priority') }}: {{ t(task.priority) }}
        </span>

        <!-- Due Date -->
        <span
          v-if="task.due_date"
          class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-md border"
          :class="
            isOverdue
              ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800'
              : 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
          "
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          {{ task.due_date }}
        </span>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">
          {{ t('taskDescription') }}
        </label>
        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap min-h-20">
          {{ task.description || '—' }}
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-between">
        <AppButton
          variant="danger"
          size="sm"
          @click="emit('delete', task)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
          </svg>
          {{ t('delete') }}
        </AppButton>

        <div class="flex items-center gap-2">
          <AppButton
            variant="secondary"
            size="sm"
            @click="emit('close')"
          >
            {{ t('close') }}
          </AppButton>
          <AppButton
            variant="primary"
            size="sm"
            @click="emit('edit', task)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
            </svg>
            {{ t('editTask') }}
          </AppButton>
        </div>
      </div>
    </template>
  </AppModal>
</template>
