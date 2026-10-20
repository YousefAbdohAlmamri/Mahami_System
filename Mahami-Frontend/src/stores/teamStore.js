import { defineStore } from 'pinia'
import { ref } from 'vue'
import teamService from '@/services/teamService'

export const useTeamStore = defineStore('team', () => {
  const teams = ref([])
  const currentTeam = ref(null)
  const currentTeamMembers = ref([])
  const boardTeams = ref([])
  const isLoading = ref(false)
  const error = ref(null)

  async function fetchTeams(workspaceId) {
    if (!workspaceId) {
      teams.value = []
      return []
    }
    isLoading.value = true
    error.value = null
    try {
      const response = await teamService.getTeams(workspaceId)
      teams.value = response.data || []
      return teams.value
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function createTeam(workspaceId, data) {
    const response = await teamService.createTeam(workspaceId, data)
    const newTeam = response.data
    teams.value.unshift(newTeam)
    return newTeam
  }

  async function updateTeam(teamId, data) {
    const response = await teamService.updateTeam(teamId, data)
    const updated = response.data
    const index = teams.value.findIndex((t) => String(t.id) === String(teamId))
    if (index !== -1) {
      teams.value[index] = { ...teams.value[index], ...updated }
    }
    if (currentTeam.value && String(currentTeam.value.id) === String(teamId)) {
      currentTeam.value = { ...currentTeam.value, ...updated }
    }
    return updated
  }

  async function deleteTeam(teamId) {
    await teamService.deleteTeam(teamId)
    teams.value = teams.value.filter((t) => String(t.id) !== String(teamId))
    if (currentTeam.value && String(currentTeam.value.id) === String(teamId)) {
      currentTeam.value = null
      currentTeamMembers.value = []
    }
  }

  async function fetchTeamMembers(teamId) {
    isLoading.value = true
    try {
      const response = await teamService.getTeamMembers(teamId)
      currentTeamMembers.value = response.data || []
      return currentTeamMembers.value
    } finally {
      isLoading.value = false
    }
  }

  async function addMember(teamId, data) {
    const response = await teamService.addTeamMember(teamId, data)
    const newMember = response.data
    currentTeamMembers.value.push(newMember)
    // Update team member count
    const team = teams.value.find((t) => String(t.id) === String(teamId))
    if (team) team.members_count = (team.members_count || 0) + 1
    return newMember
  }

  async function updateMemberRole(teamId, userId, role) {
    const response = await teamService.updateTeamMemberRole(teamId, userId, { role })
    const updated = response.data
    const idx = currentTeamMembers.value.findIndex((m) => String(m.id) === String(userId))
    if (idx !== -1) {
      currentTeamMembers.value[idx] = { ...currentTeamMembers.value[idx], ...updated }
    }
    return updated
  }

  async function removeMember(teamId, userId) {
    await teamService.removeTeamMember(teamId, userId)
    currentTeamMembers.value = currentTeamMembers.value.filter((m) => String(m.id) !== String(userId))
    const team = teams.value.find((t) => String(t.id) === String(teamId))
    if (team && team.members_count > 0) team.members_count -= 1
  }

  async function fetchBoardTeams(boardId) {
    isLoading.value = true
    try {
      const response = await teamService.getBoardTeams(boardId)
      boardTeams.value = response.data || []
      return boardTeams.value
    } finally {
      isLoading.value = false
    }
  }

  async function assignBoardTeam(boardId, teamId, permission = 'editor') {
    const response = await teamService.assignBoardTeam(boardId, {
      team_id: teamId,
      permission,
    })
    const assigned = response.data
    boardTeams.value.push(assigned)
    return assigned
  }

  async function updateBoardTeamPermission(boardId, teamId, permission) {
    const response = await teamService.updateBoardTeam(boardId, teamId, { permission })
    const updated = response.data
    const idx = boardTeams.value.findIndex((bt) => String(bt.id) === String(teamId))
    if (idx !== -1) {
      boardTeams.value[idx] = { ...boardTeams.value[idx], ...updated }
    }
    return updated
  }

  async function removeBoardTeam(boardId, teamId) {
    await teamService.removeBoardTeam(boardId, teamId)
    boardTeams.value = boardTeams.value.filter((bt) => String(bt.id) !== String(teamId))
  }

  function reset() {
    teams.value = []
    currentTeam.value = null
    currentTeamMembers.value = []
    boardTeams.value = []
    error.value = null
  }

  return {
    teams,
    currentTeam,
    currentTeamMembers,
    boardTeams,
    isLoading,
    error,
    fetchTeams,
    createTeam,
    updateTeam,
    deleteTeam,
    fetchTeamMembers,
    addMember,
    updateMemberRole,
    removeMember,
    fetchBoardTeams,
    assignBoardTeam,
    updateBoardTeamPermission,
    removeBoardTeam,
    reset,
  }
})
