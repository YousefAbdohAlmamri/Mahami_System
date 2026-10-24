<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { useWorkspaceStore } from "@/stores/workspaceStore";
import { useI18n } from "@/composables/useI18n";
import { useToast } from "@/composables/useToast";
import workspaceService from "@/services/workspaceService";
import AppButton from "@/components/common/AppButton.vue";
import LoadingIndicator from "@/components/common/LoadingIndicator.vue";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const workspaceStore = useWorkspaceStore();
const { t } = useI18n();
const toast = useToast();

const token = route.params.token;
const isLoading = ref(true);
const invitation = ref(null);
const error = ref(null);
const isProcessing = ref(false);

onMounted(async () => {
  if (!token) {
    error.value = t("invalidInvitation");
    isLoading.value = false;
    return;
  }

  try {
    const res = await workspaceService.getInvitation(token);
    const data = res.data || res.invitation || res;
    if (data && data.is_valid !== false) {
      invitation.value = {
        ...data,
        inviter_name: data.inviter?.name || data.inviter_name,
      };
    } else {
      error.value = res.message || t("invalidInvitation");
    }
  } catch (err) {
    error.value = err.response?.data?.message || t("invalidInvitation");
  } finally {
    isLoading.value = false;
  }
});

async function handleAccept() {
  isProcessing.value = true;
  try {
    const res = await workspaceService.acceptInvitation(token);
    toast.success(t("invitationAccepted"));
    await workspaceStore.fetchWorkspaces();
    const targetWsId =
      res.workspace_id ||
      res.invitation?.workspace_id ||
      invitation.value?.workspace_id;
    if (targetWsId) {
      workspaceStore.setActiveWorkspace(targetWsId);
    }
    router.push({ name: "boards" });
  } catch (err) {
    toast.error(err.response?.data?.message || t("genericError"));
  } finally {
    isProcessing.value = false;
  }
}

async function handleDecline() {
  isProcessing.value = true;
  try {
    await workspaceService.declineInvitation(token);
    toast.info(t("invitationDeclined"));
    router.push({ name: "boards" });
  } catch (err) {
    toast.error(err.response?.data?.message || t("genericError"));
  } finally {
    isProcessing.value = false;
  }
}

function getRoleLabel(role) {
  if (!role) return "";
  return t("role" + role.charAt(0).toUpperCase() + role.slice(1));
}
</script>

<template>
  <div class="min-h-full flex items-center justify-center p-3 sm:p-4">
    <div
      class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 p-5 sm:p-8 shadow-xl text-center space-y-6 min-w-0 break-words"
    >
      <!-- Loading -->
      <div v-if="isLoading" class="py-12 flex flex-col items-center gap-3">
        <LoadingIndicator />
        <span class="text-sm text-slate-500">{{ t("loading") }}</span>
      </div>

      <!-- Error / Invalid -->
      <div v-else-if="error" class="space-y-4">
        <div
          class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 dark:bg-rose-900/30 text-rose-500 flex items-center justify-center"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-7 h-7"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
          {{ error }}
        </h3>
        <AppButton variant="primary" @click="router.push({ name: 'boards' })">
          {{ t("appName") }}
        </AppButton>
      </div>

      <!-- Valid invitation -->
      <div v-else class="space-y-6">
        <!-- Icon badge -->
        <div
          class="w-16 h-16 mx-auto rounded-3xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/25"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-8 h-8"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <line x1="19" y1="8" x2="19" y2="14" />
            <line x1="22" y1="11" x2="16" y2="11" />
          </svg>
        </div>

        <div>
          <span
            class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400"
          >
            {{ t("workspaceInvitedYou") }}
          </span>
          <h2
            class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1"
          >
            {{ invitation.workspace_name }}
          </h2>
          <p
            v-if="invitation.inviter_name"
            class="text-xs text-slate-500 dark:text-slate-400 mt-1"
          >
            {{ t("invitedBy") }}:
            <span class="font-medium text-slate-700 dark:text-slate-300">{{
              invitation.inviter_name
            }}</span>
          </p>
        </div>

        <div
          class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between text-xs"
        >
          <span class="text-slate-600 dark:text-slate-400 font-medium">{{
            t("role")
          }}</span>
          <span
            class="font-bold px-2 py-0.5 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300"
          >
            {{ getRoleLabel(invitation.role) }}
          </span>
        </div>

        <!-- Authenticated actions -->
        <div v-if="authStore.isAuthenticated" class="flex flex-col gap-2.5">
          <AppButton
            variant="primary"
            size="lg"
            :loading="isProcessing"
            class="w-full justify-center"
            @click="handleAccept"
          >
            {{ t("acceptInvitation") }}
          </AppButton>
          <AppButton
            variant="ghost"
            :disabled="isProcessing"
            class="w-full justify-center"
            @click="handleDecline"
          >
            {{ t("declineInvitation") }}
          </AppButton>
        </div>

        <!-- Unauthenticated prompt -->
        <div v-else class="space-y-3">
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ t("sessionExpired") }}
          </p>
          <div class="flex flex-col gap-2">
            <AppButton
              variant="primary"
              class="w-full justify-center"
              @click="
                router.push({
                  name: 'login',
                  query: { redirect: route.fullPath },
                })
              "
            >
              {{ t("login") }}
            </AppButton>
            <AppButton
              variant="secondary"
              class="w-full justify-center"
              @click="
                router.push({
                  name: 'register',
                  query: { redirect: route.fullPath },
                })
              "
            >
              {{ t("register") }}
            </AppButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
