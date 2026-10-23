<script setup>
import { ref, reactive, watch } from "vue";
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
  // When provided (e.g. in "All Workspaces" mode) the user picks where the new board goes
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
      <AppInput
        v-model="form.title"
        :label="t('boardTitle')"
        :placeholder="t('boardTitlePlaceholder')"
        :error="errors.title"
        required
        autofocus
      />

      <div v-if="!board && workspaces" class="space-y-1.5">
        <label
          class="block text-sm font-medium text-slate-700 dark:text-slate-300"
        >
          {{ t("boardWorkspace") }}
        </label>
        <select
          v-model="form.workspace_id"
          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all"
        >
          <option v-for="ws in workspaces" :key="ws.id" :value="ws.id">
            {{ ws.name }}
          </option>
          <option value="">{{ t("noWorkspacePersonal") }}</option>
        </select>
      </div>

      <AppInput
        v-model="form.description"
        type="textarea"
        :label="t('boardDescription')"
        :placeholder="t('boardDescriptionPlaceholder')"
      />

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
