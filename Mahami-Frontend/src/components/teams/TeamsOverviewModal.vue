<script setup>
import { ref, onMounted } from 'vue'
import { useTeamStore } from '@/stores/teamStore'
import { useWorkspaceStore } from '@/stores/workspaceStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import LoadingIndicator from '@/components/common/LoadingIndicator.vue'
import TeamModal from './TeamModal.vue'
import TeamMembersModal from './TeamMembersModal.vue'

const props = defineProps({
  workspace: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close'])
const { t } = useI18n()
const toast = useToast()
const teamStore = useTeamStore()
const workspaceStore = useWorkspaceStore()

const showTeamModal = ref(false)
const selectedTeam = ref(null)
const isSubmitting = ref(false)

const showMembersModal = ref(false)
const teamForMembers = ref(null)

const showDeleteConfirm = ref(false)
const teamToDelete = ref(null)
const isDeleting = ref(false)

onMounted(async () => {
  try {
    await teamStore.fetchTeams(props.workspace.id)
  } catch (err) {
    toast.error(err.response?.data?.message || t('networkError'))
  }
})

function openCreateTeam() {
  selectedTeam.value = null
  showTeamModal.value = true
}

function openEditTeam(team) {
  selectedTeam.value = team
  showTeamModal.value = true
}

function openManageMembers(team) {
  teamForMembers.value = team
  showMembersModal.value = true
}

function promptDeleteTeam(team) {
  teamToDelete.value = team
  showDeleteConfirm.value = true
}

async function handleSaveTeam(data) {
  isSubmitting.value = true
  try {
    if (selectedTeam.value) {
      await teamStore.updateTeam(selectedTeam.value.id, data)
      toast.success(t('savedSuccessfully'))
    } else {
      await teamStore.createTeam(props.workspace.id, data)
      toast.success(t('createdSuccessfully'))
    }
    showTeamModal.value = false
    selectedTeam.value = null
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isSubmitting.value = false
  }
}

async function handleDeleteTeam() {
  if (!teamToDelete.value) return
  isDeleting.value = true
  try {
    await teamStore.deleteTeam(teamToDelete.value.id)
    toast.success(t('deletedSuccessfully'))
    showDeleteConfirm.value = false
    teamToDelete.value = null
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <AppModal
    :title="t('manageTeams') + ' — ' + workspace.name"
    size="xl"
    @close="emit('close')"
  >
    <div class="space-y-6">
      <!-- ── Header Action Bar ──────────────────────────────────────── -->
      <div class="flex items-center justify-between gap-3">
        <div>
          <h4 class="text-sm font-bold text-slate-900 dark:text-white">
            {{ t('teams') }} ({{ teamStore.teams.length }})
          </h4>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ t('noTeamsSubtitle') }}
          </p>
        </div>

        <AppButton
          v-if="workspaceStore.canManageMembers"
          variant="primary"
          size="sm"
          @click="openCreateTeam"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12 5v14M5 12h14" />
          </svg>
          {{ t('createTeam') }}
        </AppButton>
      </div>

      <!-- ── Teams Grid / List ──────────────────────────────────────── -->
      <div v-if="teamStore.isLoading" class="py-12 flex justify-center">
        <LoadingIndicator />
      </div>

      <div
        v-else-if="teamStore.teams.length === 0"
        class="text-center py-12 p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/80 space-y-3"
      >
        <div class="w-12 h-12 rounded-2xl bg-slate-200/70 dark:bg-slate-700 text-slate-400 flex items-center justify-center mx-auto">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </div>
        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">
          {{ t('noTeams') }}
        </h4>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          {{ t('noTeamsSubtitle') }}
        </p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div
          v-for="team in teamStore.teams"
          :key="team.id"
          class="p-4 rounded-2xl border border-slate-200/90 dark:border-slate-700/90 bg-white dark:bg-slate-800/60 shadow-2xs hover:shadow-sm transition-all space-y-3"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-purple-500 via-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0 shadow-2xs">
                {{ team.name?.charAt(0) || 'T' }}
              </div>
              <div class="min-w-0">
                <h5 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                  {{ team.name }}
                </h5>
                <span
                  v-if="team.current_user_role"
                  class="inline-block text-[10px] font-semibold px-1.5 py-0.2 rounded bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300"
                >
                  {{ team.current_user_role === 'lead' ? t('teamLead') : t('teamMember') }}
                </span>
              </div>
            </div>

            <!-- Team Actions -->
            <div class="flex items-center gap-1 shrink-0">
              <button
                type="button"
                class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                :title="t('editTeam')"
                @click="openEditTeam(team)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9" />
                  <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                </svg>
              </button>

              <button
                v-if="workspaceStore.canManageMembers"
                type="button"
                class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors cursor-pointer"
                :title="t('deleteTeam')"
                @click="promptDeleteTeam(team)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </div>
          </div>

          <p v-if="team.description" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
            {{ team.description }}
          </p>

          <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs">
            <span class="text-slate-500 dark:text-slate-400 font-medium">
              {{ team.members_count || 0 }} {{ t('members') }} • {{ team.boards_count || 0 }} {{ t('boards') }}
            </span>

            <button
              type="button"
              class="font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
              @click="openManageMembers(team)"
            >
              {{ t('manageTeams') }} &rarr;
            </button>
          </div>
        </div>
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
        <AppButton variant="secondary" @click="emit('close')">
          {{ t('close') }}
        </AppButton>
      </div>
    </div>

    <!-- Modals -->
    <TeamModal
      v-if="showTeamModal"
      :team="selectedTeam"
      :loading="isSubmitting"
      @save="handleSaveTeam"
      @close="showTeamModal = false; selectedTeam = null"
    />

    <TeamMembersModal
      v-if="showMembersModal && teamForMembers"
      :team="teamForMembers"
      @close="showMembersModal = false; teamForMembers = null"
    />

    <ConfirmDialog
      v-if="showDeleteConfirm"
      :title="t('deleteTeam')"
      :message="t('deleteTeamConfirm')"
      :confirm-label="t('delete')"
      :cancel-label="t('cancel')"
      :loading="isDeleting"
      variant="danger"
      @confirm="handleDeleteTeam"
      @cancel="showDeleteConfirm = false; teamToDelete = null"
    />
  </AppModal>
</template>
