import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useToastStore } from '@/stores/toastStore'

describe('toastStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('adds success toast to queue', () => {
    const toastStore = useToastStore()
    toastStore.success('Saved successfully!')

    expect(toastStore.toasts.length).toBe(1)
    expect(toastStore.toasts[0].message).toBe('Saved successfully!')
    expect(toastStore.toasts[0].type).toBe('success')
  })

  it('removes toast by id', () => {
    const toastStore = useToastStore()
    const id = toastStore.error('Something went wrong')

    expect(toastStore.toasts.length).toBe(1)
    toastStore.remove(id)
    expect(toastStore.toasts.length).toBe(0)
  })

  it('clears all toasts', () => {
    const toastStore = useToastStore()
    toastStore.info('Toast 1')
    toastStore.warning('Toast 2')

    expect(toastStore.toasts.length).toBe(2)
    toastStore.clear()
    expect(toastStore.toasts.length).toBe(0)
  })
})
