import http from './http'

export const analyticsService = {
  async getBoardAnalytics(boardId) {
    const response = await http.get(`/api/boards/${boardId}/analytics`)
    return response.data
  },

  async getWorkspaceAnalytics(workspaceId) {
    const response = await http.get(`/api/workspaces/${workspaceId}/analytics`)
    return response.data
  },
}

export default analyticsService

