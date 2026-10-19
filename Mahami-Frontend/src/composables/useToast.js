import { useToastStore } from '@/stores/toastStore'

export function useToast() {
  const store = useToastStore()

  return {
    show: store.show,
    success: store.success,
    error: store.error,
    info: store.info,
    warning: store.warning,
    remove: store.remove,
    clear: store.clear,
  }
}

