import { defineStore } from 'pinia'
import { prenotazioneService } from '../service/prenotazione.service'
import { useAuthModule } from './authModule'

export const usePrenotazioneModule = defineStore('prenotazione', {
  state: () => ({
    prenotazioni: [],
    loading: false,
    error: null
  }),
  
  getters: {
    getPrenotazioni: (state) => state.prenotazioni,
    isLoading: (state) => state.loading
  },
  
  actions: {
    async fetchPrenotazioniByLaboratorio(laboratorioId) {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'User not authenticated'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await prenotazioneService.getByLaboratorioId(laboratorioId, sessionId)
        this.prenotazioni = response.data || []
        
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch reservations'
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async createPrenotazione(prenotazione) {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'User not authenticated'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await prenotazioneService.create(prenotazione, sessionId)
        
        // Refresh the list after creation if we have a laboratorio ID
        if (prenotazione.laboratorioId) {
          await this.fetchPrenotazioniByLaboratorio(prenotazione.laboratorioId)
        }
        
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create reservation'
        throw error
      } finally {
        this.loading = false
      }
    },
    
    // Reset state (useful when logging out)
    resetState() {
      this.prenotazioni = []
      this.loading = false
      this.error = null
    }
  }
})
