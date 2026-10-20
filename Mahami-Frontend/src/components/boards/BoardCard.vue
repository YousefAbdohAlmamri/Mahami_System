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
    class="group relative flex flex-col justify-between p-6 bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs hover:shadow-xl hover:shadow-blue-500/5 hover:border-blue-400/80 dark:hover:border-blue-500/60 hover:-translate-y-1 transition-all duration-200 overflow-hidden"
  >
    <!-- Top decorative accent line -->
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 opacity-80 group-hover:opacity-100 transition-opacity" />

    <!-- Card Header / Title -->
    <div>
      <div class="flex items-start justify-between gap-3 mb-2.5">
        <router-link
          :to="{ name: 'board-details', params: { id: board.id } }"
          class="font-bold text-base sm:text-lg text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 line-clamp-1 transition-colors tracking-tight"
        >
          {{ board.title }}
        </router-link>

        <!-- Quick actions -->
        <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity shrink-0">
          <button
            class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
            :title="t('editBoard')"
            @click.stop="emit('edit', board)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
            </svg>
          </button>
          <button
            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer"
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
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 line-clamp-2 min-h-10 mb-4 leading-relaxed">
        {{ board.description || '—' }}
      </p>
    </div>

    <!-- Card Footer -->
    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700/60 text-xs text-slate-500 dark:text-slate-400">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700/60 font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="18" rx="1" />
          <rect x="14" y="3" width="7" height="18" rx="1" />
        </svg>
        {{ columnsCount }}
      </span>

      <router-link
        :to="{ name: 'board-details', params: { id: board.id } }"
        class="font-semibold text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 inline-flex items-center gap-1 group/btn"
      >
        <span>Open</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 rtl:rotate-180 group-hover/btn:translate-x-0.5 rtl:group-hover/btn:-translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="m9 18 6-6-6-6" />
        </svg>
      </router-link>
    </div>
  </div>
</template>

