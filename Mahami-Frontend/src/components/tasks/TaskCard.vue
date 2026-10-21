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

const priorityConfig = {
  low: {
    badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
    dot: 'bg-emerald-500',
  },
  medium: {
    badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    dot: 'bg-amber-500',
  },
  high: {
    badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 border-rose-200 dark:border-rose-800',
    dot: 'bg-rose-500',
  },
}

const currentPriority = computed(() => {
  return priorityConfig[props.task.priority] || priorityConfig.medium
})

const formattedDueDate = computed(() => {
  if (!props.task.due_date) return null
  return props.task.due_date
})

const isOverdue = computed(() => {
  if (!props.task.due_date) return false
  const today = new Date().toISOString().slice(0, 10)
  return props.task.due_date < today
})

const isDueToday = computed(() => {
  if (!props.task.due_date) return false
  const today = new Date().toISOString().slice(0, 10)
  return props.task.due_date === today
})

const checklistSummary = computed(() => {
  if (props.task.checklist_summary && props.task.checklist_summary.total > 0) {
    return props.task.checklist_summary
  }
  if (props.task.checklists && props.task.checklists.length > 0) {
    let total = 0
    let completed = 0
    props.task.checklists.forEach((ch) => {
      const items = ch.items || []
      total += items.length
      completed += items.filter((i) => i.is_completed).length
    })
    return total > 0 ? { total, completed } : null
  }
  return null
})

const attachmentsCount = computed(() => {
  return props.task.attachments?.length || 0
})

const assignees = computed(() => {
  return props.task.assignees || []
})

const labels = computed(() => {
  return props.task.labels || []
})
</script>

<template>
  <div
    :data-task-id="task.id"
    class="task-item group relative bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/90 dark:border-slate-700/80 shadow-2xs hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-0.5 transition-all duration-150 cursor-grab active:cursor-grabbing select-none space-y-2.5"
    @click="emit('click', task)"
  >
    <!-- Labels -->
    <div v-if="labels.length > 0" class="flex flex-wrap gap-1.5">
      <span
        v-for="lbl in labels"
        :key="lbl.id"
        class="text-[10px] font-bold px-2 py-0.5 rounded-md text-white shadow-2xs truncate max-w-32"
        :style="{ backgroundColor: lbl.color || '#3b82f6' }"
      >
        {{ lbl.name }}
      </span>
    </div>

    <!-- Priority badge + quick actions -->
    <div class="flex items-center justify-between gap-2">
      <span
        :class="[
          'inline-flex items-center gap-1.5 text-[11px] font-bold px-2 py-0.5 rounded-lg border',
          currentPriority.badge,
        ]"
      >
        <span class="w-1.5 h-1.5 rounded-full" :class="currentPriority.dot" />
        {{ t(task.priority) }}
      </span>

      <div class="flex items-center gap-1 opacity-80 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
        <button
          class="p-1 rounded-lg text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
          :title="t('editTask')"
          @click.stop="emit('edit', task)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
          </svg>
        </button>
        <button
          class="p-1 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer"
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
    <h4 class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 leading-snug line-clamp-2">
      {{ task.title }}
    </h4>

    <!-- Description preview -->
    <p
      v-if="task.description"
      class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed"
    >
      {{ task.description }}
    </p>

    <!-- Footer metadata (Due Date, Checklists, Attachments, Assignees) -->
    <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-2 text-[11px]">
      <div class="flex items-center gap-2.5 flex-wrap">
        <!-- Due Date -->
        <div
          v-if="formattedDueDate"
          class="flex items-center gap-1 font-medium"
          :class="{
            'text-rose-600 dark:text-rose-400 font-bold': isOverdue,
            'text-amber-600 dark:text-amber-400 font-bold': isDueToday,
            'text-slate-500 dark:text-slate-400': !isOverdue && !isDueToday
          }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          <span>{{ formattedDueDate }}</span>
        </div>

        <!-- Checklist progress -->
        <div
          v-if="checklistSummary"
          class="flex items-center gap-1 font-medium text-slate-600 dark:text-slate-300"
          :class="{ 'text-emerald-600 dark:text-emerald-400 font-bold': checklistSummary.completed === checklistSummary.total }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 11 12 14 22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
          </svg>
          <span>{{ checklistSummary.completed }}/{{ checklistSummary.total }}</span>
        </div>

        <!-- Attachments icon -->
        <div
          v-if="attachmentsCount > 0"
          class="flex items-center gap-1 font-medium text-slate-500 dark:text-slate-400"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48" />
          </svg>
          <span>{{ attachmentsCount }}</span>
        </div>

        <!-- Comments count badge -->
        <div
          v-if="task.comments_count > 0"
          class="flex items-center gap-1 font-medium text-slate-500 dark:text-slate-400"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
          </svg>
          <span>{{ task.comments_count }}</span>
        </div>
      </div>

      <!-- Assignees Avatars -->
      <div v-if="assignees.length > 0" class="flex items-center -space-x-1.5 rtl:space-x-reverse shrink-0">
        <div
          v-for="user in assignees.slice(0, 3)"
          :key="user.id"
          class="w-6 h-6 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-[10px] uppercase border-2 border-white dark:border-slate-800 shadow-2xs"
          :title="user.name"
        >
          {{ user.name?.charAt(0) || 'U' }}
        </div>
        <div
          v-if="assignees.length > 3"
          class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-[9px] border-2 border-white dark:border-slate-800 shadow-2xs"
        >
          +{{ assignees.length - 3 }}
        </div>
      </div>
    </div>
  </div>
</template>
