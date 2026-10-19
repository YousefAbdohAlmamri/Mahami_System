import http from './http'

export const listService = {
  async createList(boardId, data) {
    const response = await http.post(`/api/boards/${boardId}/lists`, data)
    return response.data
  },

  async updateList(id, data) {
    const response = await http.put(`/api/lists/${id}`, data)
    return response.data
  },

  async deleteList(id) {
    const response = await http.delete(`/api/lists/${id}`)
    return response.data
  },

  async reorderLists(boardId, orderedIds) {
    const response = await http.patch(`/api/boards/${boardId}/lists/reorder`, {
      ordered_ids: orderedIds,
    })
    return response.data
  },
}

export default listService

