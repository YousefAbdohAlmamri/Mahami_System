<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import Sortable from 'sortablejs'
import { useI18n } from '@/composables/useI18n'
import ColumnHeader from './ColumnHeader.vue'
import TaskCard from '@/components/tasks/TaskCard.vue'

const props = defineProps({
  list: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'update-title',
  'delete-list',
  'add-task',
  'click-task',
  'edit-task',
  'delete-task',
  'task-drop',
])

const { t } = useI18n()
const tasksContainer = ref(null)
let sortableInstance = null

onMounted(() => {
  if (tasksContainer.value) {
    sortableInstance = new Sortable(tasksContainer.value, {
      group: 'board-tasks',
      animation: 180,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      handle: '.task-item',
      onEnd: (evt) => {
        const taskId = parseInt(evt.item.getAttribute('data-task-id'), 10)
        const fromListId = parseInt(evt.from.getAttribute('data-list-id'), 10)
        const toListId = parseInt(evt.to.getAttribute('data-list-id'), 10)
        const newIndex = evt.newIndex
        const oldIndex = evt.oldIndex

        if (isNaN(taskId)) return

        emit('task-drop', {
          taskId,
          fromListId,
          toListId,
          oldIndex,
          newIndex,
          evt,
        })
      },
    })
  }
})

onBeforeUnmount(() => {
  if (sortableInstance) {
    sortableInstance.destroy()
    sortableInstance = null
  }
})
</script>

<template>
  <div
    :data-list-id="list.id"
    class="board-column-item flex flex-col w-[84vw] max-w-[290px] sm:w-80 sm:max-w-none shrink-0 bg-slate-100/90 dark:bg-slate-900/90 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs max-h-[calc(100vh-8.5rem)] sm:max-h-full overflow-hidden transition-shadow"
  >
    <!-- Header -->
    <ColumnHeader
      :list="list"
      :task-count="list.tasks?.length || 0"
      @update-title="(title) => emit('update-title', { listId: list.id, title })"
      @delete="() => emit('delete-list', list)"
    />

    <!-- Tasks List (Scrollable) -->
    <div
      ref="tasksContainer"
      :data-list-id="list.id"
      class="flex-1 overflow-y-auto px-3 py-3 flex flex-col gap-2.5 min-h-16"
    >
      <TaskCard
        v-for="task in list.tasks || []"
        :key="task.id"
        :task="task"
        @click="(t) => emit('click-task', t)"
        @edit="(t) => emit('edit-task', t)"
        @delete="(t) => emit('delete-task', t)"
      />

      <!-- Empty list hint -->
      <div
        v-if="!list.tasks || list.tasks.length === 0"
        class="py-8 text-center text-xs text-slate-400 dark:text-slate-500 italic pointer-events-none select-none flex flex-col items-center gap-1.5"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10" />
          <line x1="8" y1="12" x2="16" y2="12" />
        </svg>
        <span>{{ t('noTasks') }}</span>
      </div>
    </div>

    <!-- Add Task button -->
    <div class="p-3 pt-1 border-t border-slate-200/60 dark:border-slate-800 bg-slate-100/50 dark:bg-slate-900/50">
      <button
        class="w-full flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-white dark:hover:bg-slate-800 transition-all border border-dashed border-slate-300 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-500 shadow-2xs hover:shadow-xs cursor-pointer active:scale-[0.98]"
        @click="emit('add-task', list.id)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M12 5v14M5 12h14" />
        </svg>
        {{ t('addTask') }}
      </button>
    </div>
  </div>
</template>

