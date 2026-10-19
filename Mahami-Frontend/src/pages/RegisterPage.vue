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
    } else {
      errors.general = err.response?.data?.message || t('genericError')
    }
  }
}
</script>

<template>
  <div>
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2 text-center">
      {{ t('register') }}
    </h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 text-center">
      {{ t('getStarted') }}
    </p>

    <div
      v-if="errors.general"
      class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400"
      role="alert"
    >
      {{ errors.general }}
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
        class="w-full mt-2"
        :loading="authStore.isLoading"
      >
        {{ authStore.isLoading ? t('registering') : t('register') }}
      </AppButton>
    </form>

    <div class="mt-6 text-center text-sm">
      <router-link
        :to="{ name: 'login' }"
        class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
      >
        {{ t('alreadyHaveAccount') }}
      </router-link>
    </div>
  </div>
</template>
