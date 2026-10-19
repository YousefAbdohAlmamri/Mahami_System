import http from './http'

export const authService = {
  async getCsrfCookie() {
    try {
      await http.get('/sanctum/csrf-cookie')
    } catch {
      // Ignore if server is configured without stateful cookie requirements
    }
  },

  async register(data) {
    await this.getCsrfCookie()
    const response = await http.post('/api/register', data)
    return response.data
  },

  async login(credentials) {
    await this.getCsrfCookie()
    const response = await http.post('/api/login', credentials)
    return response.data
  },

  async logout() {
    const response = await http.post('/api/logout')
    return response.data
  },

  async getCurrentUser() {
    const response = await http.get('/api/user')
    return response.data
  },
}

export default authService

