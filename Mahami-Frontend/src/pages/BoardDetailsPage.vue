<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import Sortable from 'sortablejs'
import { useBoardStore } from '@/stores/boardStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'

import BoardColumn from '@/components/lists/BoardColumn.vue'
import ColumnForm from '@/components/lists/ColumnForm.vue'
import TaskForm from '@/components/tasks/TaskForm.vue'
import TaskDetailsModal from '@/components/tasks/TaskDetailsModal.vue'
import BoardForm from '@/components/boards/BoardForm.vue'
import BoardTeamsModal from '@/components/teams/BoardTeamsModal.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import LoadingIndicator from '@/components/common/LoadingIndicator.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
})

const router = useRouter()
const boardStore = useBoardStore()
const { t } = useI18n()
const toast = useToast()

const columnsContainer = ref(null)
let columnSortableInstance = null

// Modals State
const showColumnModal = ref(false)
const isSubmittingColumn = ref(false)

const showTaskModal = ref(false)
const targetListIdForNewTask = ref(null)
const selectedTaskForEdit = ref(null)
const isSubmittingTask = ref(false)

const showTaskDetailsModal = ref(false)
const selectedTaskForDetails = ref(null)

const showEditBoardModal = ref(false)
const isSubmittingBoard = ref(false)
const showBoardTeamsModal = ref(false)

const showDeleteConfirm = ref(false)
const deleteAction = ref({ type: '', item: null, title: '', message: '' })
const isDeleting = ref(false)

onMounted(async () => {
  await loadBoard()
})

onBeforeUnmount(() => {
  if (columnSortableInstance) {
    columnSortableInstance.destroy()
    columnSortableInstance = null
  }
})

async function loadBoard() {
  try {
    await boardStore.fetchBoard(props.id)
    await nextTick()
    setupColumnSortable()
  } catch (err) {
    toast.error(err.response?.data?.message || t('networkError'))
    router.push({ name: 'boards' })
  }
}

function setupColumnSortable() {
  if (columnSortableInstance) {
    columnSortableInstance.destroy()
    columnSortableInstance = null
  }

  if (columnsContainer.value) {
    columnSortableInstance = new Sortable(columnsContainer.value, {
      handle: '.column-drag-handle',
      animation: 180,
      ghostClass: 'sortable-ghost',
      draggable: '.board-column-item',
      onEnd: async (evt) => {
        if (evt.oldIndex === evt.newIndex) return

        // Compute ordered IDs from DOM
        const columnEls = Array.from(columnsContainer.value.querySelectorAll('.board-column-item'))
        const orderedIds = columnEls.map((el) => parseInt(el.getAttribute('data-list-id'), 10))

        try {
          await boardStore.reorderLists(props.id, orderedIds)
          toast.success(t('reorderedSuccessfully'))
        } catch (err) {
          toast.error(err.response?.data?.message || t('genericError'))
          await loadBoard()
        }
      },
    })
  }
}

// ── Column Handlers ───────────────────────────────────────────
function openAddColumnModal() {
  showColumnModal.value = true
}

async function handleSaveColumn(data) {
  isSubmittingColumn.value = true
  try {
    await boardStore.createList(props.id, data)
    toast.success(t('createdSuccessfully'))
    showColumnModal.value = false
    await nextTick()
    setupColumnSortable()
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isSubmittingColumn.value = false
  }
}

