import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('mini_trello_user') || 'null'))
  const token = ref(localStorage.getItem('mini_trello_token') || null)
  const isLoading = ref(false)
  const isInitialized = ref(false)

  const isAuthenticated = computed(() => !!user.value)

  async function register(data) {
    isLoading.value = true
    try {
      const response = await authService.register(data)
      setAuthData(response.user, response.token)
      return response
    } finally {
      isLoading.value = false
    }
  }

  async function login(credentials) {
    isLoading.value = true
    try {
      const response = await authService.login(credentials)
      setAuthData(response.user, response.token)
      return response
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    isLoading.value = true
    try {
      await authService.logout()
    } catch {
      // Continue client cleanup even if network fails
    } finally {
      clearAuthData()
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) {
      isInitialized.value = true
      return null
    }

    isLoading.value = true
    try {
      const response = await authService.getCurrentUser()
      user.value = response.user
      localStorage.setItem('mini_trello_user', JSON.stringify(response.user))
      return response.user
    } catch {
      clearAuthData()
      return null
    } finally {
      isLoading.value = false
      isInitialized.value = true
    }
  }

  function setAuthData(userData, tokenValue) {
    user.value = userData
    token.value = tokenValue
    if (userData) {
      localStorage.setItem('mini_trello_user', JSON.stringify(userData))
    }
    if (tokenValue) {
      localStorage.setItem('mini_trello_token', tokenValue)
    }
  }

  function clearAuthData() {
    user.value = null
    token.value = null
    localStorage.removeItem('mini_trello_user')
    localStorage.removeItem('mini_trello_token')
  }

  return {
    user,
    token,
    isLoading,
    isInitialized,
    isAuthenticated,
    register,
    login,
    logout,
    fetchUser,
    clearAuthData,
  }
})

