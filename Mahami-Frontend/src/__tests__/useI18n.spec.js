import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useI18n } from '@/composables/useI18n'
import { usePreferencesStore } from '@/stores/preferencesStore'

describe('useI18n composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('translates strings according to active locale', () => {
    const prefs = usePreferencesStore()
    const { t } = useI18n()

    // Default Arabic
    expect(t('appName')).toBe('ميني تريلو')

    // Switch to English
    prefs.setLocale('en')
    expect(t('appName')).toBe('Mini Trello')
  })

  it('interpolates params in strings', () => {
    const prefs = usePreferencesStore()
    const { t } = useI18n()
    prefs.setLocale('en')

    expect(t('columnsCount', { count: 3 })).toBe('3 columns')
  })

  it('falls back to key if translation is missing', () => {
    const { t } = useI18n()
    expect(t('non_existent_key_xyz')).toBe('non_existent_key_xyz')
  })
})
