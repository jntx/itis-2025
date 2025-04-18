<script setup>
import { onMounted, ref, computed, reactive, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePrenotazioneModule } from '@/stores/prenotazioneModule';
import { useLabModule } from '@/stores/labModule';
import { useUserModule } from '@/stores/userModule';

// Router e route
const route = useRoute();
const router = useRouter();
const laboratorioId = computed(() => route.params.id);

// Store
const prenotazioneStore = usePrenotazioneModule();
const labStore = useLabModule();
const userStore = useUserModule();

// Reactive state
const searchQuery = ref('');
const laboratorio = ref(null);
const showModal = ref(false);
const formData = reactive({
  utente_id: '',
  laboratorio_id: '',
  data_prenotazione: '',
  ora_inizio: '',
  ora_fine: ''
});
const formErrors = reactive({
  utente_id: '',
  data_prenotazione: '',
  api: '' // Nuovo campo per gli errori API
});

// Computed properties
const filteredPrenotazioni = computed(() => {
  if (!searchQuery.value) {
    return prenotazioneStore.getPrenotazioni;
  }
  
  const query = searchQuery.value.toLowerCase();
  return prenotazioneStore.getPrenotazioni.filter(prenotazione => 
    prenotazione.data_prenotazione?.toLowerCase().includes(query) || 
    prenotazione.stato?.toLowerCase().includes(query)
  );
});

// Funzione per ottenere il nome completo dell'utente dato l'ID
const getUserName = (userId) => {
  if (!userId || !userStore.getUsers.length) return 'N/A';
  
  const user = userStore.getUsers.find(u => u.id == userId);
  if (!user) return `ID: ${userId}`;
  
  return `${user.cognome} ${user.nome}`;
};

// Lifecycle hooks
onMounted(async () => {
  try {
    // Carica il laboratorio
    if (!labStore.getLaboratori.length) {
      await labStore.fetchAllLaboratori();
    }
    
    const lab = labStore.getLaboratori.find(l => l.id == laboratorioId.value);
    if (lab) {
      laboratorio.value = lab;
    }
    
    // Carica le prenotazioni
    await prenotazioneStore.fetchPrenotazioniByLaboratorio(laboratorioId.value);
    
    // Carica gli utenti
    await userStore.fetchAllUsers();
  } catch (error) {
    console.error('Errore nel caricamento dei dati:', error);
  }
});

// Watch per cambiamenti nell'ID del laboratorio
watch(laboratorioId, async (newId) => {
  if (newId) {
    try {
      await prenotazioneStore.fetchPrenotazioniByLaboratorio(newId);
      
      // Aggiorna il laboratorio corrente
      const lab = labStore.getLaboratori.find(l => l.id == newId);
      if (lab) {
        laboratorio.value = lab;
      }
    } catch (error) {
      console.error('Errore nel caricamento delle prenotazioni:', error);
    }
  }
});

// Methods
function tornaAiLaboratori() {
  router.push({ name: 'laboratori' });
}

async function openCreateModal() {
  resetForm();
  
  // Assicurati che gli utenti siano caricati
  if (!userStore.getUsers.length && !userStore.isLoading) {
    try {
      await userStore.fetchAllUsers();
    } catch (error) {
      console.error('Errore nel caricamento degli utenti:', error);
    }
  }
  
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  resetForm();
}

function resetForm() {
  formData.utente_id = '';
  formData.laboratorio_id = laboratorioId.value;
  formData.data_prenotazione = '';
  formData.ora_inizio = '';
  formData.ora_fine = '';
  
  // Reset validation errors
  Object.keys(formErrors).forEach(key => {
    formErrors[key] = '';
  });
}

function validateForm() {
  let isValid = true;
  
  // Reset errors
  Object.keys(formErrors).forEach(key => {
    formErrors[key] = '';
  });
  
  // Validate required fields
  if (!formData.utente_id) {
    formErrors.utente_id = 'La selezione di un utente è obbligatoria';
    isValid = false;
  }
  
  if (!formData.data_prenotazione.trim()) {
    formErrors.data_prenotazione = 'La data è obbligatoria';
    isValid = false;
  }
  
  return isValid;
}

