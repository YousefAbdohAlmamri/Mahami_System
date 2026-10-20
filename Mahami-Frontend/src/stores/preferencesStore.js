import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usePreferencesStore = defineStore('preferences', () => {
  // 1. Language & Direction
  const savedLocale = localStorage.getItem('mini_trello_locale') || 'ar'
  const locale = ref(savedLocale)

  const isRtl = computed(() => locale.value === 'ar')
  const dir = computed(() => (isRtl.value ? 'rtl' : 'ltr'))

  // 2. Theme (light / dark)
  const getInitialTheme = () => {
    const saved = localStorage.getItem('mini_trello_theme')
    if (saved === 'dark' || saved === 'light') return saved
    if (typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark'
    }
    return 'light'
  }

  const theme = ref(getInitialTheme())
  const isDark = computed(() => theme.value === 'dark')

  // Apply DOM side effects
  function applyPreferences() {
    if (typeof document !== 'undefined') {
      document.documentElement.lang = locale.value
      document.documentElement.dir = dir.value

      if (theme.value === 'dark') {
        document.documentElement.classList.add('dark')
        document.documentElement.setAttribute('data-theme', 'dark')
      } else {
        document.documentElement.classList.remove('dark')
        document.documentElement.setAttribute('data-theme', 'light')
      }
    }
  }

  function setLocale(newLocale) {
    if (['ar', 'en'].includes(newLocale)) {
      locale.value = newLocale
      localStorage.setItem('mini_trello_locale', newLocale)
      applyPreferences()
    }
  }

  function toggleLocale() {
    setLocale(locale.value === 'ar' ? 'en' : 'ar')
  }

  function setTheme(newTheme) {
    if (['light', 'dark'].includes(newTheme)) {
      theme.value = newTheme
      localStorage.setItem('mini_trello_theme', newTheme)
      applyPreferences()
    }
  }

  function toggleTheme() {
    setTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  // Initial application
  applyPreferences()

  return {
    locale,
    dir,
    isRtl,
    theme,
    isDark,
    setLocale,
    toggleLocale,
    setTheme,
    toggleTheme,
    applyPreferences,
  }
})

