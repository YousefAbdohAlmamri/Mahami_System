<script setup>
import { ref, onMounted } from 'vue'
import { useBoardStore } from '@/stores/boardStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import BoardCard from '@/components/boards/BoardCard.vue'
import BoardForm from '@/components/boards/BoardForm.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import LoadingIndicator from '@/components/common/LoadingIndicator.vue'
import AppButton from '@/components/common/AppButton.vue'

const boardStore = useBoardStore()
const { t } = useI18n()
const toast = useToast()

const showFormModal = ref(false)
const selectedBoard = ref(null)
const isSubmitting = ref(false)

const showDeleteConfirm = ref(false)
const boardToDelete = ref(null)
const isDeleting = ref(false)

onMounted(async () => {
  try {
    await boardStore.fetchBoards()
  } catch {
    toast.error(t('networkError'))
  }
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
      await boardStore.createBoard(data)
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
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ t('myBoards') }}
          </h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ t('tagline') }}
          </p>
        </div>

        <AppButton variant="primary" @click="openCreateModal">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14" />
          </svg>
          {{ t('createBoard') }}
        </AppButton>
      </div>

      <!-- Loading State -->
      <div v-if="boardStore.isLoading" class="py-16">
        <LoadingIndicator :text="t('loading')" />
      </div>

      <!-- Error State -->
      <div
        v-else-if="boardStore.error"
        class="text-center py-12 p-6 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800"
      >
        <p class="text-red-600 dark:text-red-400 font-medium mb-3">
          {{ boardStore.error }}
        </p>
        <AppButton variant="secondary" size="sm" @click="boardStore.fetchBoards()">
          {{ t('retry') }}
        </AppButton>
      </div>

      <!-- Empty State -->
      <div v-else-if="boardStore.boards.length === 0" class="py-16">
        <EmptyState
          :title="t('noBoards')"
          :description="t('noBoardsSubtitle')"
          :action-text="t('createBoard')"
          @action="openCreateModal"
        />
      </div>

      <!-- Board Cards Grid -->
      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"
      >
        <BoardCard
          v-for="board in boardStore.boards"
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
  </div>
</template>

