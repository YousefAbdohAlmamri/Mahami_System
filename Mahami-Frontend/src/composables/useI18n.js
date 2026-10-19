import { computed } from 'vue'
import { usePreferencesStore } from '@/stores/preferencesStore'
import { messages } from '@/i18n'

export function useI18n() {
  const preferencesStore = usePreferencesStore()

  function t(key, params = {}) {
    const currentMessages = messages[preferencesStore.locale] || messages.en
    let text = currentMessages[key] || messages.en[key] || key

    if (params && typeof params === 'object') {
      Object.keys(params).forEach((paramKey) => {
        text = text.replace(new RegExp(`\\{${paramKey}\\}`, 'g'), params[paramKey])
      })
    }

    return text
  }

  return {
    t,
    locale: computed(() => preferencesStore.locale),
    dir: computed(() => preferencesStore.dir),
    isRtl: computed(() => preferencesStore.isRtl),
    toggleLocale: preferencesStore.toggleLocale,
    setLocale: preferencesStore.setLocale,
  }
}

