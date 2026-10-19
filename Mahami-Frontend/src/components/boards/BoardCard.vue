<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const props = defineProps({
  board: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['edit', 'delete'])
const { t } = useI18n()

const columnsCount = computed(() => {
  const count = props.board.lists_count ?? props.board.lists?.length ?? 0
  return t('columnsCount', { count })
})
</script>

<template>
  <div
    class="group relative flex flex-col justify-between p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-200"
  >
    <!-- Card Header / Title -->
    <div>
      <div class="flex items-start justify-between gap-3 mb-2">
        <router-link
          :to="{ name: 'board-details', params: { id: board.id } }"
          class="font-semibold text-base text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 line-clamp-1 transition-colors"
        >
          {{ board.title }}
        </router-link>

        <!-- Quick actions -->
        <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
          <button
            class="p-1 rounded-md text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            :title="t('editBoard')"
            @click.stop="emit('edit', board)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
            </svg>
          </button>
          <button
            class="p-1 rounded-md text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
            :title="t('deleteBoard')"
            @click.stop="emit('delete', board)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Description -->
      <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 min-h-8 mb-4">
        {{ board.description || '—' }}
      </p>
    </div>

    <!-- Card Footer -->
    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-700/60 text-xs text-slate-500 dark:text-slate-400">
      <span class="inline-flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="18" rx="1" />
          <rect x="14" y="3" width="7" height="18" rx="1" />
        </svg>
        {{ columnsCount }}
      </span>

      <router-link
        :to="{ name: 'board-details', params: { id: board.id } }"
        class="font-medium text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1"
      >
        <span>Open</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="m9 18 6-6-6-6" />
        </svg>
      </router-link>
    </div>
  </div>
</template>

