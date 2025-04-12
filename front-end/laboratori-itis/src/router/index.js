import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LabView from '@/views/LabView.vue'
import LoginView from '@/views/LoginView.vue'
import PrenotazioniView from '@/views/PrenotazioniView.vue'
import { useAuthModule } from '@/stores/authModule'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true }
    },
    {
      path: '/lab',
      name: 'laboratori',
      component: LabView,
      meta: { requiresAuth: true }
    },
    {
      path: '/prenotazioni/:id',
      name: 'prenotazioni',
      component: PrenotazioniView,
      meta: { requiresAuth: true }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true }
    },
  ],
})

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
  const authStore = useAuthModule()
  const isAuthenticated = authStore.isLoggedIn
  
  // If route requires auth and user is not authenticated, redirect to login
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login' })
  } 
  // If user is authenticated and tries to access login page, redirect to home
  else if (to.meta.guest && isAuthenticated) {
    next({ name: 'home' })
  }
  else {
    next()
  }
})

export default router