async function handleSubmit() {
  if (!validateForm()) {
    return;
  }
  
  // Reset API error
  formErrors.api = '';
  
  try {
    await prenotazioneStore.createPrenotazione({
      utente_id: formData.utente_id,
      laboratorio_id: formData.laboratorio_id || laboratorioId.value,
      data_prenotazione: formData.data_prenotazione,
      ora_inizio: formData.ora_inizio || null,
      ora_fine: formData.ora_fine || null
    });
    
    // Explicitly refresh the list of reservations after creating a new one
    await prenotazioneStore.fetchPrenotazioniByLaboratorio(laboratorioId.value);
    
    closeModal();
  } catch (error) {
    console.error('Errore durante il salvataggio della prenotazione:', error);
    
    // Gestione degli errori API
    if (error.response && error.response.data && error.response.data.error) {
      formErrors.api = error.response.data.error;
      
      // Aggiungi informazioni aggiuntive se disponibili
      if (error.response.data.periodo_validita) {
        formErrors.api += ` (Periodo valido: dal ${formatDate(error.response.data.periodo_validita.inizio)} al ${formatDate(error.response.data.periodo_validita.fine)})`;
      }
      
      if (error.response.data.orario_laboratorio) {
        formErrors.api += ` (Orario laboratorio: ${error.response.data.orario_laboratorio.apertura} - ${error.response.data.orario_laboratorio.chiusura})`;
      }
      
      if (error.response.data.capacita) {
        formErrors.api += ` (Capacità: ${error.response.data.capacita}, Prenotazioni attuali: ${error.response.data.prenotazioni_attuali})`;
      }
    } else {
      formErrors.api = 'Si è verificato un errore durante la creazione della prenotazione. Riprova più tardi.';
    }
  }
}

// Format date for display
function formatDate(dateString) {
  if (!dateString) return 'N/A';
  
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('it-IT').format(date);
}

// Format time for display
function formatTime(timeString) {
  if (!timeString) return 'N/A';
  return timeString;
}

// Get status class
function getStatusClass(stato) {
  switch (stato?.toLowerCase()) {
    case 'confermata':
      return 'status-confirmed';
    case 'in attesa':
      return 'status-pending';
    case 'annullata':
      return 'status-cancelled';
    default:
      return '';
  }
}
</script>

