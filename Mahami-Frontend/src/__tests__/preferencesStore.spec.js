import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePreferencesStore } from '@/stores/preferencesStore'

describe('preferencesStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initializes with default locale ar and light theme', () => {
    const prefs = usePreferencesStore()
    expect(prefs.locale).toBe('ar')
    expect(prefs.isRtl).toBe(true)
    expect(prefs.dir).toBe('rtl')
    expect(prefs.theme).toBe('light')
    expect(prefs.isDark).toBe(false)
  })

  it('toggles locale between ar and en and updates dir', () => {
    const prefs = usePreferencesStore()
    prefs.toggleLocale()
    expect(prefs.locale).toBe('en')
    expect(prefs.isRtl).toBe(false)
    expect(prefs.dir).toBe('ltr')
    expect(localStorage.getItem('mini_trello_locale')).toBe('en')

    prefs.toggleLocale()
    expect(prefs.locale).toBe('ar')
    expect(prefs.isRtl).toBe(true)
    expect(prefs.dir).toBe('rtl')
  })

  it('toggles theme between light and dark', () => {
    const prefs = usePreferencesStore()
    expect(document.documentElement.classList.contains('dark')).toBe(false)
    expect(document.documentElement.getAttribute('data-theme')).toBe('light')

    prefs.toggleTheme()
    expect(prefs.theme).toBe('dark')
    expect(prefs.isDark).toBe(true)
    expect(localStorage.getItem('mini_trello_theme')).toBe('dark')
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')

    prefs.toggleTheme()
    expect(prefs.theme).toBe('light')
    expect(prefs.isDark).toBe(false)
    expect(localStorage.getItem('mini_trello_theme')).toBe('light')
    expect(document.documentElement.classList.contains('dark')).toBe(false)
    expect(document.documentElement.getAttribute('data-theme')).toBe('light')
  })
})

