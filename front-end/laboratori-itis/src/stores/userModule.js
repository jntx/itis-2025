import { defineStore } from 'pinia'
import { userService } from '../service/user.service'
import { useAuthModule } from './authModule'

export const useUserModule = defineStore('user', {
  state: () => ({
    users: [],
    loading: false,
    error: null
  }),
  
  getters: {
    getUsers: (state) => state.users,
    isLoading: (state) => state.loading
  },
  
  actions: {
    async fetchAllUsers() {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'Utente non autenticato'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await userService.getAllUsers(sessionId)
        this.users = response.data || []
        
        return response
      } catch (error) {
        // Gestione degli errori API
        if (error.response?.data?.error) {
          this.error = error.response.data.error
        } else {
          this.error = 'Errore durante il caricamento degli utenti'
        }
        throw error
      } finally {
        this.loading = false
      }
    },
    
    // Reset state (useful when logging out)
    resetState() {
      this.users = []
      this.loading = false
      this.error = null
    }
  }
})
