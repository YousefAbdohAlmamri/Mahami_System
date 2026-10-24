<script setup>
import { ref, reactive, watch, computed } from "vue";
import { useI18n } from "@/composables/useI18n";
import AppModal from "@/components/common/AppModal.vue";
import AppInput from "@/components/common/AppInput.vue";
import AppButton from "@/components/common/AppButton.vue";

const props = defineProps({
  board: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  // في وضع كل مساحات العمل، يمكن اختيار مساحة العمل التي ستنتمي إليها اللوحة.
  workspaces: {
    type: Array,
    default: null,
  },
  defaultWorkspaceId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(["save", "close"]);
const { t } = useI18n();

const workspaceSearch = ref("");

const filteredWorkspaces = computed(() => {
  const items = props.workspaces || [];
  const query = workspaceSearch.value.trim().toLocaleLowerCase();

  if (!query) return items;

  return items.filter((workspace) =>
    String(workspace.name || "")
      .toLocaleLowerCase()
      .includes(query),
  );
});

const form = reactive({
  title: "",
  description: "",
  workspace_id: props.defaultWorkspaceId ?? "",
});

const errors = reactive({
  title: "",
});

watch(
  () => props.board,
  (val) => {
    if (val) {
      form.title = val.title || "";
      form.description = val.description || "";
    } else {
      form.title = "";
      form.description = "";
    }

    errors.title = "";
  },
  { immediate: true },
);

function handleSubmit() {
  errors.title = "";

  if (!form.title.trim()) {
    errors.title = t("validationError");
    return;
  }

  const payload = {
    title: form.title.trim(),
    description: form.description ? form.description.trim() : null,
  };

  // نحافظ على بنية البيانات الحالية عند إنشاء لوحة من وضع كل مساحات العمل.
  if (!props.board && props.workspaces) {
    payload.workspace_id =
      form.workspace_id === "" ? null : Number(form.workspace_id);
  }

  emit("save", payload);
}
</script>

<template>
  <AppModal
    :title="board ? t('editBoard') : t('createBoard')"
    :close-on-backdrop="!loading"
    @close="emit('close')"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <!-- عنوان اللوحة -->
      <AppInput
        v-model="form.title"
        :label="t('boardTitle')"
        :placeholder="t('boardTitlePlaceholder')"
        :error="errors.title"
        required
        autofocus
      />

      <!-- اختيار مساحة العمل عند إنشاء لوحة جديدة -->
      <section
        v-if="!board && workspaces"
        class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700 dark:bg-slate-900/40"
      >
        <!-- عنوان القسم والوصف -->
        <div class="flex items-start gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300"
            aria-hidden="true"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.75 6.75A2.25 2.25 0 0 1 6 4.5h4.1l2 2H18A2.25 2.25 0 0 1 20.25 8.75v8.5A2.25 2.25 0 0 1 18 19.5H6A2.25 2.25 0 0 1 3.75 17.25z"
              />
            </svg>
          </div>

          <div class="min-w-0 flex-1 pt-0.5">
            <label
              class="block text-sm font-semibold text-slate-900 dark:text-white"
            >
              {{ t("boardWorkspace") }}
            </label>

            <p
              class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400"
            >
              {{ t("boardWorkspaceHint") }}
            </p>
          </div>

          <span
            class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700"
          >
            {{ t("optional") }}
          </span>
        </div>

        <!-- البحث: يظهر عند وجود أكثر من أربع مساحات عمل -->
        <div v-if="workspaces.length > 4" class="relative">
          <svg
            class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            aria-hidden="true"
          >
            <circle cx="10.8" cy="10.8" r="6.8" />
            <path stroke-linecap="round" d="m16 16 4.2 4.2" />
          </svg>

          <input
            v-model="workspaceSearch"
            type="search"
            :placeholder="t('searchWorkspaces')"
            :aria-label="t('searchWorkspaces')"
            class="w-full rounded-xl border border-slate-200 bg-white py-2.5 ps-9 pe-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          />
        </div>

        <!-- بطاقات مساحات العمل -->
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <button
            v-for="ws in filteredWorkspaces"
            :key="ws.id"
            type="button"
            :aria-pressed="String(form.workspace_id) === String(ws.id)"
            @click="form.workspace_id = ws.id"
            :class="[
              'group flex min-w-0 items-center gap-3 rounded-xl border p-3 text-start transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900',
              String(form.workspace_id) === String(ws.id)
                ? 'border-blue-500 bg-blue-50 shadow-sm shadow-blue-500/10 ring-2 ring-blue-500/10 dark:border-blue-400 dark:bg-blue-500/10'
                : 'border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500/60 dark:hover:bg-slate-800/80',
            ]"
          >
            <!-- رمز مساحة العمل -->
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-sm font-bold text-white shadow-sm"
              aria-hidden="true"
            >
              {{ (ws.name || "W").trim().charAt(0).toLocaleUpperCase() }}
            </span>

            <!-- اسم مساحة العمل ووصفها -->
            <span class="min-w-0 flex-1">
              <span
                class="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100"
              >
                {{ ws.name }}
              </span>

              <span
                class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400"
              >
                {{ t("workspaceBoardDestination") }}
              </span>
            </span>

            <!-- علامة تحديد البطاقة -->
            <span
              :class="[
                'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition-colors',
                String(form.workspace_id) === String(ws.id)
                  ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-400 dark:bg-blue-400 dark:text-slate-950'
                  : 'border-slate-300 text-transparent group-hover:border-blue-400 dark:border-slate-600',
              ]"
              aria-hidden="true"
            >
              <svg
                viewBox="0 0 20 20"
                class="h-3.5 w-3.5"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m4 10 4 4 8-8"
                />
              </svg>
            </span>
          </button>

          <!-- خيار اللوحة الشخصية -->
          <button
            type="button"
            :aria-pressed="
              form.workspace_id === '' || form.workspace_id === null
            "
            @click="form.workspace_id = ''"
            :class="[
              'group flex min-w-0 items-center gap-3 rounded-xl border p-3 text-start transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900',
              form.workspace_id === '' || form.workspace_id === null
                ? 'border-blue-500 bg-blue-50 shadow-sm shadow-blue-500/10 ring-2 ring-blue-500/10 dark:border-blue-400 dark:bg-blue-500/10'
                : 'border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500/60 dark:hover:bg-slate-800/80',
            ]"
          >
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-200"
              aria-hidden="true"
            >
              <svg
                viewBox="0 0 24 24"
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <rect x="4" y="4" width="16" height="16" rx="3" />
                <path stroke-linecap="round" d="M8 9h8M8 12h5" />
              </svg>
            </span>

            <span class="min-w-0 flex-1">
              <span
                class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
              >
                {{ t("noWorkspacePersonal") }}
              </span>

              <span
                class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400"
              >
                {{ t("personalBoardDestination") }}
              </span>
            </span>

            <span
              :class="[
                'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition-colors',
                form.workspace_id === '' || form.workspace_id === null
                  ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-400 dark:bg-blue-400 dark:text-slate-950'
                  : 'border-slate-300 text-transparent group-hover:border-blue-400 dark:border-slate-600',
              ]"
              aria-hidden="true"
            >
              <svg
                viewBox="0 0 20 20"
                class="h-3.5 w-3.5"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m4 10 4 4 8-8"
                />
              </svg>
            </span>
          </button>
        </div>

        <!-- رسالة عند عدم وجود نتائج للبحث -->
        <p
          v-if="workspaceSearch && filteredWorkspaces.length === 0"
          class="rounded-xl border border-dashed border-slate-300 px-3 py-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
        >
          {{ t("noWorkspaceSearchResults") }}
        </p>
      </section>

      <!-- وصف اللوحة -->
      <AppInput
        v-model="form.description"
        type="textarea"
        :label="t('boardDescription')"
        :placeholder="t('boardDescriptionPlaceholder')"
      />

      <!-- أزرار الحفظ والإلغاء -->
      <div class="flex justify-end gap-3 pt-3">
        <AppButton
          variant="secondary"
          :disabled="loading"
          @click="emit('close')"
        >
          {{ t("cancel") }}
        </AppButton>

        <AppButton type="submit" variant="primary" :loading="loading">
          {{ loading ? t("saving") : t("save") }}
        </AppButton>
      </div>
    </form>
  </AppModal>
</template>
