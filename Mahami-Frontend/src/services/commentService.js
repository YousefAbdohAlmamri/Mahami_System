import http from './http'

export const commentService = {
  async getTaskComments(taskId) {
    const response = await http.get(`/api/tasks/${taskId}/comments`)
    return response.data
  },

  async createTaskComment(taskId, data) {
    const response = await http.post(`/api/tasks/${taskId}/comments`, data)
    return response.data
  },

  async updateTaskComment(commentId, data) {
    const response = await http.put(`/api/comments/${commentId}`, data)
    return response.data
  },

  async deleteTaskComment(commentId) {
    const response = await http.delete(`/api/comments/${commentId}`)
    return response.data
  },

  async getTaskActivities(taskId) {
    const response = await http.get(`/api/tasks/${taskId}/activities`)
    return response.data
  },

  async getBoardActivities(boardId) {
    const response = await http.get(`/api/boards/${boardId}/activities`)
    return response.data
  },
}

export default commentService
