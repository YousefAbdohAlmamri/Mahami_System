import http from './http'

export const workspaceService = {
  async getWorkspaces() {
    const response = await http.get('/api/workspaces')
    return response.data
  },

  async getWorkspace(id) {
    const response = await http.get(`/api/workspaces/${id}`)
    return response.data
  },

  async createWorkspace(data) {
    const response = await http.post('/api/workspaces', data)
    return response.data
  },

  async updateWorkspace(id, data) {
    const response = await http.put(`/api/workspaces/${id}`, data)
    return response.data
  },

  async deleteWorkspace(id) {
    const response = await http.delete(`/api/workspaces/${id}`)
    return response.data
  },

  async getMembers(workspaceId) {
    const response = await http.get(`/api/workspaces/${workspaceId}/members`)
    return response.data
  },

  async inviteMember(workspaceId, data) {
    const response = await http.post(`/api/workspaces/${workspaceId}/members/invite`, data)
    return response.data
  },

  async updateMemberRole(workspaceId, userId, data) {
    const response = await http.put(`/api/workspaces/${workspaceId}/members/${userId}`, data)
    return response.data
  },

  async removeMember(workspaceId, userId) {
    const response = await http.delete(`/api/workspaces/${workspaceId}/members/${userId}`)
    return response.data
  },

  async transferOwnership(workspaceId, data) {
    const response = await http.post(`/api/workspaces/${workspaceId}/transfer-ownership`, data)
    return response.data
  },

  async getInvitation(token) {
    const response = await http.get(`/api/invitations/${token}`)
    return response.data
  },

  async acceptInvitation(token) {
    const response = await http.post(`/api/invitations/${token}/accept`)
    return response.data
  },

  async declineInvitation(token) {
    const response = await http.post(`/api/invitations/${token}/decline`)
    return response.data
  },
}

export default workspaceService

