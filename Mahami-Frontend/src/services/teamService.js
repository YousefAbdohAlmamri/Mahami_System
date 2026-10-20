import http from './http'

export const teamService = {
  async getTeams(workspaceId) {
    const response = await http.get(`/api/workspaces/${workspaceId}/teams`)
    return response.data
  },

  async createTeam(workspaceId, data) {
    const response = await http.post(`/api/workspaces/${workspaceId}/teams`, data)
    return response.data
  },

  async getTeam(teamId) {
    const response = await http.get(`/api/teams/${teamId}`)
    return response.data
  },

  async updateTeam(teamId, data) {
    const response = await http.put(`/api/teams/${teamId}`, data)
    return response.data
  },

  async deleteTeam(teamId) {
    const response = await http.delete(`/api/teams/${teamId}`)
    return response.data
  },

  async getTeamMembers(teamId) {
    const response = await http.get(`/api/teams/${teamId}/members`)
    return response.data
  },

  async addTeamMember(teamId, data) {
    const response = await http.post(`/api/teams/${teamId}/members`, data)
    return response.data
  },

  async updateTeamMemberRole(teamId, userId, data) {
    const response = await http.put(`/api/teams/${teamId}/members/${userId}`, data)
    return response.data
  },

  async removeTeamMember(teamId, userId) {
    const response = await http.delete(`/api/teams/${teamId}/members/${userId}`)
    return response.data
  },

  async getBoardTeams(boardId) {
    const response = await http.get(`/api/boards/${boardId}/teams`)
    return response.data
  },

  async assignBoardTeam(boardId, data) {
    const response = await http.post(`/api/boards/${boardId}/teams`, data)
    return response.data
  },

  async updateBoardTeam(boardId, teamId, data) {
    const response = await http.put(`/api/boards/${boardId}/teams/${teamId}`, data)
    return response.data
  },

  async removeBoardTeam(boardId, teamId) {
    const response = await http.delete(`/api/boards/${boardId}/teams/${teamId}`)
    return response.data
  },
}

export default teamService