<template>
  <div class="container">
    <div class="header-section">
      <div class="title-section">
        <button @click="tornaAiLaboratori" class="btn-back">
          &larr; Torna ai laboratori
        </button>
        <h1>Prenotazioni {{ laboratorio ? `- ${laboratorio.nome}` : '' }}</h1>
      </div>
      <div class="actions">
        <div class="search-container">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Cerca prenotazioni..." 
            class="search-input"
          />
        </div>
        <button @click="openCreateModal" class="btn-primary">
          Nuova Prenotazione
        </button>
      </div>
    </div>
    
    <!-- Dettagli laboratorio -->
    <div v-if="laboratorio" class="lab-details">
      <h2>{{ laboratorio.nome }}</h2>
      <p>{{ laboratorio.descrizione }}</p>
      <div class="lab-info">
        <span><strong>Capacità:</strong> {{ laboratorio.capacita }} persone</span>
        <span v-if="laboratorio.orario_apertura"><strong>Orario:</strong> {{ laboratorio.orario_apertura }} - {{ laboratorio.orario_chiusura }}</span>
      </div>
    </div>
    
    <!-- Loading state -->
    <div v-if="prenotazioneStore.isLoading" class="loading-container">
      <div class="loader"></div>
      <p>Caricamento prenotazioni in corso...</p>
    </div>
    
    <!-- Error state -->
    <div v-else-if="prenotazioneStore.error" class="error-container">
      <p>Si è verificato un errore: {{ prenotazioneStore.error }}</p>
      <button @click="prenotazioneStore.fetchPrenotazioniByLaboratorio(laboratorioId)" class="btn-secondary">
        Riprova
      </button>
    </div>
    
    <!-- Empty state -->
    <div v-else-if="filteredPrenotazioni.length === 0" class="empty-container">
      <p v-if="searchQuery">Nessuna prenotazione trovata per "{{ searchQuery }}"</p>
      <p v-else>Nessuna prenotazione disponibile per questo laboratorio.</p>
    </div>
    
    <!-- Data table -->
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Utente</th>
            <th>Data</th>
            <th>Ora Inizio</th>
            <th>Ora Fine</th>
            <th>Stato</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="prenotazione in filteredPrenotazioni" :key="prenotazione.id">
            <td>{{ prenotazione.id }}</td>
            <td>{{ getUserName(prenotazione.utente_id) }}</td>
            <td>{{ formatDate(prenotazione.data_prenotazione) }}</td>
            <td>{{ formatTime(prenotazione.ora_inizio) }}</td>
            <td>{{ formatTime(prenotazione.ora_fine) }}</td>
            <td>
              <span :class="['status-badge', getStatusClass(prenotazione.stato)]">
                {{ prenotazione.stato || 'N/A' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Modal per nuova prenotazione -->
    <div v-if="showModal" class="modal-backdrop" @click="closeModal">
      <div class="modal-content" @click.stop>
        <!-- Modal header -->
        <div class="modal-header">
          <h2>Nuova Prenotazione</h2>
          <button @click="closeModal" class="btn-close">&times;</button>
        </div>
        
        <!-- Modal body -->
        <div class="modal-body">
          <!-- Messaggio di errore API -->
          <div v-if="formErrors.api" class="api-error-message">
            <p>{{ formErrors.api }}</p>
          </div>
          
          <form @submit.prevent="handleSubmit">
            <div class="form-group">
              <label for="utente_id">Utente *</label>
              <select 
                id="utente_id"
                v-model="formData.utente_id"
                required
                class="select-input"
                :disabled="userStore.isLoading"
              >
                <option value="" disabled>{{ userStore.isLoading ? 'Caricamento utenti...' : 'Seleziona un utente' }}</option>
                <option 
                  v-for="utente in userStore.getUsers" 
                  :key="utente.id" 
                  :value="utente.id"
                >
                  {{ utente.cognome }} {{ utente.nome }} ({{ utente.username }})
                </option>
              </select>
              <div v-if="formErrors.utente_id" class="error-message">{{ formErrors.utente_id }}</div>
              <div v-if="userStore.error" class="error-message">Errore nel caricamento degli utenti: {{ userStore.error }}</div>
            </div>
            
            <div class="form-group">
              <label for="data_prenotazione">Data Prenotazione *</label>
              <input 
                id="data_prenotazione"
                v-model="formData.data_prenotazione"
                type="date"
                required
              />
              <div v-if="formErrors.data_prenotazione" class="error-message">{{ formErrors.data_prenotazione }}</div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="ora_inizio">Ora Inizio</label>
                <input 
                  id="ora_inizio"
                  v-model="formData.ora_inizio"
                  type="time"
                />
              </div>
              
              <div class="form-group">
                <label for="ora_fine">Ora Fine</label>
                <input 
                  id="ora_fine"
                  v-model="formData.ora_fine"
                  type="time"
                />
              </div>
            </div>
            
            <div class="form-actions">
              <button type="button" @click="closeModal" class="btn-secondary">Annulla</button>
              <button type="submit" class="btn-primary">Salva</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Component-specific styles only */
.lab-details {
  background-color: #f8fafc;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.lab-details h2 {
  margin-top: 0;
  margin-bottom: 0.5rem;
}

.lab-details p {
  margin-bottom: 1rem;
}

.lab-info {
  display: flex;
  gap: 2rem;
}

/* Stile per il messaggio di errore API */
.api-error-message {
  background-color: #fee2e2;
  border: 1px solid #ef4444;
  border-radius: 6px;
  padding: 12px 16px;
  margin-bottom: 16px;
  color: #b91c1c;
}

.api-error-message p {
  margin: 0;
  font-weight: 500;
}

.error-message {
  color: #b91c1c;
  font-size: 0.875rem;
  margin-top: 4px;
}

/* Stile per il select dropdown */
.select-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background-color: #fff;
  font-size: 1rem;
  line-height: 1.5;
  color: #374151;
  appearance: auto;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.select-input:focus {
  border-color: #3b82f6;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

.select-input option {
  padding: 0.5rem;
}

@media (max-width: 768px) {
  .lab-info {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
