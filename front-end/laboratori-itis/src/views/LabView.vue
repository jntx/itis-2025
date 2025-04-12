<script setup>
import { onMounted, ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useLabModule } from '@/stores/labModule';
import { useAuthModule } from '@/stores/authModule';

// Router
const router = useRouter();

// Store
const labStore = useLabModule();
const authStore = useAuthModule();

// Reactive state
const showModal = ref(false);
const modalMode = ref('view'); // 'view', 'create', 'edit'
const searchQuery = ref('');
const selectedLaboratorio = ref(null);

// Form state
const formData = reactive({
  nome: '',
  descrizione: '',
  capacita: 0,
  orario_apertura: '',
  orario_chiusura: '',
  data_inizio: '',
  data_fine: ''
});

// Form validation
const formErrors = reactive({
  nome: '',
  descrizione: '',
  capacita: ''
});

// Computed properties
const filteredLaboratori = computed(() => {
  if (!searchQuery.value) {
    return labStore.getLaboratori;
  }
  
  const query = searchQuery.value.toLowerCase();
  return labStore.getLaboratori.filter(lab => 
    lab.nome.toLowerCase().includes(query) || 
    lab.descrizione.toLowerCase().includes(query)
  );
});

// Lifecycle hooks
onMounted(async () => {
  try {
    await labStore.fetchAllLaboratori();
  } catch (error) {
    console.error('Errore nel caricamento dei laboratori:', error);
  }
});

// Methods
function openViewModal(laboratorio) {
  selectedLaboratorio.value = laboratorio;
  modalMode.value = 'view';
  showModal.value = true;
}

function navigateToPrenotazioni(laboratorioId) {
  router.push({ name: 'prenotazioni', params: { id: laboratorioId } });
}

function openCreateModal() {
  resetForm();
  modalMode.value = 'create';
  showModal.value = true;
}

function openEditModal(laboratorio) {
  selectedLaboratorio.value = laboratorio;
  
  // Populate form with selected laboratorio data
  formData.nome = laboratorio.nome;
  formData.descrizione = laboratorio.descrizione;
  formData.capacita = laboratorio.capacita;
  formData.orario_apertura = laboratorio.orario_apertura || '';
  formData.orario_chiusura = laboratorio.orario_chiusura || '';
  formData.data_inizio = laboratorio.data_inizio || '';
  formData.data_fine = laboratorio.data_fine || '';
  
  modalMode.value = 'edit';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  resetForm();
}

function resetForm() {
  formData.nome = '';
  formData.descrizione = '';
  formData.capacita = 0;
  formData.orario_apertura = '';
  formData.orario_chiusura = '';
  formData.data_inizio = '';
  formData.data_fine = '';
  
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
  if (!formData.nome.trim()) {
    formErrors.nome = 'Il nome è obbligatorio';
    isValid = false;
  }
  
  if (!formData.descrizione.trim()) {
    formErrors.descrizione = 'La descrizione è obbligatoria';
    isValid = false;
  }
  
  if (!formData.capacita || formData.capacita <= 0) {
    formErrors.capacita = 'La capacità deve essere maggiore di zero';
    isValid = false;
  }
  
  return isValid;
}

async function handleSubmit() {
  if (!validateForm()) {
    return;
  }
  
  try {
    if (modalMode.value === 'create') {
      await labStore.createLaboratorio(formData);
    } else if (modalMode.value === 'edit' && selectedLaboratorio.value) {
      await labStore.updateLaboratorio(selectedLaboratorio.value.id, formData);
    }
    
    closeModal();
  } catch (error) {
    console.error('Errore durante il salvataggio:', error);
  }
}

// Format date for display
function formatDate(dateString) {
  if (!dateString) return 'N/A';
  
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('it-IT').format(date);
}
</script>

