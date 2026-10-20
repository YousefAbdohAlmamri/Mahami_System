<script setup>
import { reactive, watch } from 'vue'
import { useI18n } from '@/composables/useI18n'
import AppModal from '@/components/common/AppModal.vue'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  workspace: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['save', 'close'])
const { t } = useI18n()

const form = reactive({
  name: '',
  description: '',
})

const errors = reactive({
  name: '',
})

watch(
  () => props.workspace,
  (val) => {
    if (val) {
      form.name = val.name || ''
      form.description = val.description || ''
    } else {
      form.name = ''
      form.description = ''
    }
    errors.name = ''
  },
  { immediate: true },
)

function handleSubmit() {
  errors.name = ''
  if (!form.name.trim()) {
    errors.name = t('validationError')
    return
  }

  emit('save', {
    name: form.name.trim(),
    description: form.description ? form.description.trim() : null,
  })
}
</script>

<template>
  <AppModal
    :title="workspace ? t('editWorkspace') : t('createWorkspace')"
    size="md"
    @close="emit('close')"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <AppInput
        v-model="form.name"
        :label="t('workspaceName')"
        :placeholder="t('workspaceNamePlaceholder')"
        :error="errors.name"
        required
      />

      <div class="space-y-1">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
          {{ t('workspaceDescription') }}
        </label>
        <textarea
          v-model="form.description"
          rows="3"
          :placeholder="t('workspaceDescriptionPlaceholder')"
          class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all resize-none"
        />
      </div>

      <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
        <AppButton
          type="button"
          variant="secondary"
          @click="emit('close')"
        >
          {{ t('cancel') }}
        </AppButton>
        <AppButton
          type="submit"
          variant="primary"
          :loading="loading"
        >
          {{ t('save') }}
        </AppButton>
      </div>
    </form>
  </AppModal>
</template>
