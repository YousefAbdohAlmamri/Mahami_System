<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useTeamStore } from '@/stores/teamStore'
import { useWorkspaceStore } from '@/stores/workspaceStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'
import LoadingIndicator from '@/components/common/LoadingIndicator.vue'

const props = defineProps({
  board: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close'])
const { t } = useI18n()
const toast = useToast()
const teamStore = useTeamStore()
const workspaceStore = useWorkspaceStore()

const assignForm = reactive({
  teamId: '',
  permission: 'editor',
})
const isAssigning = ref(false)

const permissionOptions = [
  { value: 'viewer', labelKey: 'permViewer', descKey: 'permViewerDesc' },
  { value: 'editor', labelKey: 'permEditor', descKey: 'permEditorDesc' },
  { value: 'admin', labelKey: 'permAdmin', descKey: 'permAdminDesc' },
]

onMounted(async () => {
  try {
    await teamStore.fetchBoardTeams(props.board.id)
    if (workspaceStore.currentWorkspaceId) {
      await teamStore.fetchTeams(workspaceStore.currentWorkspaceId)
    }
  } catch (err) {
    toast.error(err.response?.data?.message || t('networkError'))
  }
})

// Filter out teams already assigned to this board
const availableTeams = computed(() => {
  const assignedIds = teamStore.boardTeams.map((bt) => String(bt.id))
  return teamStore.teams.filter((t) => !assignedIds.includes(String(t.id)))
})

async function handleAssignTeam() {
  if (!assignForm.teamId) return
  isAssigning.value = true
  try {
    await teamStore.assignBoardTeam(props.board.id, assignForm.teamId, assignForm.permission)
    toast.success(t('teamAssigned'))
    assignForm.teamId = ''
    assignForm.permission = 'editor'
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    isAssigning.value = false
  }
}

async function handlePermissionChange(team, newPerm) {
  try {
    await teamStore.updateBoardTeamPermission(props.board.id, team.id, newPerm)
    toast.success(t('savedSuccessfully'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}

async function handleRemoveTeam(team) {
  try {
    await teamStore.removeBoardTeam(props.board.id, team.id)
    toast.success(t('teamRemovedFromBoard'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  }
}
</script>

<template>
  <AppModal
    :title="t('boardTeams') + ' — ' + board.title"
    size="lg"
    @close="emit('close')"
  >
    <div class="space-y-6">
      <!-- ── Assign Form ────────────────────────────────────────────── -->
      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/80 space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
          {{ t('assignTeamToBoard') }}
        </h4>

        <form @submit.prevent="handleAssignTeam" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
          <div class="sm:col-span-6">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ t('team') }}
            </label>
            <select
              v-model="assignForm.teamId"
              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              required
            >
              <option value="" disabled>{{ t('selectMember') }}</option>
              <option
                v-for="team in availableTeams"
                :key="team.id"
                :value="team.id"
              >
                {{ team.name }} ({{ team.members_count || 0 }} {{ t('members') }})
              </option>
            </select>
          </div>

          <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ t('permissionLevel') }}
            </label>
            <select
              v-model="assignForm.permission"
              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            >
              <option v-for="opt in permissionOptions" :key="opt.value" :value="opt.value">
                {{ t(opt.labelKey) }}
              </option>
            </select>
          </div>

          <div class="sm:col-span-2">
            <AppButton
              type="submit"
              variant="primary"
              :loading="isAssigning"
              :disabled="!assignForm.teamId"
              class="w-full justify-center"
            >
              {{ t('save') }}
            </AppButton>
          </div>
        </form>
      </div>

      <!-- ── Assigned Teams List ────────────────────────────────────── -->
      <div class="space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
          {{ t('teams') }} ({{ teamStore.boardTeams.length }})
        </h4>

        <div v-if="teamStore.isLoading" class="py-8 flex justify-center">
          <LoadingIndicator />
        </div>

        <div v-else-if="teamStore.boardTeams.length === 0" class="text-center py-6 text-xs text-slate-500">
          {{ t('noTeamsSubtitle') }}
        </div>

        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden bg-white dark:bg-slate-800/30">
          <div
            v-for="team in teamStore.boardTeams"
            :key="team.id"
            class="p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/50"
          >
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
                {{ team.name?.charAt(0) || 'T' }}
              </div>
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                  {{ team.name }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ team.members_count || 0 }} {{ t('members') }}
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <select
                :value="team.permission"
                class="px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                @change="handlePermissionChange(team, $event.target.value)"
              >
                <option v-for="opt in permissionOptions" :key="opt.value" :value="opt.value">
                  {{ t(opt.labelKey) }}
                </option>
              </select>

              <button
                type="button"
                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors cursor-pointer"
                :title="t('delete')"
                @click="handleRemoveTeam(team)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
        <AppButton variant="secondary" @click="emit('close')">
          {{ t('close') }}
        </AppButton>
      </div>
    </div>
  </AppModal>
</template>
