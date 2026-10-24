<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { useWorkspaceStore } from "@/stores/workspaceStore";
import { useNotificationStore } from "@/stores/notificationStore";
import { usePreferencesStore } from "@/stores/preferencesStore";
import { useI18n } from "@/composables/useI18n";
import { useToast } from "@/composables/useToast";
import WorkspaceModal from "@/components/workspaces/WorkspaceModal.vue";
import WorkspaceMembersModal from "@/components/workspaces/WorkspaceMembersModal.vue";
import TeamsOverviewModal from "@/components/teams/TeamsOverviewModal.vue";
import ConfirmDialog from "@/components/common/ConfirmDialog.vue";
import NotificationDropdown from "@/components/common/NotificationDropdown.vue";

const router = useRouter();
const authStore = useAuthStore();
const workspaceStore = useWorkspaceStore();
const notificationStore = useNotificationStore();
const prefs = usePreferencesStore();
const { t } = useI18n();
const toast = useToast();

const userMenuOpen = ref(false);
const workspaceMenuOpen = ref(false);

const showWorkspaceModal = ref(false);
const workspaceToEdit = ref(null);
const isWorkspaceSubmitting = ref(false);

const showMembersModal = ref(false);
const showTeamsModal = ref(false);
const showDeleteConfirm = ref(false);
const isWorkspaceDeleting = ref(false);

onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      await workspaceStore.fetchWorkspaces();
    } catch {
      // Ignored
    }
  }
});

function selectWorkspace(id) {
  workspaceStore.setActiveWorkspace(id);
  workspaceMenuOpen.value = false;
}

function openCreateWorkspace() {
  workspaceToEdit.value = null;
  showWorkspaceModal.value = true;
  workspaceMenuOpen.value = false;
}

function openEditWorkspace() {
  workspaceToEdit.value = workspaceStore.currentWorkspace;
  showWorkspaceModal.value = true;
  workspaceMenuOpen.value = false;
}

function openMembersModal() {
  showMembersModal.value = true;
  workspaceMenuOpen.value = false;
}

function openTeamsModal() {
  showTeamsModal.value = true;
  workspaceMenuOpen.value = false;
}

function promptDeleteWorkspace() {
  showDeleteConfirm.value = true;
  workspaceMenuOpen.value = false;
}

async function handleSaveWorkspace(data) {
  isWorkspaceSubmitting.value = true;
  try {
    if (workspaceToEdit.value) {
      await workspaceStore.updateWorkspace(workspaceToEdit.value.id, data);
      toast.success(t("savedSuccessfully"));
    } else {
      await workspaceStore.createWorkspace(data);
      toast.success(t("createdSuccessfully"));
    }
    showWorkspaceModal.value = false;
    workspaceToEdit.value = null;
  } catch (err) {
    toast.error(err.response?.data?.message || t("genericError"));
  } finally {
    isWorkspaceSubmitting.value = false;
  }
}

async function handleDeleteWorkspace() {
  if (!workspaceStore.currentWorkspaceId) return;
  isWorkspaceDeleting.value = true;
  try {
    await workspaceStore.deleteWorkspace(workspaceStore.currentWorkspaceId);
    toast.success(t("deletedSuccessfully"));
    showDeleteConfirm.value = false;
  } catch (err) {
    toast.error(err.response?.data?.message || t("genericError"));
  } finally {
    isWorkspaceDeleting.value = false;
  }
}

async function handleLogout() {
  userMenuOpen.value = false;
  workspaceStore.reset();
  notificationStore.reset();
  await authStore.logout();
  toast.success(t("loggedOut"));
  router.push({ name: "login" });
}
</script>

