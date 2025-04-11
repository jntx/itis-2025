<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthModule } from '@/stores/authModule'

const authStore = useAuthModule()
const router = useRouter()

const credentials = reactive({
  username: '',
  password: ''
})

const error = ref('')
const isLoading = ref(false)

async function handleLogin() {
  if (!credentials.username || !credentials.password) {
    error.value = 'Username e password sono obbligatori'
    return
  }

  try {
    error.value = ''
    isLoading.value = true
    
    await authStore.login(credentials)
    
    // Redirect to home page after successful login
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login fallito. Riprova.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-container">
    <h1>Accedi</h1>
    
    <form @submit.prevent="handleLogin" class="login-form">
      <div class="form-group">
        <label for="username">Username</label>
        <input 
          id="username"
          v-model="credentials.username"
          type="text"
          placeholder="Inserisci username"
          autocomplete="username"
        />
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input 
          id="password"
          v-model="credentials.password"
          type="password"
          placeholder="Inserisci password"
          autocomplete="current-password"
        />
      </div>
      
      <div v-if="error" class="error-message">
        {{ error }}
      </div>
      
      <button type="submit" :disabled="isLoading" class="login-button">
        {{ isLoading ? 'Accesso in corso...' : 'Accedi' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 0 auto;
  padding: 2rem;
}

h1 {
  text-align: center;
  margin-bottom: 2rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

label {
  font-weight: bold;
}

input {
  padding: 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
}

.error-message {
  color: #e74c3c;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.login-button {
  background-color: #2c3e50;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 0.75rem;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.3s;
}

.login-button:hover:not(:disabled) {
  background-color: #1a252f;
}

.login-button:disabled {
  background-color: #95a5a6;
  cursor: not-allowed;
}
</style>
