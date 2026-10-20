<script setup>
import { ref, computed, nextTick } from 'vue'
import { useI18n } from '@/composables/useI18n'

const props = defineProps({
  list: {
    type: Object,
    required: true,
  },
  taskCount: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['update-title', 'delete'])
const { t } = useI18n()

const isEditing = ref(false)
const titleInput = ref('')
const inputEl = ref(null)

const dotColor = computed(() => {
  const title = (props.list.title || '').toLowerCase()
  if (title.includes('done') || title.includes('مكتمل') || title.includes('منجز')) {
    return 'bg-emerald-500'
  }
  if (title.includes('prog') || title.includes('تنفيذ') || title.includes('عمل') || title.includes('review')) {
    return 'bg-amber-500'
  }
  return 'bg-blue-500'
})

function startEdit() {
  titleInput.value = props.list.title
  isEditing.value = true
  nextTick(() => {
    inputEl.value?.focus()
    inputEl.value?.select()
  })
}

function saveEdit() {
  if (!isEditing.value) return
  const trimmed = titleInput.value.trim()
  if (trimmed && trimmed !== props.list.title) {
    emit('update-title', trimmed)
  }
  isEditing.value = false
}

function cancelEdit() {
  isEditing.value = false
}
</script>

<template>
  <div class="flex items-center justify-between gap-2 px-4 py-3.5 border-b border-slate-200/80 dark:border-slate-800 select-none">
    <!-- Drag handle + Title -->
    <div class="flex items-center gap-2 flex-1 min-w-0">
      <div
        class="column-drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-0.5 rounded transition-colors"
        title="Drag to reorder column"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="5" r="1" />
          <circle cx="9" cy="12" r="1" />
          <circle cx="9" cy="19" r="1" />
          <circle cx="15" cy="5" r="1" />
          <circle cx="15" cy="12" r="1" />
          <circle cx="15" cy="19" r="1" />
        </svg>
      </div>

      <!-- Dot indicator -->
      <span class="w-2 h-2 rounded-full shrink-0" :class="dotColor" />

      <div v-if="!isEditing" class="flex items-center gap-2 truncate cursor-pointer group" @click="startEdit">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
          {{ list.title }}
        </h3>
        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold shrink-0">
          {{ taskCount }}
        </span>
      </div>

      <input
        v-else
        ref="inputEl"
        v-model="titleInput"
        class="text-sm font-bold px-2 py-1 rounded-lg border-2 border-blue-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white w-full outline-none shadow-xs"
        @blur="saveEdit"
        @keydown.enter="saveEdit"
        @keydown.esc="cancelEdit"
      />
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-0.5 shrink-0">
      <button
        v-if="!isEditing"
        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-white dark:hover:bg-slate-800 transition-colors cursor-pointer"
        :title="t('editColumn')"
        @click="startEdit"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
        </svg>
      </button>

      <button
        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer"
        :title="t('deleteColumn')"
        @click="emit('delete', list)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
        </svg>
      </button>
    </div>
  </div>
</template>

