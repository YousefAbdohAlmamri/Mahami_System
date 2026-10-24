<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useTeamStore } from "@/stores/teamStore";
import { useWorkspaceStore } from "@/stores/workspaceStore";
import { useI18n } from "@/composables/useI18n";
import { useToast } from "@/composables/useToast";
import AppModal from "@/components/common/AppModal.vue";
import AppButton from "@/components/common/AppButton.vue";
import LoadingIndicator from "@/components/common/LoadingIndicator.vue";

const props = defineProps({
  team: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["close"]);
const { t } = useI18n();
const toast = useToast();
const teamStore = useTeamStore();
const workspaceStore = useWorkspaceStore();

const addForm = reactive({
  userId: "",
  role: "member",
});
const isAdding = ref(false);

onMounted(async () => {
  try {
    await teamStore.fetchTeamMembers(props.team.id);
    if (
      workspaceStore.members.length === 0 &&
      workspaceStore.currentWorkspaceId
    ) {
      await workspaceStore.fetchMembers(workspaceStore.currentWorkspaceId);
    }
  } catch (err) {
    toast.error(err.response?.data?.message || t("networkError"));
  }
});

// Filter out workspace members already in this team
const availableWorkspaceMembers = computed(() => {
  const currentMemberIds = teamStore.currentTeamMembers.map((m) =>
    String(m.id),
  );
  return workspaceStore.members.filter(
    (wm) => !currentMemberIds.includes(String(wm.id)),
  );
});

async function handleAddMember() {
  if (!addForm.userId) return;
  isAdding.value = true;
  try {
    await teamStore.addMember(props.team.id, {
      user_id: addForm.userId,
      role: addForm.role,
    });
    toast.success(t("createdSuccessfully"));
    addForm.userId = "";
    addForm.role = "member";
  } catch (err) {
    toast.error(err.response?.data?.message || t("genericError"));
  } finally {
    isAdding.value = false;
  }
}

async function handleRoleChange(member, newRole) {
  try {
    await teamStore.updateMemberRole(props.team.id, member.id, newRole);
    toast.success(t("roleUpdated"));
  } catch (err) {
    toast.error(err.response?.data?.message || t("genericError"));
  }
}

async function handleRemoveMember(member) {
  try {
    await teamStore.removeMember(props.team.id, member.id);
    toast.success(t("memberRemoved"));
  } catch (err) {
    toast.error(err.response?.data?.message || t("genericError"));
  }
}
</script>

<template>
  <AppModal
    :title="t('manageTeams') + ' — ' + team.name"
    size="lg"
    @close="emit('close')"
  >
    <div class="space-y-6">
      <!-- ── Add Member Form ────────────────────────────────────────── -->
      <div
        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/80 space-y-3"
      >
        <h4
          class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300"
        >
          {{ t("addTeamMember") }}
        </h4>

        <form
          @submit.prevent="handleAddMember"
          class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end"
        >
          <div class="sm:col-span-7">
            <label
              class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
            >
              {{ t("selectMember") }}
            </label>
            <select
              v-model="addForm.userId"
              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              required
            >
              <option value="" disabled>{{ t("selectMember") }}</option>
              <option
                v-for="wm in availableWorkspaceMembers"
                :key="wm.id"
                :value="wm.id"
              >
                {{ wm.name }} ({{ wm.email }})
              </option>
            </select>
          </div>

          <div class="sm:col-span-3">
            <label
              class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
            >
              {{ t("role") }}
            </label>
            <select
              v-model="addForm.role"
              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            >
              <option value="member">{{ t("teamMember") }}</option>
              <option value="lead">{{ t("teamLead") }}</option>
            </select>
          </div>

          <div class="sm:col-span-2">
            <AppButton
              type="submit"
              variant="primary"
              :loading="isAdding"
              :disabled="!addForm.userId"
              class="w-full justify-center"
            >
              {{ t("save") }}
            </AppButton>
          </div>
        </form>
      </div>

      <!-- ── Current Team Members List ──────────────────────────────── -->
      <div class="space-y-3">
        <h4
          class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300"
        >
          {{ t("members") }} ({{ teamStore.currentTeamMembers.length }})
        </h4>

        <div v-if="teamStore.isLoading" class="py-8 flex justify-center">
          <LoadingIndicator />
        </div>

        <div
          v-else-if="teamStore.currentTeamMembers.length === 0"
          class="text-center py-6 text-xs text-slate-500"
        >
          {{ t("noTasks") }}
        </div>

        <div
          v-else
          class="divide-y divide-slate-100 dark:divide-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden bg-white dark:bg-slate-800/30"
        >
          <div
            v-for="member in teamStore.currentTeamMembers"
            :key="member.id"
            class="p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 sm:gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/50"
          >
            <div class="flex items-center gap-3 min-w-0 sm:flex-1">
              <div
                class="w-8 h-8 rounded-lg bg-gradient-to-tr from-purple-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0"
              >
                {{ member.name?.charAt(0) || "U" }}
              </div>
              <div class="min-w-0">
                <div
                  class="text-sm font-semibold text-slate-900 dark:text-white truncate"
                >
                  {{ member.name }}
                </div>
                <div
                  class="text-xs text-slate-500 dark:text-slate-400 truncate"
                >
                  {{ member.email }}
                </div>
              </div>
            </div>

            <div
              class="flex flex-wrap items-center gap-2 sm:shrink-0 sm:justify-end"
            >
              <select
                :value="member.role"
                class="px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                @change="handleRoleChange(member, $event.target.value)"
              >
                <option value="member">{{ t("teamMember") }}</option>
                <option value="lead">{{ t("teamLead") }}</option>
              </select>

              <button
                type="button"
                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors cursor-pointer"
                :title="t('removeMember')"
                @click="handleRemoveMember(member)"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-4 h-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                  />
                  <line x1="10" y1="11" x2="10" y2="17" />
                  <line x1="14" y1="11" x2="14" y2="17" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        class="flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800"
      >
        <AppButton variant="secondary" @click="emit('close')">
          {{ t("close") }}
        </AppButton>
      </div>
    </div>
  </AppModal>
</template>
