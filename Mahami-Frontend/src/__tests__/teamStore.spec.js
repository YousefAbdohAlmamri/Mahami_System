import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useTeamStore } from '@/stores/teamStore'
import teamService from '@/services/teamService'

vi.mock('@/services/teamService', () => ({
  default: {
    getTeams: vi.fn(),
    createTeam: vi.fn(),
    getTeam: vi.fn(),
    updateTeam: vi.fn(),
    deleteTeam: vi.fn(),
    getTeamMembers: vi.fn(),
    addTeamMember: vi.fn(),
    updateTeamMemberRole: vi.fn(),
    removeTeamMember: vi.fn(),
    getBoardTeams: vi.fn(),
    assignBoardTeam: vi.fn(),
    updateBoardTeam: vi.fn(),
    removeBoardTeam: vi.fn(),
  },
}))

describe('teamStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initializes with default empty state', () => {
    const store = useTeamStore()
    expect(store.teams).toEqual([])
    expect(store.currentTeam).toBeNull()
    expect(store.currentTeamMembers).toEqual([])
    expect(store.boardTeams).toEqual([])
  })

  it('fetches teams for a workspace', async () => {
    const mockTeams = [
      { id: 1, name: 'Frontend', members_count: 3 },
      { id: 2, name: 'Backend', members_count: 4 },
    ]
    teamService.getTeams.mockResolvedValueOnce({ data: mockTeams })

    const store = useTeamStore()
    const result = await store.fetchTeams(10)

    expect(result.length).toBe(2)
    expect(store.teams[0].name).toBe('Frontend')
    expect(teamService.getTeams).toHaveBeenCalledWith(10)
  })

  it('creates team and prepends to list', async () => {
    const newTeam = { id: 3, name: 'DevOps', members_count: 1 }
    teamService.createTeam.mockResolvedValueOnce({ data: newTeam })

    const store = useTeamStore()
    const result = await store.createTeam(10, { name: 'DevOps' })

    expect(result.name).toBe('DevOps')
    expect(store.teams[0].id).toBe(3)
  })

  it('updates team details in the list', async () => {
    const updated = { id: 1, name: 'Frontend Guild', members_count: 5 }
    teamService.updateTeam.mockResolvedValueOnce({ data: updated })

    const store = useTeamStore()
    store.teams = [{ id: 1, name: 'Frontend', members_count: 3 }]

    await store.updateTeam(1, { name: 'Frontend Guild' })

    expect(store.teams[0].name).toBe('Frontend Guild')
  })

  it('deletes team from the list', async () => {
    teamService.deleteTeam.mockResolvedValueOnce({ message: 'Deleted' })

    const store = useTeamStore()
    store.teams = [
      { id: 1, name: 'Design' },
      { id: 2, name: 'QA' },
    ]

    await store.deleteTeam(1)

    expect(store.teams.length).toBe(1)
    expect(store.teams[0].id).toBe(2)
  })

  it('manages board team permissions', async () => {
    const assigned = { id: 5, name: 'Core Team', permission: 'editor' }
    teamService.assignBoardTeam.mockResolvedValueOnce({ data: assigned })

    const store = useTeamStore()
    await store.assignBoardTeam(100, 5, 'editor')

    expect(store.boardTeams.length).toBe(1)
    expect(store.boardTeams[0].permission).toBe('editor')

    const updated = { id: 5, name: 'Core Team', permission: 'admin' }
    teamService.updateBoardTeam.mockResolvedValueOnce({ data: updated })

    await store.updateBoardTeamPermission(100, 5, 'admin')
    expect(store.boardTeams[0].permission).toBe('admin')

    teamService.removeBoardTeam.mockResolvedValueOnce({ message: 'Removed' })
    await store.removeBoardTeam(100, 5)
    expect(store.boardTeams.length).toBe(0)
  })
})
