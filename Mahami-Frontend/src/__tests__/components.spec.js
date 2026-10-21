import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import AppButton from '@/components/common/AppButton.vue'
import AppInput from '@/components/common/AppInput.vue'
import TaskCard from '@/components/tasks/TaskCard.vue'

describe('Common Components', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('AppButton renders label and handles click', async () => {
    const wrapper = mount(AppButton, {
      slots: { default: 'Click Me' },
    })
    expect(wrapper.text()).toContain('Click Me')
    await wrapper.trigger('click')
    expect(wrapper.emitted()).toHaveProperty('click')
  })

  it('AppButton respects disabled and loading states', () => {
    const wrapper = mount(AppButton, {
      props: { loading: true },
      slots: { default: 'Submit' },
    })
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    expect(wrapper.find('svg.animate-spin').exists()).toBe(true)
  })

  it('AppInput emits update:modelValue on typing', async () => {
    const wrapper = mount(AppInput, {
      props: {
        modelValue: '',
        label: 'Email',
      },
    })
    const input = wrapper.find('input')
    await input.setValue('test@example.com')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['test@example.com'])
  })

  it('TaskCard displays title, priority, and emits click', async () => {
    const task = {
      id: 1,
      title: 'Fix auth bug',
      description: 'Check CSRF token',
      priority: 'high',
      due_date: '2026-12-31',
      position: 0,
    }
    const wrapper = mount(TaskCard, {
      props: { task },
    })

    expect(wrapper.text()).toContain('Fix auth bug')
    expect(wrapper.text()).toContain('Check CSRF token')
    expect(wrapper.text()).toContain('2026-12-31')

    await wrapper.trigger('click')
    expect(wrapper.emitted('click')[0]).toEqual([task])
  })

  it('TaskCard displays Phase 3 labels, assignees, checklists and attachments', () => {
    const task = {
      id: 2,
      title: 'Advanced Task Feature',
      priority: 'medium',
      due_date: '2026-11-20',
      labels: [
        { id: 10, name: 'BugFix', color: '#ef4444' },
        { id: 11, name: 'UI', color: '#10b981' },
      ],
      assignees: [
        { id: 1, name: 'Yousef' },
        { id: 2, name: 'Ahmad' },
      ],
      checklist_summary: {
        total: 4,
        completed: 2,
        percentage: 50,
      },
      attachments: [
        { id: 1, file_name: 'document.pdf' },
      ],
    }
    const wrapper = mount(TaskCard, {
      props: { task },
    })

    expect(wrapper.text()).toContain('Advanced Task Feature')
    expect(wrapper.text()).toContain('BugFix')
    expect(wrapper.text()).toContain('UI')
    expect(wrapper.text()).toContain('2/4')
    expect(wrapper.text()).toContain('1') // Attachment badge count
    expect(wrapper.text()).toContain('Y') // Assignee avatar initial
    expect(wrapper.text()).toContain('A') // Assignee avatar initial
  })
})