async function handleUpdateColumnTitle({ listId, title }) {
  try {
    await boardStore.updateList(listId, { title })
    toast.success(t('savedSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
    await loadBoard()
  }
}

function confirmDeleteColumn(list) {
  deleteAction.value = {
    type: 'column',
    item: list,
    title: t('deleteColumn'),
    message: t('deleteColumnConfirm'),
  }
  showDeleteConfirm.value = true
}

// ── Task Handlers ─────────────────────────────────────────────
function openAddTaskModal(listId) {
  targetListIdForNewTask.value = listId
  selectedTaskForEdit.value = null
  showTaskModal.value = true
}

function openEditTaskModal(task) {
  selectedTaskForEdit.value = task
  showTaskModal.value = true
}

function openTaskDetails(task) {
  selectedTaskForDetails.value = task
  showTaskDetailsModal.value = true
}

async function handleSaveTask(data) {
  isSubmittingTask.value = true
  try {
    if (selectedTaskForEdit.value) {
      const updated = await boardStore.updateTask(selectedTaskForEdit.value.id, data)
      toast.success(t('savedSuccessfully'))
      if (selectedTaskForDetails.value?.id === updated.id) {
        selectedTaskForDetails.value = updated
      }
    } else {
      await boardStore.createTask(targetListIdForNewTask.value, data)
      toast.success(t('createdSuccessfully'))
    }
    showTaskModal.value = false
    selectedTaskForEdit.value = null
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isSubmittingTask.value = false
  }
}

function confirmDeleteTask(task) {
  deleteAction.value = {
    type: 'task',
    item: task,
    title: t('deleteTask'),
    message: t('deleteTaskConfirm'),
  }
  showDeleteConfirm.value = true
}

// ── Task Drag & Drop ──────────────────────────────────────────
async function handleTaskDrop({ taskId, fromListId, toListId, oldIndex, newIndex, evt }) {
  // 1. Intra-column reorder
  if (fromListId === toListId) {
    if (oldIndex === newIndex) return

    const list = boardStore.currentBoard?.lists?.find((l) => l.id === fromListId)
    if (!list?.tasks) return

    // Calculate new ordered IDs based on DOM order
    const taskEls = Array.from(evt.to.querySelectorAll('.task-item'))
    const orderedIds = taskEls.map((el) => parseInt(el.getAttribute('data-task-id'), 10))

    try {
      await boardStore.reorderTasks(fromListId, orderedIds)
      toast.success(t('reorderedSuccessfully'))
    } catch (err) {
      toast.error(err.response?.data?.message || t('genericError'))
      await loadBoard()
    }
    return
  }

  // 2. Inter-column move
  try {
    await boardStore.moveTask(taskId, toListId, newIndex)
    toast.success(t('movedSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
    await loadBoard()
  }
}

// ── Board Actions ─────────────────────────────────────────────
async function handleUpdateBoard(data) {
  isSubmittingBoard.value = true
  try {
    await boardStore.updateBoard(props.id, data)
    toast.success(t('savedSuccessfully'))
    showEditBoardModal.value = false
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isSubmittingBoard.value = false
  }
}

function confirmDeleteBoard() {
  deleteAction.value = {
    type: 'board',
    item: boardStore.currentBoard,
    title: t('deleteBoard'),
    message: t('deleteBoardConfirm'),
  }
  showDeleteConfirm.value = true
}

// ── Generic Delete Execution ─────────────────────────────────
async function executeDelete() {
  isDeleting.value = true
  try {
    const { type, item } = deleteAction.value
    if (type === 'board') {
      await boardStore.deleteBoard(item.id)
      toast.success(t('deletedSuccessfully'))
      showDeleteConfirm.value = false
      router.push({ name: 'boards' })
      return
    } else if (type === 'column') {
      await boardStore.deleteList(item.id)
      toast.success(t('deletedSuccessfully'))
      await nextTick()
      setupColumnSortable()
    } else if (type === 'task') {
      await boardStore.deleteTask(item.id)
      toast.success(t('deletedSuccessfully'))
      if (showTaskDetailsModal.value && selectedTaskForDetails.value?.id === item.id) {
        showTaskDetailsModal.value = false
      }
    }
    showDeleteConfirm.value = false
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="h-full flex flex-col overflow-hidden bg-slate-50/50 dark:bg-slate-950/50">
    <!-- Top Board Toolbar -->
    <div
      v-if="boardStore.currentBoard"
      class="min-h-14 py-2 px-3 sm:px-6 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2 sm:gap-4 select-none shrink-0"
    >
      <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
        <router-link
          :to="{ name: 'boards' }"
          class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0"
          :title="t('boards')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m15 18-6-6 6-6" />
          </svg>
        </router-link>

        <div class="min-w-0 flex-1">
          <h2 class="text-sm sm:text-lg font-bold text-slate-900 dark:text-white truncate">
            {{ boardStore.currentBoard.title }}
          </h2>
          <p
            v-if="boardStore.currentBoard.description"
            class="text-xs text-slate-500 dark:text-slate-400 truncate hidden md:block"
          >
            {{ boardStore.currentBoard.description }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <!-- Board Teams & Permissions -->
        <button
          class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
          :title="t('boardTeams')"
          @click="showBoardTeamsModal = true"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          <span class="hidden md:inline">{{ t('teams') }}</span>
        </button>

        <button
          class="p-1.5 sm:p-2 rounded-lg text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
          :title="t('editBoard')"
          @click="showEditBoardModal = true"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
          </svg>
        </button>

        <button
          class="p-1.5 sm:p-2 rounded-lg text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer"
          :title="t('deleteBoard')"
          @click="confirmDeleteBoard"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
          </svg>
        </button>

        <div class="h-4 sm:h-5 w-px bg-slate-200 dark:bg-slate-800 mx-0.5 sm:mx-1" />

        <AppButton variant="primary" size="sm" class="px-2.5 sm:px-3" @click="openAddColumnModal">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14" />
          </svg>
          <span class="hidden sm:inline">{{ t('addColumn') }}</span>
        </AppButton>
      </div>
    </div>

    <!-- Board Content (Horizontal Scroll) -->
    <div
      v-if="boardStore.isLoading"
      class="flex-1 flex items-center justify-center"
    >
      <LoadingIndicator :text="t('loading')" />
    </div>

    <div
      v-else-if="boardStore.currentBoard"
      class="flex-1 overflow-x-auto overflow-y-hidden p-3 sm:p-6"
    >
      <div
        ref="columnsContainer"
        class="inline-flex items-start gap-3 sm:gap-4 h-full"
      >
        <BoardColumn
          v-for="list in boardStore.currentBoard.lists || []"
          :key="list.id"
          :list="list"
          @update-title="handleUpdateColumnTitle"
          @delete-list="confirmDeleteColumn"
          @add-task="openAddTaskModal"
          @click-task="openTaskDetails"
          @edit-task="openEditTaskModal"
          @delete-task="confirmDeleteTask"
          @task-drop="handleTaskDrop"
        />

        <!-- Add column placeholder / action -->
        <button
          class="w-[84vw] max-w-[280px] sm:w-80 shrink-0 h-24 sm:h-28 rounded-3xl border-2 border-dashed border-slate-300 dark:border-slate-800 hover:border-blue-500 dark:hover:border-blue-500 bg-slate-100/50 dark:bg-slate-900/40 hover:bg-white dark:hover:bg-slate-900 text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 flex items-center justify-center gap-2 font-semibold text-sm transition-all duration-150 shadow-2xs cursor-pointer"
          @click="openAddColumnModal"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14" />
          </svg>
          <span>{{ t('addColumn') }}</span>
        </button>
      </div>
    </div>

    <!-- Modals -->
    <ColumnForm
      v-if="showColumnModal"
      :loading="isSubmittingColumn"
      @save="handleSaveColumn"
      @close="showColumnModal = false"
    />

    <TaskForm
      v-if="showTaskModal"
      :task="selectedTaskForEdit"
      :loading="isSubmittingTask"
      @save="handleSaveTask"
      @close="showTaskModal = false"
    />

    <TaskDetailsModal
      v-if="showTaskDetailsModal && selectedTaskForDetails"
      :task="selectedTaskForDetails"
      @edit="(t) => { showTaskDetailsModal = false; openEditTaskModal(t); }"
      @delete="(t) => { confirmDeleteTask(t); }"
      @close="showTaskDetailsModal = false"
    />

    <BoardForm
      v-if="showEditBoardModal"
      :board="boardStore.currentBoard"
      :loading="isSubmittingBoard"
      @save="handleUpdateBoard"
      @close="showEditBoardModal = false"
    />

    <!-- Board Teams & Permissions Modal -->
    <BoardTeamsModal
      v-if="showBoardTeamsModal && boardStore.currentBoard"
      :board="boardStore.currentBoard"
      @close="showBoardTeamsModal = false"
    />

    <ConfirmDialog
      v-if="showDeleteConfirm"
      :title="deleteAction.title"
      :message="deleteAction.message"
      :confirm-label="t('delete')"
      :loading="isDeleting"
      @confirm="executeDelete"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

