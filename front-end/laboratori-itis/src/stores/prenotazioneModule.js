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
        this.error = 'Utente non autenticato'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await prenotazioneService.getByLaboratorioId(laboratorioId, sessionId)
        this.prenotazioni = response.data || []
        
        return response
      } catch (error) {
        // Gestione migliorata degli errori API
        if (error.response?.data?.error) {
          this.error = error.response.data.error
        } else {
          this.error = 'Errore durante il caricamento delle prenotazioni'
        }
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async createPrenotazione(prenotazione) {
      const authStore = useAuthModule()
      const sessionId = authStore.getSessionId
      
      if (!sessionId) {
        this.error = 'Utente non autenticato'
        return
      }
      
      try {
        this.loading = true
        this.error = null
        
        const response = await prenotazioneService.create(prenotazione, sessionId)
        
        // Refresh the list after creation if we have a laboratorio ID
        if (prenotazione.laboratorio_id) {
          await this.fetchPrenotazioniByLaboratorio(prenotazione.laboratorio_id)
        }
        
        return response
      } catch (error) {
        // Gestione migliorata degli errori API
        if (error.response?.data?.error) {
          this.error = error.response.data.error
        } else {
          this.error = 'Errore durante la creazione della prenotazione'
        }
        
        // Propaga l'errore originale per permettere una gestione più dettagliata nel componente
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
