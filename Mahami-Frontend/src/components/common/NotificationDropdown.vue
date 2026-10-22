<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notificationStore'
import { useWorkspaceStore } from '@/stores/workspaceStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import workspaceService from '@/services/workspaceService'

const router = useRouter()
const notificationStore = useNotificationStore()
const workspaceStore = useWorkspaceStore()
const toast = useToast()
const { t } = useI18n()

const isOpen = ref(false)
const actionLoading = reactive({})
let pollingTimer = null

function toggleDropdown() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    notificationStore.fetchNotifications()
  }
}

function closeDropdown() {
  isOpen.value = false
}

function handleClickOutside(e) {
  if (isOpen.value && !e.target.closest('#notification-menu-container')) {
    closeDropdown()
  }
}

async function handleNotificationClick(item) {
  if (!item.read_at) {
    await notificationStore.markAsRead(item.id)
  }

  if (item.type === 'workspace_invitation' && item.token) {
    closeDropdown()
    router.push({ name: 'accept-invitation', params: { token: item.token } })
  }
}

async function handleAcceptInvitation(item) {
  if (!item.token) return
  actionLoading[item.id] = 'accept'
  try {
    const res = await workspaceService.acceptInvitation(item.token)
    toast.success(t('invitationAccepted'))
    await workspaceStore.fetchWorkspaces()
    const targetWsId = res.workspace_id || item.workspace_id
    if (targetWsId) {
      workspaceStore.setActiveWorkspace(targetWsId)
    }
    await notificationStore.markAsRead(item.id)
    await notificationStore.fetchNotifications()
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    actionLoading[item.id] = null
  }
}

async function handleDeclineInvitation(item) {
  if (!item.token) return
  actionLoading[item.id] = 'decline'
  try {
    await workspaceService.declineInvitation(item.token)
    toast.info(t('invitationDeclined'))
    await notificationStore.markAsRead(item.id)
    await notificationStore.fetchNotifications()
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    actionLoading[item.id] = null
  }
}

function formatTime(isoString) {
  if (!isoString) return ''
  try {
    const date = new Date(isoString)
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } catch {
    return isoString
  }
}

onMounted(() => {
  notificationStore.fetchNotifications()
  document.addEventListener('click', handleClickOutside)

  // Soft real-time polling every 30s
  pollingTimer = setInterval(() => {
    notificationStore.fetchNotifications()
  }, 30000)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  if (pollingTimer) clearInterval(pollingTimer)
})
</script>

<template>
  <div id="notification-menu-container" class="relative">
    <!-- Bell Button -->
    <button
      type="button"
      class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
      :title="t('notifications')"
      @click="toggleDropdown"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
      </svg>

      <!-- Unread Badge -->
      <span
        v-if="notificationStore.unreadCount > 0"
        class="absolute top-1 end-1 min-w-4 h-4 px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center shadow-xs"
      >
        {{ notificationStore.unreadCount > 9 ? '9+' : notificationStore.unreadCount }}
      </span>
    </button>

    <!-- Popup Dropdown -->
    <div
      v-if="isOpen"
      class="absolute end-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 z-50 overflow-hidden"
    >
      <!-- Header -->
      <div class="p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-sm font-bold text-slate-900 dark:text-white">
            {{ t('notifications') }}
          </span>
          <span
            v-if="notificationStore.unreadCount > 0"
            class="text-2xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400"
          >
            {{ notificationStore.unreadCount }}
          </span>
        </div>

        <button
          v-if="notificationStore.unreadCount > 0"
          type="button"
          class="text-xs text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
          @click="notificationStore.markAllAsRead"
        >
          {{ t('markAllAsRead') }}
        </button>
      </div>

      <!-- Notifications List -->
      <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
        <div
          v-if="notificationStore.isLoading && notificationStore.notifications.length === 0"
          class="p-6 text-center text-xs text-slate-400"
        >
          {{ t('loading') }}
        </div>

        <div
          v-else-if="notificationStore.notifications.length === 0"
          class="p-6 text-center text-xs text-slate-400"
        >
          {{ t('noNotifications') }}
        </div>

        <div
          v-for="item in notificationStore.notifications"
          :key="item.id"
          class="p-3 sm:p-3.5 flex items-start gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group cursor-pointer"
          :class="{ 'bg-blue-50/40 dark:bg-blue-950/20': !item.read_at }"
          @click="handleNotificationClick(item)"
        >
          <!-- Icon indicator -->
          <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
            :class="[
              item.type === 'workspace_invitation'
                ? 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400'
                : item.type === 'workspace_invitation_response'
                ? (item.accepted ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400')
                : item.type === 'mention'
                ? 'bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400'
                : item.type === 'task_due_reminder'
                ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400'
                : 'bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400'
            ]"
          >
            <!-- Workspace Invitation icon -->
            <svg v-if="item.type === 'workspace_invitation'" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <line x1="19" y1="8" x2="19" y2="14" />
              <line x1="22" y1="11" x2="16" y2="11" />
            </svg>
            <!-- Workspace Invitation Response icon -->
            <svg v-else-if="item.type === 'workspace_invitation_response'" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline v-if="item.accepted" points="20 6 9 17 4 12" />
              <g v-else>
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </g>
            </svg>
            <!-- Mention icon -->
            <svg v-else-if="item.type === 'mention'" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="4" />
              <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94" />
            </svg>
            <!-- Reminder icon -->
            <svg v-else-if="item.type === 'task_due_reminder'" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 6 12 12 16 14" />
            </svg>
            <!-- Default icon -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <line x1="19" y1="8" x2="19" y2="14" />
              <line x1="22" y1="11" x2="16" y2="11" />
            </svg>
          </div>

          <!-- Body -->
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-800 dark:text-slate-200 leading-snug">
              {{ item.message }}
            </p>

            <!-- Direct Actions for Workspace Invitation -->
            <div
              v-if="item.type === 'workspace_invitation' && item.token && !item.read_at"
              class="mt-2.5 flex items-center gap-2"
            >
              <button
                type="button"
                class="px-2.5 py-1 text-2xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors cursor-pointer disabled:opacity-50"
                :disabled="Boolean(actionLoading[item.id])"
                @click.stop="handleAcceptInvitation(item)"
              >
                {{ actionLoading[item.id] === 'accept' ? t('loading') : t('acceptInvitation') }}
              </button>
              <button
                type="button"
                class="px-2.5 py-1 text-2xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors cursor-pointer disabled:opacity-50"
                :disabled="Boolean(actionLoading[item.id])"
                @click.stop="handleDeclineInvitation(item)"
              >
                {{ actionLoading[item.id] === 'decline' ? t('loading') : t('declineInvitation') }}
              </button>
            </div>

            <span class="text-3xs text-slate-400 mt-1 block">
              {{ formatTime(item.created_at) }}
            </span>
          </div>

          <!-- Unread indicator & Delete button -->
          <div class="flex items-center gap-1 shrink-0">
            <span
              v-if="!item.read_at"
              class="w-2 h-2 rounded-full bg-blue-500"
              title="Unread"
            />
            <button
              type="button"
              class="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-red-500 rounded transition-opacity"
              :title="t('delete')"
              @click.stop="notificationStore.deleteNotification(item.id)"
            >
              ×
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

