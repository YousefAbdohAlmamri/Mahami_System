<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import { useWorkspaceStore } from '@/stores/workspaceStore'
import { useBoardStore } from '@/stores/boardStore'
import taskService from '@/services/taskService'
import commentService from '@/services/commentService'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
  board: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'edit', 'delete', 'task-updated'])
const { t } = useI18n()
const toast = useToast()
const workspaceStore = useWorkspaceStore()
const boardStore = useBoardStore()

// Navigation Tabs
const activeTab = ref('details') // 'details' | 'comments' | 'activities'

// Local copy of task to reflect immediate edits
const currentTask = ref({ ...props.task })
const isLoadingDetails = ref(false)

// Dropdowns and form states
const showAssigneeDropdown = ref(false)
const showLabelDropdown = ref(false)
const isAddingChecklist = ref(false)
const newChecklistTitle = ref('')
const newItemTitles = ref({})
const isUploadingAttachment = ref(false)
const fileInputRef = ref(null)

// New label creation state
const newLabelName = ref('')
const newLabelColor = ref('#3b82f6')
const isCreatingLabel = ref(false)

// Phase 4 Collaboration states
const comments = ref([])
const isLoadingComments = ref(false)
const newCommentContent = ref('')
const isPostingComment = ref(false)
const editingCommentId = ref(null)
const editingCommentContent = ref('')
const showMentionDropdown = ref(false)

const activities = ref([])
const isLoadingActivities = ref(false)

const PRESET_COLORS = [
  '#ef4444', // Red
  '#f97316', // Orange
  '#f59e0b', // Amber
  '#10b981', // Emerald
  '#06b6d4', // Cyan
  '#3b82f6', // Blue
  '#8b5cf6', // Violet
  '#ec4899', // Pink
  '#64748b', // Slate
]

const priorityColors = {
  low: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
  medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800',
  high: 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border-red-200 dark:border-red-800',
}

const activeBoard = computed(() => props.board || boardStore.currentBoard)

const availableBoardLabels = computed(() => {
  return activeBoard.value?.labels || []
})

const isOverdue = computed(() => {
  if (!currentTask.value.due_date) return false
  const today = new Date().toISOString().slice(0, 10)
  return currentTask.value.due_date < today
})

const availableAssignees = computed(() => {
  if (workspaceStore.members && workspaceStore.members.length > 0) {
    return workspaceStore.members.map((m) => ({
      id: m.id,
      name: m.name,
      email: m.email,
    }))
  }
  if (currentTask.value.assignees && currentTask.value.assignees.length > 0) {
    return currentTask.value.assignees
  }
  return []
})

function isUserAssigned(userId) {
  return (currentTask.value.assignees || []).some((u) => u.id === userId)
}

function isLabelAttached(labelId) {
  return (currentTask.value.labels || []).some((l) => l.id === labelId)
}

