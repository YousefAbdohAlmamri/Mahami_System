import http from './http'

export const taskService = {
  async createTask(listId, data) {
    const response = await http.post(`/api/lists/${listId}/tasks`, data)
    return response.data
  },

  async getTask(id) {
    const response = await http.get(`/api/tasks/${id}`)
    return response.data
  },

  async updateTask(id, data) {
    const response = await http.put(`/api/tasks/${id}`, data)
    return response.data
  },

  async deleteTask(id) {
    const response = await http.delete(`/api/tasks/${id}`)
    return response.data
  },

  async moveTask(id, targetListId, position) {
    const response = await http.patch(`/api/tasks/${id}/move`, {
      target_list_id: targetListId,
      position,
    })
    return response.data
  },

  async reorderTasks(listId, orderedIds) {
    const response = await http.patch(`/api/lists/${listId}/tasks/reorder`, {
      ordered_ids: orderedIds,
    })
    return response.data
  },
}

export default taskService

