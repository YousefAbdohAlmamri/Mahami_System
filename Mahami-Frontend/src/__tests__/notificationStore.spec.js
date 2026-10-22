import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useNotificationStore } from '@/stores/notificationStore'
import notificationService from '@/services/notificationService'

vi.mock('@/services/notificationService', () => ({
  default: {
    getNotifications: vi.fn(),
    markAsRead: vi.fn(),
    markAllAsRead: vi.fn(),
    deleteNotification: vi.fn(),
  },
}))

describe('notificationStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('has clean initial state', () => {
    const store = useNotificationStore()
    expect(store.notifications).toEqual([])
    expect(store.unreadCount).toBe(0)
    expect(store.isLoading).toBe(false)
  })

  it('fetchNotifications populates state and unread count', async () => {
    const mockData = {
      unread_count: 2,
      data: [
        { id: 'uuid-1', type: 'task_assigned', message: 'Assigned to task', read_at: null },
        { id: 'uuid-2', type: 'mention', message: 'Mentioned you', read_at: null },
      ],
    }
    notificationService.getNotifications.mockResolvedValueOnce(mockData)

    const store = useNotificationStore()
    await store.fetchNotifications()

    expect(store.unreadCount).toBe(2)
    expect(store.notifications).toHaveLength(2)
  })

  it('markAsRead marks notification as read and decrements unreadCount', async () => {
    const store = useNotificationStore()
    store.notifications = [
      { id: 'uuid-1', type: 'task_assigned', read_at: null },
      { id: 'uuid-2', type: 'mention', read_at: null },
    ]
    store.unreadCount = 2

    notificationService.markAsRead.mockResolvedValueOnce({ unread_count: 1 })

    await store.markAsRead('uuid-1')

    expect(store.notifications[0].read_at).not.toBeNull()
    expect(store.unreadCount).toBe(1)
  })

  it('markAllAsRead sets all read_at and resets unreadCount to 0', async () => {
    const store = useNotificationStore()
    store.notifications = [
      { id: 'uuid-1', read_at: null },
      { id: 'uuid-2', read_at: null },
    ]
    store.unreadCount = 2

    notificationService.markAllAsRead.mockResolvedValueOnce({})

    await store.markAllAsRead()

    expect(store.unreadCount).toBe(0)
    expect(store.notifications.every((n) => n.read_at !== null)).toBe(true)
  })

  it('deleteNotification removes notification and decrements unreadCount if unread', async () => {
    const store = useNotificationStore()
    store.notifications = [
      { id: 'uuid-1', read_at: null },
      { id: 'uuid-2', read_at: '2026-10-22T00:00:00Z' },
    ]
    store.unreadCount = 1

    notificationService.deleteNotification.mockResolvedValueOnce({})

    await store.deleteNotification('uuid-1')

    expect(store.notifications).toHaveLength(1)
    expect(store.notifications[0].id).toBe('uuid-2')
    expect(store.unreadCount).toBe(0)
  })
})
