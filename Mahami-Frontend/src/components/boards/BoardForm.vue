<script setup>
import { ref, reactive, watch } from 'vue'
import { useI18n } from '@/composables/useI18n'
import AppModal from '@/components/common/AppModal.vue'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  board: {
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
  title: '',
  description: '',
})

const errors = reactive({
  title: '',
})

watch(
  () => props.board,
  (val) => {
    if (val) {
      form.title = val.title || ''
      form.description = val.description || ''
    } else {
      form.title = ''
      form.description = ''
    }
    errors.title = ''
  },
  { immediate: true },
)

function handleSubmit() {
  errors.title = ''
  if (!form.title.trim()) {
    errors.title = t('validationError')
    return
  }

  emit('save', {
    title: form.title.trim(),
    description: form.description ? form.description.trim() : null,
  })
}
</script>

<template>
  <AppModal
    :title="board ? t('editBoard') : t('createBoard')"
    :close-on-backdrop="!loading"
    @close="emit('close')"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <AppInput
        v-model="form.title"
        :label="t('boardTitle')"
        :placeholder="t('boardTitlePlaceholder')"
        :error="errors.title"
        required
        autofocus
      />

      <AppInput
        v-model="form.description"
        type="textarea"
        :label="t('boardDescription')"
        :placeholder="t('boardDescriptionPlaceholder')"
      />

      <div class="flex justify-end gap-3 pt-3">
        <AppButton
          variant="secondary"
          :disabled="loading"
          @click="emit('close')"
        >
          {{ t('cancel') }}
        </AppButton>
        <AppButton
          type="submit"
          variant="primary"
          :loading="loading"
        >
          {{ loading ? t('saving') : t('save') }}
        </AppButton>
      </div>
    </form>
  </AppModal>
</template>

