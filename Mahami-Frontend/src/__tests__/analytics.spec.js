import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import AnalyticsModal from '@/components/analytics/AnalyticsModal.vue'
import analyticsService from '@/services/analyticsService'

vi.mock('@/services/analyticsService', () => ({
  default: {
    getBoardAnalytics: vi.fn(),
    getWorkspaceAnalytics: vi.fn(),
  },
}))

describe('AnalyticsModal Component', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders KPI metrics and priority distribution when loaded', async () => {
    const mockAnalytics = {
      board_id: 1,
      total_tasks: 10,
      completed_tasks: 7,
      completion_rate: 70,
      overdue_tasks_count: 2,
      priority_breakdown: { high: 4, medium: 4, low: 2 },
      status_breakdown: [
        { list_id: 1, list_title: 'To Do', task_count: 3 },
        { list_id: 2, list_title: 'Done', task_count: 7 },
      ],
      member_workload: [
        { user_id: 1, name: 'Alice', assigned_tasks: 5 },
      ],
    }

    analyticsService.getBoardAnalytics.mockResolvedValueOnce({ data: mockAnalytics })

    const wrapper = mount(AnalyticsModal, {
      props: { boardId: 1 },
      global: {
        stubs: {
          teleport: true,
        },
      },
    })

    await flushPromises()

    expect(wrapper.text()).toContain('10') // total tasks
    expect(wrapper.text()).toContain('7')  // completed tasks
    expect(wrapper.text()).toContain('70%') // completion rate
    expect(wrapper.text()).toContain('2')  // overdue
    expect(wrapper.text()).toContain('Alice') // member workload
  })
})
