<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useBoardStore } from '@/stores/boardStore'
import { useWorkspaceStore } from '@/stores/workspaceStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import BoardCard from '@/components/boards/BoardCard.vue'
import BoardForm from '@/components/boards/BoardForm.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import LoadingIndicator from '@/components/common/LoadingIndicator.vue'
import AppButton from '@/components/common/AppButton.vue'
import TeamsOverviewModal from '@/components/teams/TeamsOverviewModal.vue'

const boardStore = useBoardStore()
const workspaceStore = useWorkspaceStore()
const { t } = useI18n()
const toast = useToast()

const searchQuery = ref('')
const showFormModal = ref(false)
const selectedBoard = ref(null)
const isSubmitting = ref(false)
const showTeamsModal = ref(false)

const showDeleteConfirm = ref(false)
const boardToDelete = ref(null)
const isDeleting = ref(false)

async function loadData() {
  try {
    if (workspaceStore.workspaces.length === 0) {
      await workspaceStore.fetchWorkspaces()
    }
    await boardStore.fetchBoards(workspaceStore.currentWorkspaceId)
  } catch {
    toast.error(t('networkError'))
  }
}

onMounted(() => {
  loadData()
})

watch(
  () => workspaceStore.currentWorkspaceId,
  async (newId) => {
    try {
      await boardStore.fetchBoards(newId)
    } catch {
      toast.error(t('networkError'))
    }
  },
)

const filteredBoards = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return boardStore.boards
  return boardStore.boards.filter((b) => {
    const title = (b.title || '').toLowerCase()
    const desc = (b.description || '').toLowerCase()
    return title.includes(query) || desc.includes(query)
  })
})

function openCreateModal() {
  selectedBoard.value = null
  showFormModal.value = true
}

function openEditModal(board) {
  selectedBoard.value = board
  showFormModal.value = true
}

function openDeleteModal(board) {
  boardToDelete.value = board
  showDeleteConfirm.value = true
}

async function handleSaveBoard(data) {
  isSubmitting.value = true
  try {
    if (selectedBoard.value) {
      await boardStore.updateBoard(selectedBoard.value.id, data)
      toast.success(t('savedSuccessfully'))
    } else {
      await boardStore.createBoard({
        ...data,
        workspace_id: workspaceStore.currentWorkspaceId || null,
      })
      toast.success(t('createdSuccessfully'))
    }
    showFormModal.value = false
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isSubmitting.value = false
  }
}

async function handleDeleteBoard() {
  if (!boardToDelete.value) return
  isDeleting.value = true
  try {
    await boardStore.deleteBoard(boardToDelete.value.id)
    toast.success(t('deletedSuccessfully'))
    showDeleteConfirm.value = false
    boardToDelete.value = null
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="h-full overflow-y-auto px-4 sm:px-8 py-8">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- ── Workspace Context Banner ────────────────────────── -->
      <div
        v-if="workspaceStore.currentWorkspace"
        class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-slate-50/80 dark:from-slate-800/80 dark:via-slate-800/60 dark:to-slate-800/40 border border-slate-200/80 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
      >
        <div class="space-y-1">
          <div class="flex items-center gap-2.5">
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">
              {{ workspaceStore.currentWorkspace.name }}
            </h2>
            <span
              v-if="workspaceStore.currentUserRole"
              class="px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
            >
              {{ t('role' + workspaceStore.currentUserRole.charAt(0).toUpperCase() + workspaceStore.currentUserRole.slice(1)) }}
            </span>
          </div>
          <p
            v-if="workspaceStore.currentWorkspace.description"
            class="text-xs text-slate-500 dark:text-slate-400 max-w-xl"
          >
            {{ workspaceStore.currentWorkspace.description }}
          </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
          <span class="text-xs text-slate-500 dark:text-slate-400 font-medium me-1">
            {{ workspaceStore.currentWorkspace.members_count || 1 }} {{ t('members') }}
          </span>
          <button
            type="button"
            class="px-2.5 py-1 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all cursor-pointer"
            @click="showTeamsModal = true"
          >
            {{ t('teams') }}
          </button>
        </div>
      </div>

      <!-- ── Header ─────────────────────────────────────────── -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-2">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
              {{ t('myBoards') }}
            </h1>
            <span
              v-if="!boardStore.isLoading && boardStore.boards.length > 0"
              class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
            >
              {{ boardStore.boards.length }}
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ t('tagline') }}
          </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <!-- Search filter input -->
          <div
            v-if="boardStore.boards.length > 0"
            class="relative w-full sm:w-64"
          >
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('search')"
              class="w-full ps-9 pe-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs transition-all"
            />
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-4 h-4 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <button
              v-if="searchQuery"
              class="absolute end-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
              @click="searchQuery = ''"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>

          <AppButton
            v-if="workspaceStore.canCreateBoard"
            variant="primary"
            @click="openCreateModal"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14M5 12h14" />
            </svg>
            {{ t('createBoard') }}
          </AppButton>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="boardStore.isLoading" class="py-16">
        <LoadingIndicator :text="t('loading')" />
      </div>

      <!-- Error State -->
      <div
        v-else-if="boardStore.error"
        class="text-center py-12 p-6 rounded-3xl bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800"
      >
        <p class="text-rose-600 dark:text-rose-400 font-medium mb-3 text-sm">
          {{ boardStore.error }}
        </p>
        <AppButton variant="secondary" size="sm" @click="loadData">
          {{ t('retry') }}
        </AppButton>
      </div>

      <!-- Empty State -->
      <div v-else-if="boardStore.boards.length === 0" class="py-16">
        <EmptyState
          :title="t('noBoards')"
          :description="t('noBoardsSubtitle')"
          :action-text="workspaceStore.canCreateBoard ? t('createBoard') : null"
          @action="openCreateModal"
        />
      </div>

      <!-- Filtered Empty State -->
      <div
        v-else-if="filteredBoards.length === 0"
        class="text-center py-16 bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 p-8 shadow-xs"
      >
        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-400 flex items-center justify-center mx-auto mb-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1">
          {{ t('noTasks') }}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
          {{ searchQuery }}
        </p>
        <AppButton variant="secondary" size="sm" @click="searchQuery = ''">
          {{ t('close') }}
        </AppButton>
      </div>

      <!-- Board Cards Grid -->
      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <BoardCard
          v-for="board in filteredBoards"
          :key="board.id"
          :board="board"
          @edit="openEditModal"
          @delete="openDeleteModal"
        />
      </div>
    </div>

    <!-- Modals -->
    <BoardForm
      v-if="showFormModal"
      :board="selectedBoard"
      :loading="isSubmitting"
      @save="handleSaveBoard"
      @close="showFormModal = false"
    />

    <ConfirmDialog
      v-if="showDeleteConfirm"
      :title="t('deleteBoard')"
      :message="t('deleteBoardConfirm')"
      :confirm-label="t('delete')"
      :loading="isDeleting"
      @confirm="handleDeleteBoard"
      @cancel="showDeleteConfirm = false"
    />

    <!-- Teams Overview Modal -->
    <TeamsOverviewModal
      v-if="showTeamsModal && workspaceStore.currentWorkspace"
      :workspace="workspaceStore.currentWorkspace"
      @close="showTeamsModal = false"
    />
  </div>
</template>
