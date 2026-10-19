<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import MainLayout from '@/layouts/MainLayout.vue'
import AppToast from '@/components/common/AppToast.vue'

const route = useRoute()

const layout = computed(() => {
  if (route.meta.layout === 'auth') return AuthLayout
  return MainLayout
})
</script>

<template>
  <component :is="layout">
    <router-view />
  </component>

  <!-- Toast notifications layer – always on top -->
  <AppToast />
</template>
