<script setup>
import { onMounted, ref, computed, reactive, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePrenotazioneModule } from '@/stores/prenotazioneModule';
import { useLabModule } from '@/stores/labModule';

// Router e route
const route = useRoute();
const router = useRouter();
const laboratorioId = computed(() => route.params.id);

// Store
const prenotazioneStore = usePrenotazioneModule();
const labStore = useLabModule();

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
  data_prenotazione: ''
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
  } catch (error) {
    console.error('Errore nel caricamento delle prenotazioni:', error);
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

function openCreateModal() {
  resetForm();
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
  if (!formData.utente_id.trim()) {
    formErrors.utente_id = 'L\'ID utente è obbligatorio';
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
  
  try {
    await prenotazioneStore.createPrenotazione({
      utente_id: formData.utente_id,
      laboratorio_id: formData.laboratorio_id || laboratorioId.value,
      data_prenotazione: formData.data_prenotazione,
      ora_inizio: formData.ora_inizio || null,
      ora_fine: formData.ora_fine || null
    });
    
    closeModal();
  } catch (error) {
    console.error('Errore durante il salvataggio della prenotazione:', error);
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
            <td>{{ prenotazione.utente_id }}</td>
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
          <form @submit.prevent="handleSubmit">
            <div class="form-group">
              <label for="utente_id">ID Utente *</label>
              <input 
                id="utente_id"
                v-model="formData.utente_id"
                type="text"
                placeholder="Inserisci l'ID dell'utente"
                required
              />
              <div v-if="formErrors.utente_id" class="error-message">{{ formErrors.utente_id }}</div>
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

@media (max-width: 768px) {
  .lab-info {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
