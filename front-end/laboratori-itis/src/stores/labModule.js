import { defineStore } from 'pinia'
import { labService } from '../service/lab.service'
import { useAuthModule } from './authModule'

export const useLabModule = defineStore('lab', {
  state: () => ({
    laboratori: [],
    currentLaboratorio: null,
    loading: false,
    error: null
  }),
  
  getters: {
    getLaboratori: (state) => state.laboratori,
    getCurrentLaboratorio: (state) => state.currentLaboratorio,
    isLoading: (state) => state.loading
  },
  
  actions: {
    async fetchAllLaboratori() {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'User not authenticated'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await labService.retrieveAll(sessionId)
        this.laboratori = response.data || []
        
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch laboratories'
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async fetchLaboratorioById(id) {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'User not authenticated'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await labService.getById(id, sessionId)
        this.currentLaboratorio = response.data || null
        
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch laboratory'
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async createLaboratorio(laboratorio) {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'User not authenticated'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await labService.create(laboratorio, sessionId)
        
        // Refresh the list after creation
        await this.fetchAllLaboratori()
        
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create laboratory'
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async updateLaboratorio(id, laboratorio) {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'User not authenticated'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await labService.update(id, laboratorio, sessionId)
        
        // Refresh the list and current item after update
        await this.fetchAllLaboratori()
        if (this.currentLaboratorio && this.currentLaboratorio.id === id) {
          await this.fetchLaboratorioById(id)
        }
        
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update laboratory'
        throw error
      } finally {
        this.loading = false
      }
    },
    
    // Reset state (useful when logging out)
    resetState() {
      this.laboratori = []
      this.currentLaboratorio = null
      this.loading = false
      this.error = null
    }
  }
})
