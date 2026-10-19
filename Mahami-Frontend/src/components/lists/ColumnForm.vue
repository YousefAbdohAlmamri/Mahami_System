<script setup>
import { reactive, watch } from 'vue'
import { useI18n } from '@/composables/useI18n'
import AppModal from '@/components/common/AppModal.vue'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  list: {
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
})

const errors = reactive({
  title: '',
})

watch(
  () => props.list,
  (val) => {
    form.title = val?.title || ''
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
  })
}
</script>

<template>
  <AppModal
    size="sm"
    :title="list ? t('editColumn') : t('addColumn')"
    :close-on-backdrop="!loading"
    @close="emit('close')"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <AppInput
        v-model="form.title"
        :label="t('columnTitle')"
        :placeholder="t('columnTitlePlaceholder')"
        :error="errors.title"
        required
        autofocus
      />

      <div class="flex justify-end gap-3 pt-2">
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

