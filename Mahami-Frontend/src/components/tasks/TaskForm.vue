<script setup>
import { reactive, watch } from 'vue'
import { useI18n } from '@/composables/useI18n'
import AppModal from '@/components/common/AppModal.vue'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  task: {
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
  priority: 'medium',
  due_date: '',
})

const errors = reactive({
  title: '',
})

watch(
  () => props.task,
  (val) => {
    if (val) {
      form.title = val.title || ''
      form.description = val.description || ''
      form.priority = val.priority || 'medium'
      form.due_date = val.due_date || ''
    } else {
      form.title = ''
      form.description = ''
      form.priority = 'medium'
      form.due_date = ''
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
    priority: form.priority,
    due_date: form.due_date ? form.due_date : null,
  })
}
</script>

<template>
  <AppModal
    :title="task ? t('editTask') : t('addTask')"
    :close-on-backdrop="!loading"
    @close="emit('close')"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <AppInput
        v-model="form.title"
        :label="t('taskTitle')"
        :placeholder="t('taskTitlePlaceholder')"
        :error="errors.title"
        required
        autofocus
      />

      <AppInput
        v-model="form.description"
        type="textarea"
        :label="t('taskDescription')"
        :placeholder="t('taskDescriptionPlaceholder')"
      />

      <!-- Priority selection -->
      <div class="flex flex-col gap-1.5">
        <label class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 select-none">
          {{ t('priority') }}
        </label>
        <div class="grid grid-cols-3 gap-2.5">
          <button
            type="button"
            class="px-3 py-2 text-xs font-bold rounded-xl border transition-all text-center cursor-pointer flex items-center justify-center gap-1.5"
            :class="[
              form.priority === 'low'
                ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:border-emerald-500 dark:text-emerald-300 ring-2 ring-emerald-500/20 shadow-xs'
                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300',
            ]"
            @click="form.priority = 'low'"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500" />
            {{ t('low') }}
          </button>

          <button
            type="button"
            class="px-3 py-2 text-xs font-bold rounded-xl border transition-all text-center cursor-pointer flex items-center justify-center gap-1.5"
            :class="[
              form.priority === 'medium'
                ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:border-amber-500 dark:text-amber-300 ring-2 ring-amber-500/20 shadow-xs'
                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300',
            ]"
            @click="form.priority = 'medium'"
          >
            <span class="w-2 h-2 rounded-full bg-amber-500" />
            {{ t('medium') }}
          </button>

          <button
            type="button"
            class="px-3 py-2 text-xs font-bold rounded-xl border transition-all text-center cursor-pointer flex items-center justify-center gap-1.5"
            :class="[
              form.priority === 'high'
                ? 'border-rose-500 bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:border-rose-500 dark:text-rose-300 ring-2 ring-rose-500/20 shadow-xs'
                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300',
            ]"
            @click="form.priority = 'high'"
          >
            <span class="w-2 h-2 rounded-full bg-rose-500" />
            {{ t('high') }}
          </button>
        </div>
      </div>

      <!-- Due date -->
      <AppInput
        v-model="form.due_date"
        type="date"
        :label="t('dueDate')"
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

