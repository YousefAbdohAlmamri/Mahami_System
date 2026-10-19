<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
  id: { type: String, default: () => `input-${Math.random().toString(36).slice(2, 8)}` },
})

const emit = defineEmits(['update:modelValue'])

const inputValue = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
</script>

<template>
  <div class="flex flex-col gap-1">
    <label
      v-if="label"
      :for="id"
      class="text-sm font-medium text-slate-700 dark:text-slate-300"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ms-0.5" aria-hidden="true">*</span>
    </label>

    <textarea
      v-if="type === 'textarea'"
      :id="id"
      v-model="inputValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :autofocus="autofocus"
      rows="3"
      :class="[
        'w-full rounded-lg border px-3 py-2 text-sm transition-colors duration-150 resize-none',
        'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100',
        'placeholder:text-slate-400 dark:placeholder:text-slate-500',
        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
        'disabled:opacity-60 disabled:cursor-not-allowed',
        error
          ? 'border-red-400 dark:border-red-500'
          : 'border-slate-300 dark:border-slate-600',
      ]"
    />

    <input
      v-else
      :id="id"
      v-model="inputValue"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :autofocus="autofocus"
      :class="[
        'w-full rounded-lg border px-3 py-2 text-sm transition-colors duration-150',
        'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100',
        'placeholder:text-slate-400 dark:placeholder:text-slate-500',
        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
        'disabled:opacity-60 disabled:cursor-not-allowed',
        error
          ? 'border-red-400 dark:border-red-500'
          : 'border-slate-300 dark:border-slate-600',
      ]"
    />

    <p v-if="error" class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ error }}</p>
  </div>
</template>