<template>
  <div class="laboratori-container">
    <div class="header-section">
      <h1>Gestione Laboratori</h1>
      <div class="actions">
        <div class="search-container">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Cerca laboratori..." 
            class="search-input"
          />
        </div>
        <button @click="openCreateModal" class="btn-primary">
          Nuovo Laboratorio
        </button>
      </div>
    </div>
    
    <!-- Loading state -->
    <div v-if="labStore.isLoading" class="loading-container">
      <div class="loader"></div>
      <p>Caricamento laboratori in corso...</p>
    </div>
    
    <!-- Error state -->
    <div v-else-if="labStore.error" class="error-container">
      <p>Si è verificato un errore: {{ labStore.error }}</p>
      <button @click="labStore.fetchAllLaboratori" class="btn-secondary">
        Riprova
      </button>
    </div>
    
    <!-- Empty state -->
    <div v-else-if="filteredLaboratori.length === 0" class="empty-container">
      <p v-if="searchQuery">Nessun laboratorio trovato per "{{ searchQuery }}"</p>
      <p v-else>Nessun laboratorio disponibile. Creane uno nuovo!</p>
    </div>
    
    <!-- Data table -->
    <div v-else class="table-container">
      <table class="laboratori-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Descrizione</th>
            <th>Capacità</th>
            <th>Orario Apertura</th>
            <th>Orario Chiusura</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lab in filteredLaboratori" :key="lab.id">
            <td>{{ lab.id }}</td>
            <td>{{ lab.nome }}</td>
            <td class="description-cell">{{ lab.descrizione }}</td>
            <td>{{ lab.capacita }} persone</td>
            <td>{{ lab.orario_apertura || 'N/A' }}</td>
            <td>{{ lab.orario_chiusura || 'N/A' }}</td>
            <td class="actions-cell">
              <button @click="openViewModal(lab)" class="btn-icon btn-view" title="Visualizza dettagli">
                👁️
              </button>
              <button @click="openEditModal(lab)" class="btn-icon btn-edit" title="Modifica">
                ✏️
              </button>
              <button @click="navigateToPrenotazioni(lab.id)" class="btn-icon btn-prenotazioni" title="Visualizza prenotazioni">
                📅
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Modal -->
    <div v-if="showModal" class="modal-backdrop" @click="closeModal">
      <div class="modal-content" @click.stop>
        <!-- Modal header -->
        <div class="modal-header">
          <h2 v-if="modalMode === 'view'">Dettagli Laboratorio</h2>
          <h2 v-else-if="modalMode === 'create'">Nuovo Laboratorio</h2>
          <h2 v-else>Modifica Laboratorio</h2>
          <button @click="closeModal" class="btn-close">&times;</button>
        </div>
        
        <!-- View mode -->
        <div v-if="modalMode === 'view'" class="modal-body">
          <div class="detail-item">
            <strong>Nome:</strong> {{ selectedLaboratorio.nome }}
          </div>
          <div class="detail-item">
            <strong>Descrizione:</strong> {{ selectedLaboratorio.descrizione }}
          </div>
          <div class="detail-item">
            <strong>Capacità:</strong> {{ selectedLaboratorio.capacita }} persone
          </div>
          <div class="detail-item">
            <strong>Orario Apertura:</strong> {{ selectedLaboratorio.orario_apertura || 'Non specificato' }}
          </div>
          <div class="detail-item">
            <strong>Orario Chiusura:</strong> {{ selectedLaboratorio.orario_chiusura || 'Non specificato' }}
          </div>
          <div class="detail-item">
            <strong>Disponibile dal:</strong> {{ selectedLaboratorio.data_inizio ? formatDate(selectedLaboratorio.data_inizio) : 'Non specificato' }}
          </div>
          <div class="detail-item">
            <strong>Disponibile fino al:</strong> {{ selectedLaboratorio.data_fine ? formatDate(selectedLaboratorio.data_fine) : 'Non specificato' }}
          </div>
          
          <div class="detail-actions">
            <button @click="navigateToPrenotazioni(selectedLaboratorio.id)" class="btn-secondary">
              Visualizza prenotazioni
            </button>
          </div>
        </div>
        
        <!-- Create/Edit mode -->
        <div v-else class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="form-group">
              <label for="nome">Nome *</label>
              <input 
                id="nome"
                v-model="formData.nome"
                type="text"
                placeholder="Nome del laboratorio"
                required
              />
              <div v-if="formErrors.nome" class="error-message">{{ formErrors.nome }}</div>
            </div>
            
            <div class="form-group">
              <label for="descrizione">Descrizione *</label>
              <textarea 
                id="descrizione"
                v-model="formData.descrizione"
                placeholder="Descrizione del laboratorio"
                rows="3"
                required
              ></textarea>
              <div v-if="formErrors.descrizione" class="error-message">{{ formErrors.descrizione }}</div>
            </div>
            
            <div class="form-group">
              <label for="capacita">Capacità (persone) *</label>
              <input 
                id="capacita"
                v-model.number="formData.capacita"
                type="number"
                min="1"
                required
              />
              <div v-if="formErrors.capacita" class="error-message">{{ formErrors.capacita }}</div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="orario_apertura">Orario Apertura</label>
                <input 
                  id="orario_apertura"
                  v-model="formData.orario_apertura"
                  type="time"
                />
              </div>
              
              <div class="form-group">
                <label for="orario_chiusura">Orario Chiusura</label>
                <input 
                  id="orario_chiusura"
                  v-model="formData.orario_chiusura"
                  type="time"
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="data_inizio">Data Inizio Disponibilità</label>
                <input 
                  id="data_inizio"
                  v-model="formData.data_inizio"
                  type="date"
                />
              </div>
              
              <div class="form-group">
                <label for="data_fine">Data Fine Disponibilità</label>
                <input 
                  id="data_fine"
                  v-model="formData.data_fine"
                  type="date"
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
.laboratori-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-container {
  position: relative;
}

