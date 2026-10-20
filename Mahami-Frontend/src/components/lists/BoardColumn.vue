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
    class="board-column-item flex flex-col w-[84vw] max-w-[280px] sm:w-80 sm:max-w-none shrink-0 bg-slate-100/90 dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs max-h-[calc(100vh-8.5rem)] sm:max-h-full"
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
      class="flex-1 overflow-y-auto px-3 py-2.5 flex flex-col gap-2.5 min-h-16"
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
        class="py-6 text-center text-xs text-slate-400 dark:text-slate-500 italic pointer-events-none select-none"
      >
        {{ t('noTasks') }}
      </div>
    </div>

    <!-- Add Task button -->
    <div class="p-2.5 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
      <button
        class="w-full flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-white dark:hover:bg-slate-700/60 transition-colors border border-dashed border-slate-300 dark:border-slate-600"
        @click="emit('add-task', list.id)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14" />
        </svg>
        {{ t('addTask') }}
      </button>
    </div>
  </div>
</template>