function calculateChecklistProgress(checklist) {
  const items = checklist.items || []
  if (items.length === 0) return { total: 0, completed: 0, percentage: 0 }
  const completed = items.filter((i) => i.is_completed).length
  return {
    total: items.length,
    completed,
    percentage: Math.round((completed / items.length) * 100),
  }
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function formatTimestamp(isoString) {
  if (!isoString) return ''
  try {
    const date = new Date(isoString)
    return date.toLocaleString([], {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return isoString
  }
}

// ── Tab Switching ─────────────────────────────────────────────
async function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'comments' && comments.value.length === 0) {
    await fetchComments()
  } else if (tab === 'activities' && activities.value.length === 0) {
    await fetchActivities()
  }
}

// ── Initial Fetch ─────────────────────────────────────────────
onMounted(async () => {
  try {
    isLoadingDetails.value = true
    const response = await taskService.getTask(props.task.id)
    if (response?.data) {
      currentTask.value = response.data
      emit('task-updated', currentTask.value)
    }
  } catch {
    // Fallback
  } finally {
    isLoadingDetails.value = false
  }

  if (activeBoard.value?.workspace_id && workspaceStore.members.length === 0) {
    try {
      await workspaceStore.fetchMembers(activeBoard.value.workspace_id)
    } catch {
      // Ignored
    }
  }

  if (activeBoard.value?.id && (!activeBoard.value.labels || activeBoard.value.labels.length === 0)) {
    try {
      const res = await taskService.getBoardLabels(activeBoard.value.id)
      if (res?.data && activeBoard.value) {
        activeBoard.value.labels = res.data
      }
    } catch {
      // Ignored
    }
  }
})

// ── Assignees Handlers ─────────────────────────────────────────
async function toggleAssignee(user) {
  const currentIds = (currentTask.value.assignees || []).map((u) => u.id)
  let updatedIds = []
  if (currentIds.includes(user.id)) {
    updatedIds = currentIds.filter((id) => id !== user.id)
  } else {
    updatedIds = [...currentIds, user.id]
  }

  try {
    const res = await taskService.syncTaskAssignees(currentTask.value.id, updatedIds)
    if (res?.data) {
      currentTask.value = res.data
      emit('task-updated', currentTask.value)
      toast.success(t('savedSuccessfully'))
    }
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

// ── Labels Handlers ────────────────────────────────────────────
async function toggleLabel(label) {
  const currentIds = (currentTask.value.labels || []).map((l) => l.id)
  let updatedIds = []
  if (currentIds.includes(label.id)) {
    updatedIds = currentIds.filter((id) => id !== label.id)
  } else {
    updatedIds = [...currentIds, label.id]
  }

  try {
    const res = await taskService.syncTaskLabels(currentTask.value.id, updatedIds)
    if (res?.data) {
      currentTask.value = res.data
      emit('task-updated', currentTask.value)
    }
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

async function handleCreateLabel() {
  if (!newLabelName.value.trim() || !activeBoard.value?.id) return
  isCreatingLabel.value = true
  try {
    const res = await taskService.createBoardLabel(activeBoard.value.id, {
      name: newLabelName.value.trim(),
      color: newLabelColor.value,
    })
    const createdLabel = res.data
    if (activeBoard.value) {
      if (!activeBoard.value.labels) activeBoard.value.labels = []
      activeBoard.value.labels.push(createdLabel)
    }
    await toggleLabel(createdLabel)
    newLabelName.value = ''
    newLabelColor.value = '#3b82f6'
    toast.success(t('createdSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isCreatingLabel.value = false
  }
}

// ── Checklists Handlers ────────────────────────────────────────
async function handleCreateChecklist() {
  if (!newChecklistTitle.value.trim()) return
  try {
    const res = await taskService.createChecklist(currentTask.value.id, {
      title: newChecklistTitle.value.trim(),
    })
    if (!currentTask.value.checklists) currentTask.value.checklists = []
    currentTask.value.checklists.push({ ...res.data, items: [] })
    newChecklistTitle.value = ''
    isAddingChecklist.value = false
    emit('task-updated', currentTask.value)
    toast.success(t('createdSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

async function handleDeleteChecklist(checklistId) {
  try {
    await taskService.deleteChecklist(checklistId)
    currentTask.value.checklists = (currentTask.value.checklists || []).filter(
      (c) => c.id !== checklistId
    )
    emit('task-updated', currentTask.value)
    toast.success(t('deletedSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

async function handleAddItem(checklistId) {
  const text = (newItemTitles.value[checklistId] || '').trim()
  if (!text) return

  try {
    const res = await taskService.addChecklistItem(checklistId, { title: text })
    const targetChecklist = currentTask.value.checklists.find((c) => c.id === checklistId)
    if (targetChecklist) {
      if (!targetChecklist.items) targetChecklist.items = []
      targetChecklist.items.push(res.data)
    }
    newItemTitles.value[checklistId] = ''
    emit('task-updated', currentTask.value)
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

async function handleToggleItem(checklist, item) {
  const prevVal = item.is_completed
  item.is_completed = !prevVal
  try {
    await taskService.updateChecklistItem(item.id, { is_completed: item.is_completed })
    emit('task-updated', currentTask.value)
  } catch {
    item.is_completed = prevVal
    toast.error(t('genericError'))
  }
}

async function handleDeleteItem(checklist, itemId) {
  try {
    await taskService.deleteChecklistItem(itemId)
    checklist.items = checklist.items.filter((i) => i.id !== itemId)
    emit('task-updated', currentTask.value)
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

// ── Attachments Handlers ───────────────────────────────────────
function triggerFileInput() {
  fileInputRef.value?.click()
}

async function handleFileUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return

  isUploadingAttachment.value = true
  try {
    const res = await taskService.uploadAttachment(currentTask.value.id, file)
    if (!currentTask.value.attachments) currentTask.value.attachments = []
    currentTask.value.attachments.unshift(res.data)
    emit('task-updated', currentTask.value)
    toast.success(t('createdSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isUploadingAttachment.value = false
    if (fileInputRef.value) fileInputRef.value.value = ''
  }
}

async function handleDeleteAttachment(attachmentId) {
  try {
    await taskService.deleteAttachment(attachmentId)
    currentTask.value.attachments = (currentTask.value.attachments || []).filter(
      (a) => a.id !== attachmentId
    )
    emit('task-updated', currentTask.value)
    toast.success(t('deletedSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

// ── Phase 4 Collaboration Handlers (Comments & Activities) ────
async function fetchComments() {
  isLoadingComments.value = true
  try {
    const res = await commentService.getTaskComments(currentTask.value.id)
    comments.value = res.data || []
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isLoadingComments.value = false
  }
}

async function fetchActivities() {
  isLoadingActivities.value = true
  try {
    const res = await commentService.getTaskActivities(currentTask.value.id)
    activities.value = res.data || []
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isLoadingActivities.value = false
  }
}

function insertMention(memberName) {
  newCommentContent.value += ` @${memberName} `
  showMentionDropdown.value = false
}

async function handlePostComment() {
  if (!newCommentContent.value.trim()) return
  isPostingComment.value = true
  try {
    const res = await commentService.createTaskComment(currentTask.value.id, {
      content: newCommentContent.value.trim(),
    })
    comments.value.push(res.data)
    newCommentContent.value = ''
    currentTask.value.comments_count = (currentTask.value.comments_count || 0) + 1
    emit('task-updated', currentTask.value)
    toast.success(t('createdSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isPostingComment.value = false
  }
}

function startEditComment(comment) {
  editingCommentId.value = comment.id
  editingCommentContent.value = comment.content
}

async function handleSaveEditComment(commentId) {
  if (!editingCommentContent.value.trim()) return
  try {
    const res = await commentService.updateTaskComment(commentId, {
      content: editingCommentContent.value.trim(),
    })
    const idx = comments.value.findIndex((c) => c.id === commentId)
    if (idx !== -1) {
      comments.value[idx] = res.data
    }
    editingCommentId.value = null
    editingCommentContent.value = ''
    toast.success(t('savedSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

async function handleDeleteComment(commentId) {
  try {
    await commentService.deleteTaskComment(commentId)
    comments.value = comments.value.filter((c) => c.id !== commentId)
    if (currentTask.value.comments_count > 0) {
      currentTask.value.comments_count--
    }
    emit('task-updated', currentTask.value)
    toast.success(t('deletedSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}
</script>

<template>
  <AppModal size="xl" :title="t('taskDetails')" @close="emit('close')">
    <div class="space-y-5">
      <!-- Title & Badges Bar -->
      <div class="space-y-2.5 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white leading-relaxed">
          {{ currentTask.title }}
        </h3>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Priority Badge -->
          <span
            :class="[
              'text-xs font-semibold px-2.5 py-1 rounded-md border',
              priorityColors[currentTask.priority] || priorityColors.medium,
            ]"
          >
            {{ t('priority') }}: {{ t(currentTask.priority) }}
          </span>

          <!-- Due Date Badge -->
          <span
            v-if="currentTask.due_date"
            class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-md border font-medium"
            :class="
              isOverdue
                ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800'
                : 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
            "
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            {{ currentTask.due_date }}
            <span v-if="isOverdue" class="text-2xs font-bold text-red-600 dark:text-red-400 uppercase ms-1">
              ({{ t('overdue') }})
            </span>
          </span>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button
          type="button"
          class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-xl transition-all cursor-pointer"
          :class="
            activeTab === 'details'
              ? 'bg-blue-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
          "
          @click="switchTab('details')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <path d="M3 9h18M9 21V9" />
          </svg>
          {{ t('tabDetails') }}
        </button>

        <button
          type="button"
          class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-xl transition-all cursor-pointer"
          :class="
            activeTab === 'comments'
              ? 'bg-blue-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
          "
          @click="switchTab('comments')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
          </svg>
          {{ t('tabComments') }}
          <span
            v-if="comments.length > 0 || currentTask.comments_count > 0"
            class="px-1.5 py-0.2 rounded-full text-3xs font-bold"
            :class="activeTab === 'comments' ? 'bg-white/25 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
          >
            {{ comments.length || currentTask.comments_count || 0 }}
          </span>
        </button>

        <button
          type="button"
          class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-xl transition-all cursor-pointer"
          :class="
            activeTab === 'activities'
              ? 'bg-blue-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
          "
          @click="switchTab('activities')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
          </svg>
          {{ t('tabActivity') }}
        </button>
      </div>

      <!-- TAB 1: DETAILS (2 Columns on desktop) -->
      <div v-if="activeTab === 'details'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left / Main Column (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 mb-1.5 uppercase tracking-wider">
              {{ t('taskDescription') }}
            </label>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap min-h-20 leading-relaxed">
              {{ currentTask.description || '—' }}
            </div>
          </div>

          <!-- Checklists Section -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m9 11 3 3L22 4" />
                  <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                </svg>
                {{ t('checklists') }}
                <span class="text-xs font-normal text-slate-400">({{ currentTask.checklists?.length || 0 }})</span>
              </h4>

              <AppButton
                v-if="!isAddingChecklist"
                variant="ghost"
                size="xs"
                @click="isAddingChecklist = true"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 5v14M5 12h14" />
                </svg>
                {{ t('addChecklist') }}
              </AppButton>
            </div>

            <!-- Add Checklist Inline Form -->
            <div
              v-if="isAddingChecklist"
              class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-blue-200 dark:border-blue-900/60 space-y-2.5"
            >
              <input
                v-model="newChecklistTitle"
                type="text"
                :placeholder="t('checklistTitle')"
                class="w-full px-3 py-1.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                @keyup.enter="handleCreateChecklist"
              />
              <div class="flex items-center justify-end gap-2">
                <AppButton variant="ghost" size="xs" @click="isAddingChecklist = false; newChecklistTitle = ''">
                  {{ t('cancel') }}
                </AppButton>
                <AppButton variant="primary" size="xs" :disabled="!newChecklistTitle.trim()" @click="handleCreateChecklist">
                  {{ t('save') }}
                </AppButton>
              </div>
            </div>

            <!-- List of Checklists -->
            <div
              v-for="checklist in currentTask.checklists || []"
              :key="checklist.id"
              class="p-4 rounded-2xl bg-white dark:bg-slate-900/70 border border-slate-200/80 dark:border-slate-800 space-y-3"
            >
              <!-- Checklist Header -->
              <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                  {{ checklist.title }}
                </span>
                <button
                  class="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                  :title="t('delete')"
                  @click="handleDeleteChecklist(checklist.id)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                  </svg>
                </button>
              </div>

              <!-- Checklist Progress Bar -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-2xs font-semibold text-slate-500 dark:text-slate-400">
                  <span>{{ calculateChecklistProgress(checklist).percentage }}%</span>
                  <span>{{ calculateChecklistProgress(checklist).completed }} / {{ calculateChecklistProgress(checklist).total }}</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-emerald-500 rounded-full transition-all duration-300"
                    :style="{ width: `${calculateChecklistProgress(checklist).percentage}%` }"
                  />
                </div>
              </div>

              <!-- Checklist Items -->
              <div class="space-y-1.5 pt-1">
                <div
                  v-for="item in checklist.items || []"
                  :key="item.id"
                  class="group flex items-center justify-between gap-2 py-1 px-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                >
                  <label class="flex items-center gap-2.5 flex-1 cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="item.is_completed"
                      class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 dark:bg-slate-800 border-slate-300 dark:border-slate-700 cursor-pointer"
                      @change="handleToggleItem(checklist, item)"
                    />
                    <span
                      class="text-sm transition-all"
                      :class="
                        item.is_completed
                          ? 'line-through text-slate-400 dark:text-slate-500'
                          : 'text-slate-700 dark:text-slate-200'
                      "
                    >
                      {{ item.title }}
                    </span>
                  </label>

                  <button
                    class="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-red-500 rounded transition-all"
                    :title="t('delete')"
                    @click="handleDeleteItem(checklist, item.id)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="18" y1="6" x2="6" y2="18" />
                      <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Add Item Input -->
              <div class="flex items-center gap-2 pt-2">
                <input
                  v-model="newItemTitles[checklist.id]"
                  type="text"
                  :placeholder="t('itemTitlePlaceholder')"
                  class="flex-1 px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                  @keyup.enter="handleAddItem(checklist.id)"
                />
                <AppButton
                  variant="secondary"
                  size="xs"
                  :disabled="!(newItemTitles[checklist.id] || '').trim()"
                  @click="handleAddItem(checklist.id)"
                >
                  {{ t('addItem') }}
                </AppButton>
              </div>
            </div>
          </div>

          <!-- Attachments Section -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                </svg>
                {{ t('attachments') }}
                <span class="text-xs font-normal text-slate-400">({{ currentTask.attachments?.length || 0 }})</span>
              </h4>

              <div>
                <input
                  ref="fileInputRef"
                  type="file"
                  class="hidden"
                  @change="handleFileUpload"
                />
                <AppButton
                  variant="ghost"
                  size="xs"
                  :loading="isUploadingAttachment"
                  @click="triggerFileInput"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                  </svg>
                  {{ t('uploadFile') }}
                </AppButton>
              </div>
            </div>

            <!-- Attachments List -->
            <div class="space-y-2">
              <div
                v-for="att in currentTask.attachments || []"
                :key="att.id"
                class="flex items-center justify-between p-3 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 transition-colors"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                      <polyline points="14 2 14 8 20 8" />
                    </svg>
                  </div>
                  <div class="truncate">
                    <a
                      v-if="att.file_url"
                      :href="att.file_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline truncate block"
                    >
                      {{ att.file_name }}
                    </a>
                    <span v-else class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate block">
                      {{ att.file_name }}
                    </span>
                    <span class="text-2xs text-slate-400">
                      {{ formatFileSize(att.file_size) }}
                    </span>
                  </div>
                </div>

                <button
                  class="p-1.5 text-slate-400 hover:text-red-500 dark:hover:text-red-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                  :title="t('delete')"
                  @click="handleDeleteAttachment(att.id)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right / Sidebar Column (1 col) -->
        <div class="space-y-6">
          <!-- Assignees Block -->
          <div class="space-y-3 p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                {{ t('assignees') }}
              </label>

              <!-- Add/Toggle Assignee Dropdown Button -->
              <div class="relative">
                <AppButton
                  variant="ghost"
                  size="xs"
                  @click="showAssigneeDropdown = !showAssigneeDropdown"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <line x1="19" y1="8" x2="19" y2="14" />
                    <line x1="22" y1="11" x2="16" y2="11" />
                  </svg>
                  {{ t('assignTo') }}
                </AppButton>

                <!-- Assignees Popup Menu -->
                <div
                  v-if="showAssigneeDropdown"
                  class="absolute end-0 mt-1 w-56 rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 p-2 z-20 space-y-1 max-h-56 overflow-y-auto"
                >
                  <div
                    v-for="user in availableAssignees"
                    :key="user.id"
                    class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer text-xs"
                    @click="toggleAssignee(user)"
                  >
                    <span class="font-medium text-slate-800 dark:text-slate-200 truncate">
                      {{ user.name }}
                    </span>
                    <span v-if="isUserAssigned(user.id)" class="text-blue-500 font-bold">✓</span>
                  </div>
                  <div v-if="availableAssignees.length === 0" class="p-2 text-center text-2xs text-slate-400">
                    {{ t('noAssignees') }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Assigned Avatars Stack -->
            <div v-if="(currentTask.assignees || []).length > 0" class="flex flex-wrap gap-2">
              <div
                v-for="u in currentTask.assignees"
                :key="u.id"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-700 dark:text-slate-300"
              >
                <div class="w-4 h-4 rounded-full bg-blue-600 text-white text-3xs font-bold flex items-center justify-center">
                  {{ (u.name || 'U').charAt(0).toUpperCase() }}
                </div>
                <span>{{ u.name }}</span>
                <button
                  class="text-slate-400 hover:text-red-500 ms-1"
                  @click="toggleAssignee(u)"
                >
                  ×
                </button>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400">
              {{ t('noAssignees') }}
            </div>
          </div>

          <!-- Labels Block -->
          <div class="space-y-3 p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                {{ t('labels') }}
              </label>

              <!-- Add/Manage Labels Dropdown Button -->
              <div class="relative">
                <AppButton
                  variant="ghost"
                  size="xs"
                  @click="showLabelDropdown = !showLabelDropdown"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                  </svg>
                  {{ t('addLabel') }}
                </AppButton>

                <!-- Labels Dropdown Menu -->
                <div
                  v-if="showLabelDropdown"
                  class="absolute end-0 mt-1 w-64 rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 p-3 z-20 space-y-3"
                >
                  <!-- Existing Board Labels List -->
                  <div class="space-y-1 max-h-40 overflow-y-auto">
                    <div
                      v-for="lbl in availableBoardLabels"
                      :key="lbl.id"
                      class="flex items-center justify-between p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer text-xs"
                      @click="toggleLabel(lbl)"
                    >
                      <div class="flex items-center gap-2 truncate">
                        <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: lbl.color }" />
                        <span class="text-slate-800 dark:text-slate-200 truncate font-medium">{{ lbl.name }}</span>
                      </div>
                      <span v-if="isLabelAttached(lbl.id)" class="text-blue-500 font-bold">✓</span>
                    </div>
                  </div>

                  <!-- Create New Label Inline Section -->
                  <div class="pt-2 border-t border-slate-100 dark:border-slate-700 space-y-2">
                    <span class="text-2xs font-semibold text-slate-400 block uppercase">
                      {{ t('createLabel') }}
                    </span>
                    <input
                      v-model="newLabelName"
                      type="text"
                      :placeholder="t('labelName')"
                      class="w-full px-2.5 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-blue-500"
                    />

                    <!-- Palette Selector -->
                    <div class="flex flex-wrap gap-1.5 pt-1">
                      <button
                        v-for="color in PRESET_COLORS"
                        :key="color"
                        type="button"
                        class="w-5 h-5 rounded-full border transition-all"
                        :class="newLabelColor === color ? 'ring-2 ring-blue-500 scale-110' : 'border-transparent'"
                        :style="{ backgroundColor: color }"
                        @click="newLabelColor = color"
                      />
                    </div>

                    <AppButton
                      variant="primary"
                      size="xs"
                      class="w-full justify-center mt-1"
                      :loading="isCreatingLabel"
                      :disabled="!newLabelName.trim()"
                      @click="handleCreateLabel"
                    >
                      {{ t('createLabel') }}
                    </AppButton>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attached Labels Chips -->
            <div v-if="(currentTask.labels || []).length > 0" class="flex flex-wrap gap-1.5">
              <span
                v-for="lbl in currentTask.labels"
                :key="lbl.id"
                class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full text-white shadow-2xs"
                :style="{ backgroundColor: lbl.color || '#3b82f6' }"
              >
                {{ lbl.name }}
                <button
                  class="text-white/80 hover:text-white"
                  @click="toggleLabel(lbl)"
                >
                  ×
                </button>
              </span>
            </div>
            <div v-else class="text-xs text-slate-400">
              {{ t('labels') }} —
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: COMMENTS (Phase 4 Collaboration) -->
      <div v-else-if="activeTab === 'comments'" class="space-y-5">
        <!-- New Comment Box -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-3">
          <div class="relative">
            <textarea
              v-model="newCommentContent"
              rows="3"
              :placeholder="t('commentPlaceholder')"
              class="w-full p-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500 transition-all resize-none"
            />

            <!-- Mention Autocomplete Helper Button / Dropdown -->
            <div class="flex items-center justify-between pt-1">
              <div class="relative">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
                  @click="showMentionDropdown = !showMentionDropdown"
                >
                  <span>@</span>
                  <span>{{ t('assignTo') }}</span>
                </button>

                <div
                  v-if="showMentionDropdown"
                  class="absolute start-0 mt-1 w-48 rounded-xl bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 p-1.5 z-20 space-y-1 max-h-40 overflow-y-auto"
                >
                  <div
                    v-for="u in availableAssignees"
                    :key="u.id"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 cursor-pointer truncate"
                    @click="insertMention(u.name)"
                  >
                    @{{ u.name }}
                  </div>
                </div>
              </div>

              <AppButton
                variant="primary"
                size="sm"
                :loading="isPostingComment"
                :disabled="!newCommentContent.trim()"
                @click="handlePostComment"
              >
                {{ t('addComment') }}
              </AppButton>
            </div>
          </div>
        </div>

        <!-- Comments Feed -->
        <div v-if="isLoadingComments" class="py-8 text-center text-xs text-slate-400">
          {{ t('loading') }}
        </div>
        <div v-else-if="comments.length === 0" class="py-8 text-center text-xs text-slate-400">
          {{ t('noComments') }}
        </div>
        <div v-else class="space-y-3 max-h-96 overflow-y-auto pe-1">
          <div
            v-for="comment in comments"
            :key="comment.id"
            class="p-4 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-2 hover:border-slate-300 dark:hover:border-slate-700 transition-colors"
          >
            <!-- Comment Header: Avatar, Name, Time, Actions -->
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-3xs uppercase shadow-2xs">
                  {{ (comment.user?.name || 'U').charAt(0) }}
                </div>
                <div>
                  <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                    {{ comment.user?.name }}
                  </span>
                  <span class="text-3xs text-slate-400 ms-2">
                    {{ formatTimestamp(comment.created_at) }}
                  </span>
                </div>
              </div>

              <!-- Edit / Delete actions -->
              <div v-if="comment.can_edit || comment.can_delete" class="flex items-center gap-1">
                <button
                  v-if="comment.can_edit && editingCommentId !== comment.id"
                  class="p-1 text-slate-400 hover:text-blue-500 rounded transition-colors"
                  :title="t('editTask')"
                  @click="startEditComment(comment)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                  </svg>
                </button>
                <button
                  v-if="comment.can_delete"
                  class="p-1 text-slate-400 hover:text-red-500 rounded transition-colors"
                  :title="t('delete')"
                  @click="handleDeleteComment(comment.id)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Comment Content / Edit Form -->
            <div v-if="editingCommentId === comment.id" class="space-y-2 pt-1">
              <textarea
                v-model="editingCommentContent"
                rows="2"
                class="w-full p-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white resize-none focus:outline-hidden focus:ring-2 focus:ring-blue-500"
              />
              <div class="flex items-center justify-end gap-2">
                <AppButton variant="ghost" size="xs" @click="editingCommentId = null">
                  {{ t('cancel') }}
                </AppButton>
                <AppButton variant="primary" size="xs" :disabled="!editingCommentContent.trim()" @click="handleSaveEditComment(comment.id)">
                  {{ t('save') }}
                </AppButton>
              </div>
            </div>
            <div v-else class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">
              {{ comment.content }}
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: ACTIVITIES (Phase 4 Audit Trail) -->
      <div v-else-if="activeTab === 'activities'" class="space-y-4">
        <div v-if="isLoadingActivities" class="py-8 text-center text-xs text-slate-400">
          {{ t('loading') }}
        </div>
        <div v-else-if="activities.length === 0" class="py-8 text-center text-xs text-slate-400">
          {{ t('noActivities') }}
        </div>
        <div v-else class="relative border-s-2 border-slate-200 dark:border-slate-800 ms-3 space-y-4 max-h-96 overflow-y-auto pe-1">
          <div
            v-for="act in activities"
            :key="act.id"
            class="relative ps-5"
          >
            <!-- Timeline dot -->
            <div class="absolute -start-[5px] top-1.5 w-2 h-2 rounded-full bg-blue-500 ring-4 ring-white dark:ring-slate-900" />

            <div class="p-3 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                  {{ act.user?.name || 'System' }}
                </span>
                <span class="text-3xs text-slate-400">
                  {{ formatTimestamp(act.created_at) }}
                </span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400">
                {{ act.description }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <template #footer>
      <div class="flex flex-wrap items-center justify-between gap-2">
        <AppButton
          variant="danger"
          size="sm"
          @click="emit('delete', currentTask)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
          </svg>
          {{ t('delete') }}
        </AppButton>

        <div class="flex items-center gap-2 ms-auto">
          <AppButton
            variant="secondary"
            size="sm"
            @click="emit('close')"
          >
            {{ t('close') }}
          </AppButton>
          <AppButton
            variant="primary"
            size="sm"
            @click="emit('edit', currentTask)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
            </svg>
            {{ t('editTask') }}
          </AppButton>
        </div>
      </div>
    </template>
  </AppModal>
</template>
