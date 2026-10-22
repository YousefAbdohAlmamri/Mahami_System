import { defineStore } from 'pinia'
import { ref } from 'vue'
import notificationService from '@/services/notificationService'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const isLoading = ref(false)

  async function fetchNotifications() {
    isLoading.value = true
    try {
      const response = await notificationService.getNotifications()
      notifications.value = response.data || []
      unreadCount.value = response.unread_count || 0
      return response
    } finally {
      isLoading.value = false
    }
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
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  }
})

