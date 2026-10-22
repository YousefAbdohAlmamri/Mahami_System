<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from '@/composables/useI18n'
import analyticsService from '@/services/analyticsService'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  boardId: {
    type: [Number, String],
    default: null,
  },
  workspaceId: {
    type: [Number, String],
    default: null,
  },
  title: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close'])
const { t } = useI18n()

const analytics = ref(null)
const isLoading = ref(true)
const error = ref(null)

const modalTitle = computed(() => {
  if (props.title) return props.title
  return props.boardId ? t('boardAnalytics') : t('workspaceAnalytics')
})

const completionPct = computed(() => {
  if (!analytics.value) return 0
  return analytics.value.completion_rate ?? analytics.value.overall_completion_rate ?? 0
})

const priorityData = computed(() => {
  if (!analytics.value?.priority_breakdown) return []
  const pb = analytics.value.priority_breakdown
  const total = (pb.high || 0) + (pb.medium || 0) + (pb.low || 0)
  return [
    {
      key: 'high',
      label: t('high'),
      count: pb.high || 0,
      pct: total > 0 ? Math.round(((pb.high || 0) / total) * 100) : 0,
      color: 'bg-rose-500',
      badge: 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60',
    },
    {
      key: 'medium',
      label: t('medium'),
      count: pb.medium || 0,
      pct: total > 0 ? Math.round(((pb.medium || 0) / total) * 100) : 0,
      color: 'bg-amber-500',
      badge: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60',
    },
    {
      key: 'low',
      label: t('low'),
      count: pb.low || 0,
      pct: total > 0 ? Math.round(((pb.low || 0) / total) * 100) : 0,
      color: 'bg-emerald-500',
      badge: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60',
    },
  ]
})

onMounted(async () => {
  isLoading.value = true
  error.value = null
  try {
    if (props.boardId) {
      const res = await analyticsService.getBoardAnalytics(props.boardId)
      analytics.value = res.data
    } else if (props.workspaceId) {
      const res = await analyticsService.getWorkspaceAnalytics(props.workspaceId)
      analytics.value = res.data
    }
  } catch (err) {
    error.value = err.response?.data?.message || t('genericError')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <AppModal size="xl" :title="modalTitle" @close="emit('close')">
    <div v-if="isLoading" class="py-16 text-center text-xs text-slate-400">
      {{ t('loading') }}
    </div>

    <div v-else-if="error" class="py-12 text-center text-rose-500 text-sm">
      {{ error }}
    </div>

    <div v-else-if="analytics" class="space-y-6">
      <!-- 4 Top KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total Tasks -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('totalTasks') }}</span>
            <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="11" rx="1.5" />
                <rect x="3" y="17" width="7" height="4" rx="1" />
                <rect x="14" y="3" width="7" height="5" rx="1" />
                <rect x="14" y="11" width="7" height="10" rx="1.5" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-slate-900 dark:text-white">
            {{ analytics.total_tasks }}
          </div>
        </div>

        <!-- Completed Tasks -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('completedTasks') }}</span>
            <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
            {{ analytics.completed_tasks }}
          </div>
        </div>

        <!-- Completion Rate -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('completionRate') }}</span>
            <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
            {{ completionPct }}%
          </div>
        </div>

        <!-- Overdue Tasks -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1 shadow-2xs">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('overdueTasks') }}</span>
            <div class="w-7 h-7 rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-black text-rose-600 dark:text-rose-400">
            {{ analytics.overdue_tasks_count }}
          </div>
        </div>
      </div>

      <!-- Priority Distribution -->
      <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-3 shadow-2xs">
        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="20" x2="18" y2="10" />
            <line x1="12" y1="20" x2="12" y2="4" />
            <line x1="6" y1="20" x2="6" y2="14" />
          </svg>
          {{ t('priorityDistribution') }}
        </h4>

        <div class="space-y-2.5">
          <div v-for="item in priorityData" :key="item.key" class="space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full" :class="item.color" />
                {{ item.label }}
              </span>
              <span>{{ item.count }} ({{ item.pct }}%)</span>
            </div>
            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="item.color"
                :style="{ width: `${item.pct}%` }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Status Breakdown (for board) OR Board Summaries (for workspace) -->
      <div
        v-if="analytics.status_breakdown && analytics.status_breakdown.length > 0"
        class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-3 shadow-2xs"
      >
        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3v18h18" />
            <path d="m19 9-5 5-4-4-3 3" />
          </svg>
          {{ t('statusDistribution') }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div
            v-for="st in analytics.status_breakdown"
            :key="st.list_id"
            class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700 flex items-center justify-between"
          >
            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">
              {{ st.list_title }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 shadow-2xs">
              {{ st.task_count }}
            </span>
          </div>
        </div>
      </div>

      <!-- Member Workload (if available) -->
      <div
        v-if="analytics.member_workload && analytics.member_workload.length > 0"
        class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-3 shadow-2xs"
      >
        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          {{ t('memberWorkload') }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="mem in analytics.member_workload"
            :key="mem.user_id"
            class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700 flex items-center justify-between gap-3"
          >
            <div class="flex items-center gap-2.5 truncate">
              <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center text-xs font-bold uppercase shadow-2xs shrink-0">
                {{ (mem.name || 'U').charAt(0) }}
              </div>
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ mem.name }}</span>
            </div>
            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 shrink-0">
              {{ mem.assigned_tasks }} {{ t('totalTasks') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end">
        <AppButton variant="secondary" size="sm" @click="emit('close')">
          {{ t('close') }}
        </AppButton>
      </div>
    </template>
  </AppModal>
</template>
