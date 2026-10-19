<script setup>
import { useToastStore } from '@/stores/toastStore'

const toastStore = useToastStore()

const typeConfig = {
  success: {
    icon: `<path d="M20 6 9 17l-5-5"/>`,
    bg: 'bg-emerald-500',
    bar: 'bg-emerald-300',
  },
  error: {
    icon: `<path d="M18 6 6 18M6 6l12 12"/>`,
    bg: 'bg-red-500',
    bar: 'bg-red-300',
  },
  warning: {
    icon: `<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>`,
    bg: 'bg-amber-500',
    bar: 'bg-amber-300',
  },
  info: {
    icon: `<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>`,
    bg: 'bg-blue-500',
    bar: 'bg-blue-300',
  },
}
</script>

<template>
  <!-- Fixed container – always top-end (right in LTR, left in RTL) -->
  <div class="fixed top-4 end-4 z-[100] flex flex-col gap-2 pointer-events-none w-80">
    <transition-group
      name="toast"
      tag="div"
      class="flex flex-col gap-2"
    >
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        class="flex items-start gap-3 rounded-xl shadow-lg overflow-hidden text-white pointer-events-auto"
        :class="typeConfig[toast.type]?.bg ?? 'bg-slate-700'"
        role="alert"
      >
        <!-- Colored bar -->
        <div class="w-1 self-stretch shrink-0" :class="typeConfig[toast.type]?.bar" />

        <!-- Icon -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5 shrink-0 mt-2.5"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          v-html="typeConfig[toast.type]?.icon"
        />

        <!-- Message -->
        <p class="flex-1 py-3 text-sm leading-relaxed">{{ toast.message }}</p>

        <!-- Close -->
        <button
          class="p-2 mt-1 opacity-80 hover:opacity-100 transition-opacity shrink-0"
          @click="toastStore.remove(toast.id)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-8px) scale(0.96);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>