.search-input {
  padding: 0.5rem 1rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  width: 250px;
}

/* Table styles */
.table-container {
  overflow-x: auto;
}

.laboratori-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.laboratori-table th,
.laboratori-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.laboratori-table th {
  background-color: #f8fafc;
  font-weight: bold;
}

.laboratori-table tr:hover {
  background-color: #f1f5f9;
}

.description-cell {
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.actions-cell {
  white-space: nowrap;
}

/* Button styles */
.btn-primary {
  background-color: #2c3e50;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 0.5rem 1rem;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-primary:hover {
  background-color: #1a252f;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #2c3e50;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  padding: 0.5rem 1rem;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-secondary:hover {
  background-color: #cbd5e1;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  padding: 0.25rem;
  margin: 0 0.25rem;
}

.btn-view:hover {
  color: #3498db;
}

.btn-edit:hover {
  color: #f39c12;
}

.btn-prenotazioni:hover {
  color: #3498db;
}

/* Loading state */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
}

.loader {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #2c3e50;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Empty and error states */
.empty-container,
.error-container {
  text-align: center;
  padding: 3rem;
  background-color: #f8fafc;
  border-radius: 8px;
}

.error-container {
  color: #e74c3c;
}

/* Modal styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 100;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-body {
  padding: 1.5rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
}

.btn-close:hover {
  color: #334155;
}

/* Detail view */
.detail-item {
  margin-bottom: 1rem;
}

.detail-actions {
  margin-top: 1.5rem;
  display: flex;
  justify-content: flex-end;
}

.detail-item strong {
  display: inline-block;
  width: 180px;
  font-weight: bold;
}

/* Form styles */
.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: bold;
}

input, textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
}

.error-message {
  color: #e74c3c;
  font-size: 0.85rem;
  margin-top: 0.25rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .actions {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-input {
    width: 100%;
  }
  
  .form-row {
    flex-direction: column;
    gap: 1.5rem;
  }
}
</style>
