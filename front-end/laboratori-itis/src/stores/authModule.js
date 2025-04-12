import { defineStore } from 'pinia'
import { authService } from '../service/auth.service'

export const useAuthModule = defineStore('auth', {
  state: () => ({
    user: null,
    sessionId: null,
    isAuthenticated: false,
    error: null
  }),
  
  getters: {
    getUser: (state) => state.user,
    getSessionId: (state) => state.sessionId,
    isLoggedIn: (state) => state.isAuthenticated
  },
  
  actions: {
    async login(credentials) {
      try {
        this.error = null
        const response = await authService.login(credentials)

        if (response.data && response.data.sessione_id) {
          this.sessionId = response.data.sessione_id
          this.user = response.data.user || { username: credentials.username }
          this.isAuthenticated = true
          
          // Store session ID in localStorage for persistence
          localStorage.setItem('sessionId', this.sessionId)
          
          return response
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed'
        throw error
      }
    },
    
    async logout() {
      try {
        if (this.sessionId) {
          await authService.logout(this.sessionId)
        }
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        // Clear state regardless of API success
        this.user = null
        this.sessionId = null
        this.isAuthenticated = false
        localStorage.removeItem('sessionId')
        
        // Reset other store modules
        this.resetOtherStores()
      }
    },
    
    // Initialize auth state from localStorage (call this on app startup)
    initAuth() {
      const sessionId = localStorage.getItem('sessionId')
      if (sessionId) {
        this.sessionId = sessionId
        this.isAuthenticated = true
      }
    },
    
    // Reset all other store modules
    resetOtherStores() {
      // Import dynamically to avoid circular dependencies
      import('./labModule').then(module => {
        const labStore = module.useLabModule()
        labStore.resetState()
      })
      
      import('./prenotazioneModule').then(module => {
        const prenotazioneStore = module.usePrenotazioneModule()
        prenotazioneStore.resetState()
      })
    }
  }
})
