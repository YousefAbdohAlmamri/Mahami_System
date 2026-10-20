<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { t } = useI18n()
const toast = useToast()

const form = reactive({
  email: '',
  password: '',
})

const errors = reactive({
  email: '',
  password: '',
  general: '',
})

function clearErrors() {
  errors.email = ''
  errors.password = ''
  errors.general = ''
}

function fillDemoAccount() {
  form.email = 'test@example.com'
  form.password = 'password'
  clearErrors()
}

async function handleSubmit() {
  clearErrors()

  let hasError = false
  if (!form.email) {
    errors.email = t('validationError')
    hasError = true
  }
  if (!form.password) {
    errors.password = t('validationError')
    hasError = true
  }

  if (hasError) return

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
    })

    toast.success(t('welcomeBack'))
    const redirect = route.query.redirect || '/boards'
    router.push(redirect)
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errors || {}
      if (serverErrors.email) errors.email = serverErrors.email[0]
      if (serverErrors.password) errors.password = serverErrors.password[0]
    } else if (err.response?.status === 429) {
      errors.general = err.response.data.message || t('genericError')
    } else if (!err.response) {
      errors.general = t('networkError')
    } else {
      errors.general = err.response?.data?.message || t('genericError')
    }
  }
}
</script>

<template>
  <div>
    <div class="mb-6 text-center">
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
        {{ t('login') }}
      </h2>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
        {{ t('welcomeBack') }}
      </p>
    </div>

    <!-- General Error Banner -->
    <div
      v-if="errors.general"
      class="mb-5 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs sm:text-sm text-rose-600 dark:text-rose-400 flex items-start gap-2.5"
      role="alert"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
      <span>{{ errors.general }}</span>
    </div>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <AppInput
        v-model="form.email"
        type="email"
        :label="t('email')"
        :error="errors.email"
        placeholder="user@example.com"
        required
        autofocus
      />

      <AppInput
        v-model="form.password"
        type="password"
        :label="t('password')"
        :error="errors.password"
        placeholder="••••••••"
        required
      />

      <!-- Demo quick fill button -->
      <div class="flex items-center justify-between text-xs pt-1">
        <button
          type="button"
          class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium inline-flex items-center gap-1 cursor-pointer transition-colors"
          @click="fillDemoAccount"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <line x1="19" y1="8" x2="19" y2="14" />
            <line x1="22" y1="11" x2="16" y2="11" />
          </svg>
          <span>Demo Account</span>
        </button>
      </div>

      <AppButton
        type="submit"
        variant="primary"
        class="w-full mt-3 py-2.5"
        :loading="authStore.isLoading"
      >
        {{ authStore.isLoading ? t('loggingIn') : t('login') }}
      </AppButton>
    </form>

    <div class="mt-6 text-center text-xs sm:text-sm">
      <router-link
        :to="{ name: 'register' }"
        class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
      >
        {{ t('dontHaveAccount') }}
      </router-link>
    </div>
  </div>
</template>

