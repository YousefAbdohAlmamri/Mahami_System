import http from './http'

export const boardService = {
  async getBoards() {
    const response = await http.get('/api/boards')
    return response.data
  },

  async getBoard(id) {
    const response = await http.get(`/api/boards/${id}`)
    return response.data
  },

  async createBoard(data) {
    const response = await http.post('/api/boards', data)
    return response.data
  },

  async updateBoard(id, data) {
    const response = await http.put(`/api/boards/${id}`, data)
    return response.data
  },

  async deleteBoard(id) {
    const response = await http.delete(`/api/boards/${id}`)
    return response.data
  },
}

export default boardService

