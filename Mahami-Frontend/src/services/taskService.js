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

  // ── Labels ──────────────────────────────────────────────────
  async getBoardLabels(boardId) {
    const response = await http.get(`/api/boards/${boardId}/labels`)
    return response.data
  },

  async createBoardLabel(boardId, data) {
    const response = await http.post(`/api/boards/${boardId}/labels`, data)
    return response.data
  },

  async deleteBoardLabel(boardId, labelId) {
    const response = await http.delete(`/api/boards/${boardId}/labels/${labelId}`)
    return response.data
  },

  async syncTaskLabels(taskId, labelIds) {
    const response = await http.post(`/api/tasks/${taskId}/labels`, { label_ids: labelIds })
    return response.data
  },

  // ── Assignees ───────────────────────────────────────────────
  async syncTaskAssignees(taskId, userIds) {
    const response = await http.post(`/api/tasks/${taskId}/assignees`, { user_ids: userIds })
    return response.data
  },

  // ── Checklists ──────────────────────────────────────────────
  async createChecklist(taskId, data) {
    const response = await http.post(`/api/tasks/${taskId}/checklists`, data)
    return response.data
  },

  async deleteChecklist(checklistId) {
    const response = await http.delete(`/api/checklists/${checklistId}`)
    return response.data
  },

  async addChecklistItem(checklistId, data) {
    const response = await http.post(`/api/checklists/${checklistId}/items`, data)
    return response.data
  },

  async updateChecklistItem(itemId, data) {
    const response = await http.put(`/api/checklist-items/${itemId}`, data)
    return response.data
  },

  async deleteChecklistItem(itemId) {
    const response = await http.delete(`/api/checklist-items/${itemId}`)
    return response.data
  },

  // ── Attachments ─────────────────────────────────────────────
  async uploadAttachment(taskId, file) {
    const formData = new FormData()
    formData.append('file', file)
    const response = await http.post(`/api/tasks/${taskId}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  async deleteAttachment(attachmentId) {
    const response = await http.delete(`/api/attachments/${attachmentId}`)
    return response.data
  },
}

export default taskService