<template>
  <div
    class="h-dvh flex flex-col overflow-hidden transition-colors duration-200"
    :dir="prefs.dir"
  >
    <!-- ── Top Navbar ──────────────────────────────────────── -->
    <header
      class="h-14 sm:h-16 shrink-0 relative z-30 glass-nav bg-white/80 dark:bg-slate-900/80 border-b border-slate-200/80 dark:border-slate-800/80 flex items-center px-3 sm:px-6 gap-2 sm:gap-3 select-none"
    >
      <!-- Brand -->
      <router-link
        :to="{ name: 'boards' }"
        class="flex items-center gap-2.5 group transition-transform active:scale-95 shrink-0"
      >
        <div
          class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-blue-500 flex items-center justify-center text-white shadow-sm shadow-blue-500/25 group-hover:shadow-md group-hover:shadow-blue-500/35 transition-all"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.25"
          >
            <rect x="3" y="3" width="7" height="11" rx="1.5" />
            <rect x="3" y="17" width="7" height="4" rx="1" />
            <rect x="14" y="3" width="7" height="5" rx="1" />
            <rect x="14" y="11" width="7" height="10" rx="1.5" />
          </svg>
        </div>
        <span
          class="font-extrabold text-lg sm:text-xl tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 dark:from-blue-400 dark:via-indigo-300 dark:to-blue-300 bg-clip-text text-transparent hidden sm:inline"
        >
          {{ t("appName") }}
        </span>
      </router-link>

      <!-- Workspace Switcher (if authenticated) -->
      <div
        v-if="authStore.isAuthenticated"
        class="sm:relative min-w-0 ms-0 sm:ms-3"
      >
        <button
          type="button"
          class="flex items-center gap-2 px-2.5 sm:px-3 py-2 sm:py-1.5 max-w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-100 cursor-pointer shadow-2xs"
          @click="
            workspaceMenuOpen = !workspaceMenuOpen;
            userMenuOpen = false;
          "
        >
          <!-- Workspace icon -->
          <div
            class="w-5 h-5 rounded-md bg-blue-500/10 dark:bg-blue-400/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-3.5 h-3.5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-6l-2-2H5a2 2 0 0 0-2 2z"
              />
            </svg>
          </div>

          <span class="max-w-24 min-[400px]:max-w-32 sm:max-w-44 truncate">
            {{ workspaceStore.currentWorkspace?.name || t("allWorkspaces") }}
          </span>

          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-3.5 h-3.5 opacity-60 transition-transform duration-150"
            :class="{ 'rotate-180': workspaceMenuOpen }"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path d="m6 9 6 6 6-6" />
          </svg>
        </button>

        <!-- Workspace Dropdown -->
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="opacity-0 scale-95 translate-y-1"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-1"
        >
          <div
            v-if="workspaceMenuOpen"
            class="absolute inset-x-3 top-full mt-2 sm:inset-x-auto sm:start-0 sm:w-64 max-h-[calc(100dvh-5rem)] overflow-y-auto overscroll-contain bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700/90 rounded-2xl shadow-xl py-2 z-50 text-xs"
          >
            <div
              class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
            >
              {{ t("workspaces") }}
            </div>

            <div class="max-h-56 overflow-y-auto px-1 space-y-0.5">
              <!-- All Workspaces option -->
              <button
                type="button"
                class="w-full flex items-center justify-between px-3 py-2.5 sm:py-2 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors text-start cursor-pointer"
                :class="{
                  'bg-blue-50 dark:bg-blue-900/30 font-semibold text-blue-600 dark:text-blue-300':
                    !workspaceStore.currentWorkspaceId,
                }"
                @click="selectWorkspace(null)"
              >
                <span>{{ t("allWorkspaces") }}</span>
                <svg
                  v-if="!workspaceStore.currentWorkspaceId"
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-4 h-4 text-blue-600"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                >
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              </button>

              <!-- Specific Workspaces -->
              <button
                v-for="ws in workspaceStore.workspaces"
                :key="ws.id"
                type="button"
                class="w-full flex items-center justify-between px-3 py-2.5 sm:py-2 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors text-start cursor-pointer"
                :class="{
                  'bg-blue-50 dark:bg-blue-900/30 font-semibold text-blue-600 dark:text-blue-300':
                    String(workspaceStore.currentWorkspaceId) === String(ws.id),
                }"
                @click="selectWorkspace(ws.id)"
              >
                <div class="min-w-0 flex-1 me-2">
                  <div class="truncate">{{ ws.name }}</div>
                  <div class="text-[10px] text-slate-400 capitalize">
                    {{ ws.current_user_role }}
                  </div>
                </div>
                <svg
                  v-if="
                    String(workspaceStore.currentWorkspaceId) === String(ws.id)
                  "
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-4 h-4 text-blue-600 shrink-0"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                >
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              </button>
            </div>

            <!-- Workspace Actions Divider -->
            <div
              class="my-2 border-t border-slate-100 dark:border-slate-700/60"
            />

            <div class="px-1 space-y-0.5">
              <!-- Manage Members (if active workspace) -->
              <button
                v-if="workspaceStore.currentWorkspace"
                type="button"
                class="w-full flex items-center gap-2 px-3 py-2.5 sm:py-1.5 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer"
                @click="openMembersModal"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-4 h-4 opacity-70"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                <span>{{ t("members") }}</span>
              </button>

              <!-- Manage Teams (if active workspace) -->
              <button
                v-if="workspaceStore.currentWorkspace"
                type="button"
                class="w-full flex items-center gap-2 px-3 py-2.5 sm:py-1.5 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer"
                @click="openTeamsModal"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-4 h-4 opacity-70"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                <span>{{ t("teams") }}</span>
              </button>

              <!-- Workspace Settings (if owner or admin) -->
              <button
                v-if="workspaceStore.canEditWorkspace"
                type="button"
                class="w-full flex items-center gap-2 px-3 py-2.5 sm:py-1.5 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer"
                @click="openEditWorkspace"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-4 h-4 opacity-70"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <circle cx="12" cy="12" r="3" />
                  <path
                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"
                  />
                </svg>
                <span>{{ t("editWorkspace") }}</span>
              </button>

              <!-- Create New Workspace -->
              <button
                type="button"
                class="w-full flex items-center gap-2 px-3 py-2.5 sm:py-1.5 rounded-xl text-blue-600 dark:text-blue-400 font-medium hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors cursor-pointer"
                @click="openCreateWorkspace"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-4 h-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                >
                  <path d="M12 5v14M5 12h14" />
                </svg>
                <span>{{ t("createWorkspace") }}</span>
              </button>

              <!-- Delete Workspace (if owner) -->
              <button
                v-if="workspaceStore.canDeleteWorkspace"
                type="button"
                class="w-full flex items-center gap-2 px-3 py-2.5 sm:py-1.5 rounded-xl text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer"
                @click="promptDeleteWorkspace"
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
                </svg>
                <span>{{ t("deleteWorkspace") }}</span>
              </button>
            </div>
          </div>
        </transition>
      </div>

      <div class="flex-1" />

      <!-- Controls -->
      <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
        <!-- Language toggle (sm+; on phones it lives in the user menu) -->
        <button
          class="hidden sm:inline-flex px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-2xs hover:shadow-xs cursor-pointer active:scale-95"
          :title="t('language')"
          @click="prefs.toggleLocale()"
        >
          <span class="inline-flex items-center gap-1">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-3.5 h-3.5 opacity-60"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="12" cy="12" r="10" />
              <line x1="2" y1="12" x2="22" y2="12" />
              <path
                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
              />
            </svg>
            {{ prefs.locale === "ar" ? "English" : "العربية" }}
          </span>
        </button>

        <!-- Theme toggle (sm+; on phones it lives in the user menu) -->
        <button
          class="hidden sm:inline-flex p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-2xs hover:shadow-xs cursor-pointer active:scale-95"
          :title="prefs.isDark ? t('lightMode') : t('darkMode')"
          @click="prefs.toggleTheme()"
        >
          <svg
            v-if="prefs.isDark"
            xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4 text-amber-400"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle cx="12" cy="12" r="5" />
            <path
              d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
            />
          </svg>
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4 text-slate-600"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
          </svg>
        </button>

        <!-- Notification Bell Dropdown -->
        <NotificationDropdown v-if="authStore.isAuthenticated" />

        <!-- User menu -->
        <div class="relative sm:ms-1">
          <button
            class="flex items-center gap-1.5 sm:gap-2 p-1 sm:p-1.5 pe-1.5 sm:pe-2.5 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-700 hover:bg-white dark:hover:bg-slate-800 transition-all text-sm text-slate-700 dark:text-slate-200 cursor-pointer"
            @click="
              userMenuOpen = !userMenuOpen;
              workspaceMenuOpen = false;
            "
          >
            <div
              class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-xs font-bold uppercase shadow-xs"
            >
              {{ authStore.user?.name?.charAt(0) ?? "U" }}
            </div>
            <span
              class="hidden sm:block max-w-28 truncate font-medium text-xs"
              >{{ authStore.user?.name }}</span
            >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-3.5 h-3.5 opacity-60 transition-transform duration-150"
              :class="{ 'rotate-180': userMenuOpen }"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <path d="m6 9 6 6 6-6" />
            </svg>
          </button>

          <!-- User Dropdown -->
          <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
          >
            <div
              v-if="userMenuOpen"
              class="absolute end-0 top-full mt-2 w-56 max-w-[calc(100vw-1.5rem)] bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700/90 rounded-2xl shadow-xl py-1.5 z-50 divide-y divide-slate-100 dark:divide-slate-700/60"
            >
              <div class="px-4 py-3">
                <p
                  class="text-xs font-semibold text-slate-900 dark:text-white truncate"
                >
                  {{ authStore.user?.name }}
                </p>
                <p
                  class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5"
                >
                  {{ authStore.user?.email }}
                </p>
              </div>

              <!-- Phone-only: language & theme -->
              <div class="p-1 sm:hidden">
                <button
                  type="button"
                  class="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 rounded-xl transition-colors cursor-pointer"
                  @click="prefs.toggleLocale()"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 opacity-70"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <circle cx="12" cy="12" r="10" />
                    <line x1="2" y1="12" x2="22" y2="12" />
                    <path
                      d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                    />
                  </svg>
                  <span>{{
                    prefs.locale === "ar" ? "English" : "العربية"
                  }}</span>
                </button>
                <button
                  type="button"
                  class="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 rounded-xl transition-colors cursor-pointer"
                  @click="prefs.toggleTheme()"
                >
                  <svg
                    v-if="prefs.isDark"
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 text-amber-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <circle cx="12" cy="12" r="5" />
                    <path
                      d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
                    />
                  </svg>
                  <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 text-slate-600 dark:text-slate-300"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                  </svg>
                  <span>{{
                    prefs.isDark ? t("lightMode") : t("darkMode")
                  }}</span>
                </button>
              </div>

              <div class="p-1">
                <button
                  class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-xl transition-colors cursor-pointer"
                  @click="handleLogout"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 rtl:rotate-180"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                  </svg>
                  <span>{{ t("logout") }}</span>
                </button>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </header>

    <!-- ── Page content ────────────────────────────────────── -->
    <main class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
      <slot />
    </main>
  </div>

  <!-- Click-outside overlay for dropdowns -->
  <div
    v-if="userMenuOpen || workspaceMenuOpen"
    class="fixed inset-0 z-20"
    @click="
      userMenuOpen = false;
      workspaceMenuOpen = false;
    "
  />

  <!-- Workspace Create / Edit Modal -->
  <WorkspaceModal
    v-if="showWorkspaceModal"
    :workspace="workspaceToEdit"
    :loading="isWorkspaceSubmitting"
    @save="handleSaveWorkspace"
    @close="
      showWorkspaceModal = false;
      workspaceToEdit = null;
    "
  />

  <!-- Workspace Members Modal -->
  <WorkspaceMembersModal
    v-if="showMembersModal && workspaceStore.currentWorkspace"
    :workspace="workspaceStore.currentWorkspace"
    @close="showMembersModal = false"
  />

  <!-- Workspace Teams Modal -->
  <TeamsOverviewModal
    v-if="showTeamsModal && workspaceStore.currentWorkspace"
    :workspace="workspaceStore.currentWorkspace"
    @close="showTeamsModal = false"
  />

  <!-- Confirm Delete Workspace Dialog -->
  <ConfirmDialog
    v-if="showDeleteConfirm"
    :title="t('deleteWorkspace')"
    :message="t('deleteWorkspaceConfirm')"
    :confirm-label="t('delete')"
    :cancel-label="t('cancel')"
    :loading="isWorkspaceDeleting"
    variant="danger"
    @confirm="handleDeleteWorkspace"
    @cancel="showDeleteConfirm = false"
  />
</template>
