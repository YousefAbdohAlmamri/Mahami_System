import { defineStore } from 'pinia'
import { ref } from 'vue'
import notificationService from '@/services/notificationService'
import { useToastStore } from '@/stores/toastStore'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const isInitialized = ref(false)

  async function fetchNotifications() {
    isLoading.value = true
    try {
      const response = await notificationService.getNotifications()
      const newItems = response.data || []
      const newUnreadCount = response.unread_count || 0

      // If already initialized and new unread notifications arrived
      if (isInitialized.value && newUnreadCount > unreadCount.value) {
        const existingIds = new Set(notifications.value.map((n) => n.id))
        const brandNewUnread = newItems.filter((n) => !n.read_at && !existingIds.has(n.id))
        if (brandNewUnread.length > 0) {
          try {
            const toastStore = useToastStore()
            brandNewUnread.forEach((item) => {
              if (item.message) {
                toastStore.info(`🔔 ${item.message}`, 6000)
              }
            })
          } catch {
            // Toast fallback
          }
        }
      }

      notifications.value = newItems
      unreadCount.value = newUnreadCount
      isInitialized.value = true
      return response
    } catch {
      return null
    } finally {
      isLoading.value = false
    }
  }

  function reset() {
    notifications.value = []
    unreadCount.value = 0
    isLoading.value = false
    isInitialized.value = false
  }

  async function markAsRead(id) {
    const item = notifications.value.find((n) => n.id === id)
    if (item && !item.read_at) {
      item.read_at = new Date().toISOString()
      if (unreadCount.value > 0) unreadCount.value--
    }

    try {
      const response = await notificationService.markAsRead(id)
      unreadCount.value = response.unread_count ?? unreadCount.value
    } catch {
      // Revert if error
    }
  }

  async function markAllAsRead() {
    notifications.value.forEach((n) => {
      n.read_at = new Date().toISOString()
    })
    unreadCount.value = 0

    try {
      await notificationService.markAllAsRead()
    } catch {
      // Revert on error
    }
  }

  async function deleteNotification(id) {
    const item = notifications.value.find((n) => n.id === id)
    if (item && !item.read_at && unreadCount.value > 0) {
      unreadCount.value--
    }
    notifications.value = notifications.value.filter((n) => n.id !== id)

    try {
      await notificationService.deleteNotification(id)
    } catch {
      // Ignored
    }
  }

  return {
    notifications,
    unreadCount,
    isLoading,
    isInitialized,
    fetchNotifications,
    reset,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  }
})

