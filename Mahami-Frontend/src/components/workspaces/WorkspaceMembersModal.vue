<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useWorkspaceStore } from '@/stores/workspaceStore'
import { useAuthStore } from '@/stores/authStore'
import { useI18n } from '@/composables/useI18n'
import { useToast } from '@/composables/useToast'
import AppModal from '@/components/common/AppModal.vue'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import LoadingIndicator from '@/components/common/LoadingIndicator.vue'

const props = defineProps({
  workspace: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close'])
const { t } = useI18n()
const toast = useToast()
const workspaceStore = useWorkspaceStore()
const authStore = useAuthStore()

const inviteForm = reactive({
  email: '',
  role: 'member',
})
const inviteLoading = ref(false)
const lastInviteLink = ref('')
const memberToRemove = ref(null)
const showRemoveConfirm = ref(false)
const removeLoading = ref(false)

const roleOptions = [
  { value: 'admin', labelKey: 'roleAdmin', descKey: 'roleAdminDesc' },
  { value: 'member', labelKey: 'roleMember', descKey: 'roleMemberDesc' },
  { value: 'guest', labelKey: 'roleGuest', descKey: 'roleGuestDesc' },
]

const canManage = computed(() => {
  return workspaceStore.canManageMembers
})

const isOwner = computed(() => {
  return workspaceStore.isOwner
})

onMounted(async () => {
  try {
    await workspaceStore.fetchMembers(props.workspace.id)
  } catch (err) {
    toast.error(err.response?.data?.message || t('networkError'))
  }
})

async function handleInvite() {
  if (!inviteForm.email.trim()) return
  inviteLoading.value = true
  lastInviteLink.value = ''
  try {
    const res = await workspaceStore.inviteMember(props.workspace.id, {
      email: inviteForm.email.trim(),
      role: inviteForm.role,
    })
    toast.success(t('invitationSent'))
    const token = res?.data?.token || res?.token || res?.invitation?.token
    if (token) {
      lastInviteLink.value = `${window.location.origin}/invitations/${token}`
    }
    inviteForm.email = ''
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    inviteLoading.value = false
  }
}

async function copyInviteLink() {
  if (!lastInviteLink.value) return
  try {
    await navigator.clipboard.writeText(lastInviteLink.value)
    toast.success(t('linkCopied'))
  } catch {
    toast.error(t('genericError'))
  }
}

async function handleRoleChange(member, newRole) {
  try {
    await workspaceStore.updateMemberRole(props.workspace.id, member.id, newRole)
    toast.success(t('roleUpdated'))
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
    await workspaceStore.fetchMembers(props.workspace.id)
  }
}

function promptRemoveMember(member) {
  memberToRemove.value = member
  showRemoveConfirm.value = true
}

async function handleConfirmRemove() {
  if (!memberToRemove.value) return
  removeLoading.value = true
  try {
    await workspaceStore.removeMember(props.workspace.id, memberToRemove.value.id)
    toast.success(t('memberRemoved'))
    showRemoveConfirm.value = false
    memberToRemove.value = null
  } catch (err) {
    toast.error(err.response?.data?.message || t('genericError'))
  } finally {
    removeLoading.value = false
  }
}

function getRoleBadgeClass(role) {
  switch (role) {
    case 'owner':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800/40'
    case 'admin':
      return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 border-purple-200 dark:border-purple-800/40'
    case 'member':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800/40'
    default:
      return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700'
  }
}
</script>

<template>
  <AppModal
    :title="t('manageMembers') + ' — ' + workspace.name"
    size="xl"
    @close="emit('close')"
  >
    <div class="space-y-6">
      <!-- ── Invite Section (Only for Owner / Admin) ─────────────────── -->
      <div v-if="canManage" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/80 space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
          {{ t('inviteMember') }}
        </h4>

        <form @submit.prevent="handleInvite" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
          <div class="sm:col-span-7">
            <AppInput
              v-model="inviteForm.email"
              type="email"
              :label="t('inviteByEmail')"
              :placeholder="t('inviteEmailPlaceholder')"
              required
            />
          </div>

          <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ t('role') }}
            </label>
            <select
              v-model="inviteForm.role"
              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            >
              <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
                {{ t(opt.labelKey) }}
              </option>
            </select>
          </div>

          <div class="sm:col-span-2">
            <AppButton
              type="submit"
              variant="primary"
              :loading="inviteLoading"
              class="w-full justify-center"
            >
              {{ t('sendInvitation') }}
            </AppButton>
          </div>
        </form>

        <!-- Generated invite link -->
        <div v-if="lastInviteLink" class="mt-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40 flex items-center justify-between gap-3 text-xs">
          <div class="flex-1 truncate font-mono text-blue-700 dark:text-blue-300">
            {{ lastInviteLink }}
          </div>
          <button
            type="button"
            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white cursor-pointer transition-colors"
            @click="copyInviteLink"
          >
            {{ t('copyLink') }}
          </button>
        </div>
      </div>

      <!-- ── Members List ────────────────────────────────────────────── -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
            {{ t('members') }} ({{ workspaceStore.members.length }})
          </h4>
        </div>

        <div v-if="workspaceStore.isMembersLoading" class="py-8 flex justify-center">
          <LoadingIndicator />
        </div>

        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden bg-white dark:bg-slate-800/30">
          <div
            v-for="member in workspaceStore.members"
            :key="member.id"
            class="p-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors"
          >
            <!-- User info -->
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0 shadow-2xs">
                {{ member.name?.charAt(0) || 'U' }}
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                    {{ member.name }}
                  </span>
                  <span
                    v-if="member.id === authStore.user?.id"
                    class="text-[10px] px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium"
                  >
                    (You)
                  </span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                  {{ member.email }}
                </div>
              </div>
            </div>

            <!-- Role & Actions -->
            <div class="flex items-center gap-2 shrink-0">
              <!-- If owner -->
              <span
                v-if="member.role === 'owner'"
                class="px-2.5 py-1 text-xs font-semibold rounded-lg border"
                :class="getRoleBadgeClass(member.role)"
              >
                {{ t('roleOwner') }}
              </span>

              <!-- If non-owner and user can manage -->
              <template v-else-if="canManage">
                <select
                  :value="member.role"
                  class="px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  @change="handleRoleChange(member, $event.target.value)"
                >
                  <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
                    {{ t(opt.labelKey) }}
                  </option>
                </select>

                <button
                  v-if="member.id !== authStore.user?.id"
                  type="button"
                  class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors cursor-pointer"
                  :title="t('removeMember')"
                  @click="promptRemoveMember(member)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    <line x1="10" y1="11" x2="10" y2="17" />
                    <line x1="14" y1="11" x2="14" y2="17" />
                  </svg>
                </button>
              </template>

              <!-- If view-only for this member -->
              <span
                v-else
                class="px-2.5 py-1 text-xs font-semibold rounded-lg border"
                :class="getRoleBadgeClass(member.role)"
              >
                {{ t('role' + member.role.charAt(0).toUpperCase() + member.role.slice(1)) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Close footer -->
      <div class="flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
        <AppButton variant="secondary" @click="emit('close')">
          {{ t('close') }}
        </AppButton>
      </div>
    </div>

    <!-- Confirm Remove Dialog -->
    <ConfirmDialog
      v-if="showRemoveConfirm"
      :title="t('removeMember')"
      :message="t('removeMemberConfirm')"
      :confirm-label="t('delete')"
      :cancel-label="t('cancel')"
      :loading="removeLoading"
      variant="danger"
      @confirm="handleConfirmRemove"
      @cancel="showRemoveConfirm = false; memberToRemove = null"
    />
  </AppModal>
</template>

