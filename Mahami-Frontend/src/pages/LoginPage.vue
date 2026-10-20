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
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2 text-center">
      {{ t('login') }}
    </h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 text-center">
      {{ t('welcomeBack') }}
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

      <AppButton
        type="submit"
        variant="primary"
        class="w-full mt-2"
        :loading="authStore.isLoading"
      >
        {{ authStore.isLoading ? t('loggingIn') : t('login') }}
      </AppButton>
    </form>

    <div class="mt-6 text-center text-sm">
      <router-link
        :to="{ name: 'register' }"
        class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
      >
        {{ t('dontHaveAccount') }}
      </router-link>
    </div>
  </div>
</template>

