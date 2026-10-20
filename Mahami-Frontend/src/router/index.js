import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const routes = [
  {
    path: '/',
    redirect: '/boards',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { guestOnly: true, layout: 'auth' },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/pages/RegisterPage.vue'),
    meta: { guestOnly: true, layout: 'auth' },
  },
  {
    path: '/boards',
    name: 'boards',
    component: () => import('@/pages/BoardsPage.vue'),
    meta: { requiresAuth: true, layout: 'main' },
  },
  {
    path: '/boards/:id',
    name: 'board-details',
    component: () => import('@/pages/BoardDetailsPage.vue'),
    meta: { requiresAuth: true, layout: 'main' },
    props: true,
  },
  {
    path: '/invitations/:token',
    name: 'accept-invitation',
    component: () => import('@/pages/AcceptInvitationPage.vue'),
    meta: { layout: 'main' },
    props: true,
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
    meta: { layout: 'main' },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation Guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Initialize auth state on first navigation if needed
  if (!authStore.isInitialized) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.meta.guestOnly && authStore.isAuthenticated) {
    next({ name: 'boards' })
  } else {
    next()
  }
})

export default router

