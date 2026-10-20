<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()
const toast = useToast()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  general: '',
})

function clearErrors() {
  errors.name = ''
  errors.email = ''
  errors.password = ''
  errors.password_confirmation = ''
  errors.general = ''
}

async function handleSubmit() {
  clearErrors()

  let hasError = false
  if (!form.name) {
    errors.name = t('validationError')
    hasError = true
  }
  if (!form.email) {
    errors.email = t('validationError')
    hasError = true
  }
  if (!form.password) {
    errors.password = t('validationError')
    hasError = true
  }
  if (form.password && form.password.length < 8) {
    errors.password = t('validationError')
    hasError = true
  }
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = t('validationError')
    hasError = true
  }

  if (hasError) return

  try {
    await authStore.register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })

    toast.success(t('createdSuccessfully'))
    router.push({ name: 'boards' })
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errors || {}
      if (serverErrors.name) errors.name = serverErrors.name[0]
      if (serverErrors.email) errors.email = serverErrors.email[0]
      if (serverErrors.password) errors.password = serverErrors.password[0]
      if (serverErrors.password_confirmation) errors.password_confirmation = serverErrors.password_confirmation[0]
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
        {{ t('register') }}
      </h2>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
        {{ t('getStarted') }}
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
        v-model="form.name"
        type="text"
        :label="t('name')"
        :error="errors.name"
        placeholder="John Doe"
        required
        autofocus
      />

      <AppInput
        v-model="form.email"
        type="email"
        :label="t('email')"
        :error="errors.email"
        placeholder="user@example.com"
        required
      />

      <AppInput
        v-model="form.password"
        type="password"
        :label="t('password')"
        :error="errors.password"
        placeholder="••••••••"
        required
      />

      <AppInput
        v-model="form.password_confirmation"
        type="password"
        :label="t('confirmPassword')"
        :error="errors.password_confirmation"
        placeholder="••••••••"
        required
      />

      <AppButton
        type="submit"
        variant="primary"
        class="w-full mt-3 py-2.5"
        :loading="authStore.isLoading"
      >
        {{ authStore.isLoading ? t('registering') : t('register') }}
      </AppButton>
    </form>

    <div class="mt-6 text-center text-xs sm:text-sm">
      <router-link
        :to="{ name: 'login' }"
        class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
      >
        {{ t('alreadyHaveAccount') }}
      </router-link>
    </div>
  </div>
</template>

