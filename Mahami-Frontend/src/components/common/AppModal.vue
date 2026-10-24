<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";

const props = defineProps({
  title: { type: String, default: "" },
  size: {
    type: String,
    default: "md",
    validator: (v) => ["sm", "md", "lg", "xl", "2xl"].includes(v),
  },
  closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(["close"]);

const modalEl = ref(null);

// Lock page scroll while any modal is open (counter supports stacked modals)
function lockScroll() {
  const n = Number(document.body.dataset.modalCount || 0) + 1;
  document.body.dataset.modalCount = String(n);
  if (n === 1) {
    document.body.dataset.prevOverflow = document.body.style.overflow || "";
    document.body.style.overflow = "hidden";
  }
}

function unlockScroll() {
  const n = Math.max(0, Number(document.body.dataset.modalCount || 0) - 1);
  document.body.dataset.modalCount = String(n);
  if (n === 0) {
    document.body.style.overflow = document.body.dataset.prevOverflow || "";
    delete document.body.dataset.prevOverflow;
  }
}

const sizeClasses = {
  sm: "max-w-sm",
  md: "max-w-md",
  lg: "max-w-lg",
  xl: "max-w-2xl",
  "2xl": "max-w-4xl",
};

function close() {
  emit("close");
}

function onKeydown(e) {
  if (e.key === "Escape") close();
}

onMounted(async () => {
  document.addEventListener("keydown", onKeydown);
  lockScroll();
  await nextTick();
  // Auto-focus the first field on desktop only – on phones it would pop the
  // keyboard open immediately and cover half of the sheet.
  const isTouch = window.matchMedia?.("(pointer: coarse)").matches;
  if (!isTouch) {
    const first = modalEl.value?.querySelector(
      "input, textarea, select, button:not([data-close])",
    );
    first?.focus();
  }
});

onBeforeUnmount(() => {
  document.removeEventListener("keydown", onKeydown);
  unlockScroll();
});
</script>

<template>
  <teleport to="body">
    <!-- Backdrop & Modal Container -->
    <div
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto overscroll-contain"
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
        enter-from-class="opacity-0 sm:scale-95 translate-y-10 sm:translate-y-0"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 sm:scale-95 translate-y-10 sm:translate-y-0"
      >
        <div
          ref="modalEl"
          :class="[
            'relative w-full bg-white dark:bg-slate-800 rounded-t-3xl rounded-b-none sm:rounded-3xl shadow-2xl z-10',
            'border border-slate-200/80 dark:border-slate-700',
            'flex flex-col max-h-[92dvh] sm:max-h-[90dvh]',
            sizeClasses[size],
          ]"
        >
          <!-- Header -->
          <div
            v-if="title || $slots.header"
            class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-700/80 shrink-0"
          >
            <slot name="header">
              <h2
                class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight min-w-0 pe-3 line-clamp-2 break-words"
              >
                {{ title }}
              </h2>
            </slot>
            <button
              data-close
              class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors -me-1 cursor-pointer"
              @click="close"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div
            class="overflow-y-auto overscroll-contain flex-1 min-h-0 px-4 sm:px-6 py-4 sm:py-5"
          >
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-slate-100 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/40 sm:rounded-b-3xl shrink-0"
          >
            <slot name="footer" />
          </div>
        </div>
      </transition>
    </div>
  </teleport>
</template>
