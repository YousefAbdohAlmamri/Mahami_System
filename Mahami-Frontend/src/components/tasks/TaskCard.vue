<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['click', 'edit', 'delete'])
const { t } = useI18n()

const priorityColors = {
  low: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
  medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800',
  high: 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border-red-200 dark:border-red-800',
}

const formattedDueDate = computed(() => {
  if (!props.task.due_date) return null
  return props.task.due_date
})

const isOverdue = computed(() => {
  if (!props.task.due_date) return false
  const today = new Date().toISOString().slice(0, 10)
  return props.task.due_date < today
})
</script>

<template>
  <div
    :data-task-id="task.id"
    class="task-item group relative bg-white dark:bg-slate-800 p-3.5 rounded-xl border border-slate-200/90 dark:border-slate-700/80 shadow-xs hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-150 cursor-grab active:cursor-grabbing select-none"
    @click="emit('click', task)"
  >
    <!-- Priority badge + quick actions -->
    <div class="flex items-center justify-between gap-2 mb-2">
      <span
        :class="[
          'text-[11px] font-semibold px-2 py-0.5 rounded-md border',
          priorityColors[task.priority] || priorityColors.medium,
        ]"
      >
        {{ t(task.priority) }}
      </span>

      <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <button
          class="p-1 rounded text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
          :title="t('editTask')"
          @click.stop="emit('edit', task)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
          </svg>
        </button>
        <button
          class="p-1 rounded text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
          :title="t('deleteTask')"
          @click.stop="emit('delete', task)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Title -->
    <h4 class="text-sm font-medium text-slate-900 dark:text-slate-100 mb-1 leading-snug line-clamp-2">
      {{ task.title }}
    </h4>

    <!-- Description preview -->
    <p
      v-if="task.description"
      class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-2.5"
    >
      {{ task.description }}
    </p>

    <!-- Footer metadata (Due Date) -->
    <div
      v-if="formattedDueDate"
      class="flex items-center gap-1.5 text-[11px] pt-2 border-t border-slate-100 dark:border-slate-700/60"
      :class="isOverdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-slate-500 dark:text-slate-400'"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
        <line x1="16" y1="2" x2="16" y2="6" />
        <line x1="8" y1="2" x2="8" y2="6" />
        <line x1="3" y1="10" x2="21" y2="10" />
      </svg>
      <span>{{ formattedDueDate }}</span>
    </div>
  </div>
</template>
