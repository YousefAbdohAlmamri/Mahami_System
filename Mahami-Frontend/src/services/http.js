import axios from 'axios'

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

export const http = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

function getCookie(name) {
  if (typeof document === 'undefined') return null
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift())
  return null
}

// Request interceptor: attach bearer token and CSRF token if present
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('mini_trello_token')
  if (token && !config.headers.Authorization) {
    config.headers.Authorization = `Bearer ${token}`
  }

  const xsrfToken = getCookie('XSRF-TOKEN')
  if (xsrfToken && !config.headers['X-XSRF-TOKEN']) {
    config.headers['X-XSRF-TOKEN'] = xsrfToken
  }

  return config
})

// Response interceptor: unified error formatting
http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('mini_trello_token')
      localStorage.removeItem('mini_trello_user')
    }

    return Promise.reject(error)
  }
)

export default http

