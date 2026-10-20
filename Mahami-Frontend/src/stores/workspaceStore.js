import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import workspaceService from '@/services/workspaceService'

export const useWorkspaceStore = defineStore('workspace', () => {
  const workspaces = ref([])
  const currentWorkspaceId = ref(
    localStorage.getItem('mahami_active_workspace')
      ? Number(localStorage.getItem('mahami_active_workspace')) || localStorage.getItem('mahami_active_workspace')
      : null
  )
  const members = ref([])
  const isLoading = ref(false)
  const isMembersLoading = ref(false)
  const error = ref(null)

  const currentWorkspace = computed(() => {
    if (!currentWorkspaceId.value) return null
    return workspaces.value.find((w) => String(w.id) === String(currentWorkspaceId.value)) || null
  })

  const currentUserRole = computed(() => {
    return currentWorkspace.value?.current_user_role || null
  })

  const isOwner = computed(() => currentUserRole.value === 'owner')
  const isAdmin = computed(() => currentUserRole.value === 'admin')
  const canManageMembers = computed(() => isOwner.value || isAdmin.value)
  const canEditWorkspace = computed(() => isOwner.value || isAdmin.value)
  const canDeleteWorkspace = computed(() => isOwner.value)
  const canCreateBoard = computed(() => currentUserRole.value !== 'guest')

  async function fetchWorkspaces() {
    isLoading.value = true
    error.value = null
    try {
      const response = await workspaceService.getWorkspaces()
      workspaces.value = response.data || []

      // If active workspace is null or invalid, pick the first one available
      if (workspaces.value.length > 0) {
        const exists = workspaces.value.some((w) => String(w.id) === String(currentWorkspaceId.value))
        if (!exists) {
          setActiveWorkspace(workspaces.value[0].id)
        }
      } else {
        currentWorkspaceId.value = null
        localStorage.removeItem('mahami_active_workspace')
      }

      return workspaces.value
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function setActiveWorkspace(id) {
    if (id === null || id === undefined || id === '') {
      currentWorkspaceId.value = null
      localStorage.removeItem('mahami_active_workspace')
      members.value = []
      return
    }

    currentWorkspaceId.value = Number(id) || id
    localStorage.setItem('mahami_active_workspace', String(currentWorkspaceId.value))
  }

  async function createWorkspace(data) {
    const response = await workspaceService.createWorkspace(data)
    const newWs = response.data
    workspaces.value.unshift(newWs)
    setActiveWorkspace(newWs.id)
    return newWs
  }

  async function updateWorkspace(id, data) {
    const response = await workspaceService.updateWorkspace(id, data)
    const updated = response.data
    const index = workspaces.value.findIndex((w) => String(w.id) === String(id))
    if (index !== -1) {
      workspaces.value[index] = { ...workspaces.value[index], ...updated }
    }
    return updated
  }

  async function deleteWorkspace(id) {
    await workspaceService.deleteWorkspace(id)
    workspaces.value = workspaces.value.filter((w) => String(w.id) !== String(id))

    if (String(currentWorkspaceId.value) === String(id)) {
      if (workspaces.value.length > 0) {
        setActiveWorkspace(workspaces.value[0].id)
      } else {
        setActiveWorkspace(null)
      }
    }
  }

  async function fetchMembers(workspaceId = null) {
    const targetId = workspaceId || currentWorkspaceId.value
    if (!targetId) {
      members.value = []
      return []
    }

    isMembersLoading.value = true
    try {
      const response = await workspaceService.getMembers(targetId)
      members.value = response.data || []
      return members.value
    } finally {
      isMembersLoading.value = false
    }
  }

  async function inviteMember(workspaceId, data) {
    const targetId = workspaceId || currentWorkspaceId.value
    const response = await workspaceService.inviteMember(targetId, data)
    return response
  }

  async function updateMemberRole(workspaceId, userId, role) {
    const targetId = workspaceId || currentWorkspaceId.value
    const response = await workspaceService.updateMemberRole(targetId, userId, { role })
    const updated = response.data
    const idx = members.value.findIndex((m) => String(m.id) === String(userId))
    if (idx !== -1) {
      members.value[idx] = { ...members.value[idx], ...updated }
    }
    return updated
  }

  async function removeMember(workspaceId, userId) {
    const targetId = workspaceId || currentWorkspaceId.value
    await workspaceService.removeMember(targetId, userId)
    members.value = members.value.filter((m) => String(m.id) !== String(userId))
  }

  async function transferOwnership(workspaceId, newOwnerId) {
    const targetId = workspaceId || currentWorkspaceId.value
    const response = await workspaceService.transferOwnership(targetId, { new_owner_id: newOwnerId })
    await fetchWorkspaces()
    return response.data
  }

  function reset() {
    workspaces.value = []
    currentWorkspaceId.value = null
    members.value = []
    error.value = null
    localStorage.removeItem('mahami_active_workspace')
  }

  return {
    workspaces,
    currentWorkspaceId,
    currentWorkspace,
    members,
    isLoading,
    isMembersLoading,
    error,
    currentUserRole,
    isOwner,
    isAdmin,
    canManageMembers,
    canEditWorkspace,
    canDeleteWorkspace,
    canCreateBoard,
    fetchWorkspaces,
    setActiveWorkspace,
    createWorkspace,
    updateWorkspace,
    deleteWorkspace,
    fetchMembers,
    inviteMember,
    updateMemberRole,
    removeMember,
    transferOwnership,
    reset,
  }
})

