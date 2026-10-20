<script setup>
import { ref, computed } from 'vue'

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

const showPassword = ref(false)

const computedType = computed(() => {
  if (props.type === 'password') {
    return showPassword.value ? 'text' : 'password'
  }
  return props.type
})

const inputValue = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label
      v-if="label"
      :for="id"
      class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 select-none"
    >
      {{ label }}
      <span v-if="required" class="text-rose-500 ms-0.5" aria-hidden="true">*</span>
    </label>

    <div class="relative">
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
          'w-full rounded-xl border px-3.5 py-2.5 text-sm transition-all duration-150 resize-none shadow-2xs',
          'bg-slate-50/60 dark:bg-slate-900/60 focus:bg-white dark:focus:bg-slate-900 text-slate-900 dark:text-slate-100',
          'placeholder:text-slate-400 dark:placeholder:text-slate-500',
          'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
          'disabled:opacity-60 disabled:cursor-not-allowed',
          error
            ? 'border-rose-400 dark:border-rose-500 ring-rose-500/10'
            : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600',
        ]"
      />

      <div v-else class="relative flex items-center">
        <input
          :id="id"
          v-model="inputValue"
          :type="computedType"
          :placeholder="placeholder"
          :disabled="disabled"
          :required="required"
          :autofocus="autofocus"
          :class="[
            'w-full rounded-xl border px-3.5 py-2.5 text-sm transition-all duration-150 shadow-2xs',
            'bg-slate-50/60 dark:bg-slate-900/60 focus:bg-white dark:focus:bg-slate-900 text-slate-900 dark:text-slate-100',
            'placeholder:text-slate-400 dark:placeholder:text-slate-500',
            'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
            'disabled:opacity-60 disabled:cursor-not-allowed',
            type === 'password' ? 'pe-10' : '',
            error
              ? 'border-rose-400 dark:border-rose-500 ring-rose-500/10'
              : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600',
          ]"
        />

        <!-- Password Visibility Toggle -->
        <button
          v-if="type === 'password'"
          type="button"
          tabindex="-1"
          class="absolute end-2.5 p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer"
          :title="showPassword ? 'Hide password' : 'Show password'"
          @click="showPassword = !showPassword"
        >
          <!-- Eye open -->
          <svg
            v-if="showPassword"
            xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
          <!-- Eye slashed -->
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
            <line x1="2" x2="22" y1="2" y2="22" />
          </svg>
        </button>
      </div>
    </div>

    <p v-if="error" class="text-xs text-rose-500 dark:text-rose-400 mt-0.5 font-medium flex items-center gap-1">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
      {{ error }}
    </p>
  </div>
</template>

