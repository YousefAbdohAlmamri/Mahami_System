import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { router } from '@/router/index.js'
import { usePreferencesStore } from '@/stores/preferencesStore.js'
import App from './App.vue'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Apply saved preferences (theme / locale / dir) before first paint
const prefs = usePreferencesStore()
prefs.applyPreferences()

app.mount('#app')
