import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useWorkspaceStore } from '@/stores/workspaceStore'
import workspaceService from '@/services/workspaceService'

vi.mock('@/services/workspaceService', () => ({
  default: {
    getWorkspaces: vi.fn(),
    createWorkspace: vi.fn(),
    updateWorkspace: vi.fn(),
    deleteWorkspace: vi.fn(),
    getMembers: vi.fn(),
    inviteMember: vi.fn(),
    updateMemberRole: vi.fn(),
    removeMember: vi.fn(),
  },
}))

describe('workspaceStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('initializes with empty workspaces and null active workspace', () => {
    const store = useWorkspaceStore()
    expect(store.workspaces).toEqual([])
    expect(store.currentWorkspaceId).toBeNull()
    expect(store.currentWorkspace).toBeNull()
    expect(store.currentUserRole).toBeNull()
  })

  it('sets active workspace and persists to localStorage', () => {
    const store = useWorkspaceStore()
    store.workspaces = [
      { id: 10, name: 'Engineering', current_user_role: 'owner' },
      { id: 20, name: 'Marketing', current_user_role: 'member' },
    ]

    store.setActiveWorkspace(10)
    expect(store.currentWorkspaceId).toBe(10)
    expect(store.currentWorkspace?.name).toBe('Engineering')
    expect(store.currentUserRole).toBe('owner')
    expect(store.isOwner).toBe(true)
    expect(store.canDeleteWorkspace).toBe(true)
    expect(store.canManageMembers).toBe(true)
    expect(store.canCreateBoard).toBe(true)
    expect(localStorage.getItem('mahami_active_workspace')).toBe('10')

    store.setActiveWorkspace(20)
    expect(store.currentWorkspaceId).toBe(20)
    expect(store.currentUserRole).toBe('member')
    expect(store.isOwner).toBe(false)
    expect(store.canDeleteWorkspace).toBe(false)
    expect(store.canManageMembers).toBe(false)
    expect(store.canCreateBoard).toBe(true)

    store.setActiveWorkspace(null)
    expect(store.currentWorkspaceId).toBeNull()
    expect(store.currentWorkspace).toBeNull()
    expect(localStorage.getItem('mahami_active_workspace')).toBeNull()
  })

  it('correctly calculates guest role permissions', () => {
    const store = useWorkspaceStore()
    store.workspaces = [
      { id: 5, name: 'External Client', current_user_role: 'guest' },
    ]
    store.setActiveWorkspace(5)

    expect(store.currentUserRole).toBe('guest')
    expect(store.isOwner).toBe(false)
    expect(store.isAdmin).toBe(false)
    expect(store.canManageMembers).toBe(false)
    expect(store.canEditWorkspace).toBe(false)
    expect(store.canDeleteWorkspace).toBe(false)
    expect(store.canCreateBoard).toBe(false)
  })

  it('fetches workspaces and auto-selects first workspace if none active', async () => {
    const mockData = [
      { id: 1, name: 'Default Space', current_user_role: 'owner' },
      { id: 2, name: 'Second Space', current_user_role: 'admin' },
    ]
    workspaceService.getWorkspaces.mockResolvedValueOnce({ data: mockData })

    const store = useWorkspaceStore()
    await store.fetchWorkspaces()

    expect(store.workspaces.length).toBe(2)
    expect(store.currentWorkspaceId).toBe(1)
    expect(store.currentWorkspace?.name).toBe('Default Space')
  })

  it('creates workspace, prepends to list, and sets active', async () => {
    const newWs = { id: 99, name: 'New Project Team', current_user_role: 'owner' }
    workspaceService.createWorkspace.mockResolvedValueOnce({ data: newWs })

    const store = useWorkspaceStore()
    const result = await store.createWorkspace({ name: 'New Project Team' })

    expect(result.id).toBe(99)
    expect(store.workspaces[0].name).toBe('New Project Team')
    expect(store.currentWorkspaceId).toBe(99)
  })

  it('deletes workspace and selects next available', async () => {
    workspaceService.deleteWorkspace.mockResolvedValueOnce({ message: 'Deleted' })

    const store = useWorkspaceStore()
    store.workspaces = [
      { id: 1, name: 'Space 1', current_user_role: 'owner' },
      { id: 2, name: 'Space 2', current_user_role: 'owner' },
    ]
    store.setActiveWorkspace(1)

    await store.deleteWorkspace(1)

    expect(store.workspaces.length).toBe(1)
    expect(store.workspaces[0].id).toBe(2)
    expect(store.currentWorkspaceId).toBe(2)
  })
})

