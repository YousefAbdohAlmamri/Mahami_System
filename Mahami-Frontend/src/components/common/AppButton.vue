<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'danger', 'ghost'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
})

const variantClasses = {
  primary:
    'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-xs shadow-blue-500/25 hover:shadow-md hover:shadow-blue-500/30 focus-visible:ring-blue-500 disabled:from-blue-400 disabled:to-indigo-400',
  secondary:
    'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/70 shadow-2xs hover:shadow-xs focus-visible:ring-slate-400',
  danger:
    'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white shadow-xs shadow-red-500/25 hover:shadow-md hover:shadow-red-500/30 focus-visible:ring-red-500 disabled:from-red-400 disabled:to-rose-400',
  ghost:
    'bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 focus-visible:ring-slate-400',
}

const sizeClasses = {
  sm: 'px-3 py-1.5 text-xs font-semibold gap-1.5 rounded-lg',
  md: 'px-4 py-2 text-sm font-medium gap-2 rounded-xl',
  lg: 'px-5 py-2.5 text-base font-medium gap-2.5 rounded-xl',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center transition-all duration-150 active:scale-[0.98] select-none cursor-pointer',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900',
      'disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100',
      variantClasses[variant],
      sizeClasses[size],
    ]"
  >
    <!-- Spinner -->
    <svg
      v-if="loading"
      class="animate-spin w-4 h-4 shrink-0"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>

    <slot />
  </button>
</template>

