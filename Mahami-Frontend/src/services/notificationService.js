import http from './http'

export const notificationService = {
  async getNotifications() {
    const response = await http.get('/api/notifications')
    return response.data
  },

  async markAsRead(id) {
    const response = await http.patch(`/api/notifications/${id}/read`)
    return response.data
  },

  async markAllAsRead() {
    const response = await http.post('/api/notifications/mark-all-read')
    return response.data
  },

  async deleteNotification(id) {
    const response = await http.delete(`/api/notifications/${id}`)
    return response.data
  },
}

export default notificationService
