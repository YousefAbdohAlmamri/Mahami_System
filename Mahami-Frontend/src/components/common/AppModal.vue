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
    <!-- Backdrop & Modal Container -->
    <div
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
      role="dialog"
      aria-modal="true"
    >
      <!-- Overlay -->
      <transition
        appear
        enter-active-class="transition-opacity duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
          @click="closeOnBackdrop && close()"
        />
      </transition>

      <!-- Panel -->
      <transition
        appear
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
      >
        <div
          ref="modalEl"
          :class="[
            'relative w-full bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl shadow-2xl z-10',
            'border border-slate-200/80 dark:border-slate-700',
            'flex flex-col max-h-[92vh] sm:max-h-[90vh]',
            sizeClasses[size],
          ]"
        >
          <!-- Header -->
          <div
            v-if="title || $slots.header"
            class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-700/80 shrink-0"
          >
            <slot name="header">
              <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight">{{ title }}</h2>
            </slot>
            <button
              data-close
              class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors -me-1 cursor-pointer"
              @click="close"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Body -->
          <div class="overflow-y-auto flex-1 px-4 sm:px-6 py-5">
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-slate-100 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/40 rounded-b-2xl sm:rounded-b-3xl shrink-0"
          >
            <slot name="footer" />
          </div>
        </div>
      </transition>
    </div>
  </teleport>
</template>

