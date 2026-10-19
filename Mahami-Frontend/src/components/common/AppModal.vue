<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  title: { type: String, default: '' },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
  closeOnBackdrop: { type: Boolean, default: true },
})

const emit = defineEmits(['close'])

const modalEl = ref(null)

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-2xl',
}

function close() {
  emit('close')
}

function onKeydown(e) {
  if (e.key === 'Escape') close()
}

onMounted(async () => {
  document.addEventListener('keydown', onKeydown)
  await nextTick()
  // Focus first focusable element inside modal
  const first = modalEl.value?.querySelector(
    'input, textarea, select, button:not([data-close])',
  )
  first?.focus()
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <teleport to="body">
    <!-- Backdrop -->
    <div
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
    >
      <!-- Overlay -->
      <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        @click="closeOnBackdrop && close()"
      />

      <!-- Panel -->
      <div
        ref="modalEl"
        :class="[
          'relative w-full bg-white dark:bg-slate-800 rounded-2xl shadow-xl z-10',
          'flex flex-col max-h-[90vh]',
          sizeClasses[size],
        ]"
      >
        <!-- Header -->
        <div
          v-if="title || $slots.header"
          class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700 shrink-0"
        >
          <slot name="header">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ title }}</h2>
          </slot>
          <button
            data-close
            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors -me-1"
            @click="close"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-4">
          <slot />
        </div>

        <!-- Footer -->
        <div
          v-if="$slots.footer"
          class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 shrink-0"
        >
          <slot name="footer" />
        </div>
      </div>
    </div>
  </teleport>
</template>

