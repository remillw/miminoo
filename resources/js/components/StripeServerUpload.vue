<template>
  <div class="stripe-server-upload">
    <div class="upload-area" 
         :class="{ 'drag-over': isDragOver, 'uploading': isUploading }"
         @drop="handleDrop"
         @dragover.prevent="isDragOver = true"
         @dragleave="isDragOver = false"
         @click="triggerFileInput">
      
      <!-- Zone de drop -->
      <div v-if="!isUploading" class="upload-prompt">
        <div class="upload-icon">
          🆔
        </div>
        <h3>Glissez vos documents d'identité ici</h3>
        <p>ou <strong>cliquez pour sélectionner</strong></p>
        <p class="upload-info">
          Formats acceptés: JPG, PNG, PDF • Max 10MB par fichier
        </p>
        <div class="server-upload-badge">
          <span class="badge-text">🔒 Upload sécurisé via serveur</span>
        </div>
      </div>
      
      <!-- Progrès d'upload -->
      <div v-if="isUploading" class="upload-progress">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
        </div>
        <p>Upload et liaison en cours... {{ uploadProgress }}%</p>
      </div>
    </div>

    <!-- Input file caché -->
    <input 
      ref="fileInput"
      type="file" 
      multiple 
      accept="image/jpeg,image/png,application/pdf"
      @change="handleFileSelect"
      style="display: none"
    />

    <!-- Liste des fichiers sélectionnés -->
    <div v-if="selectedFiles.length > 0 && !isUploading" class="selected-files">
      <h4>Fichiers sélectionnés:</h4>
      <div class="file-list">
        <div v-for="(file, index) in selectedFiles" :key="index" class="file-item">
          <span class="file-name">{{ file.name }}</span>
          <span class="file-size">({{ formatFileSize(file.size) }})</span>
          <button @click="removeFile(index)" class="remove-btn">✕</button>
        </div>
      </div>
      
      <!-- Erreurs de validation -->
      <div v-if="validationErrors.length > 0" class="validation-errors">
        <h5>❌ Erreurs de validation:</h5>
        <div v-for="(error, index) in validationErrors" :key="index" class="error-item">
          <strong>{{ error.file }}:</strong>
          <ul>
            <li v-for="err in error.errors" :key="err">{{ err }}</li>
          </ul>
        </div>
      </div>
      
      <div class="upload-actions">
        <button 
          @click="startUpload" 
          :disabled="selectedFiles.length === 0 || validationErrors.length > 0"
          class="upload-btn primary">
          🚀 Uploader et lier au compte Stripe ({{ selectedFiles.length }} fichier{{ selectedFiles.length > 1 ? 's' : '' }})
        </button>
        <button @click="clearFiles" class="upload-btn secondary">
          Effacer
        </button>
      </div>
    </div>

    <!-- Fichiers uploadés avec succès -->
    <div v-if="uploadedFiles.length > 0" class="upload-results">
      <h4>✅ Documents envoyés et liés à Stripe avec succès:</h4>
      <div class="success-message">
        <p>🎉 Vos documents ont été uploadés avec la clé secrète et automatiquement liés à votre compte Connect pour résoudre les requirements!</p>
      </div>
      <div class="file-list">
        <div v-for="file in uploadedFiles" :key="file.stripe_file_id" class="file-item success">
          <span class="file-name">{{ file.filename || 'Document' }}</span>
          <span class="file-size">({{ formatFileSize(file.size) }})</span>
          <span class="stripe-id">Stripe ID: {{ file.stripe_file_id }}</span>
          <span v-if="file.linked_to_account" class="linked-badge">✅ Lié au compte</span>
        </div>
      </div>
    </div>

    <!-- Erreurs -->
    <div v-if="errors.length > 0" class="upload-errors">
      <h4>❌ Erreurs d'upload:</h4>
      <div class="error-list">
        <div v-for="(error, index) in errors" :key="index" class="error-item">
          <strong>{{ error.file || 'Erreur générale' }}:</strong> 
          {{ error.error || error.errors?.join(', ') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useStripeServerUpload } from '@/composables/useStripeServerUpload'

// Props
const props = defineProps({
  purpose: {
    type: String,
    default: 'identity_document'
  }
})

// Events
const emit = defineEmits(['upload-complete', 'upload-error', 'files-selected'])

// Composable
const {
  isUploading,
  uploadProgress,
  uploadedFiles,
  errors,
  uploadFiles,
  validateFiles,
  reset
} = useStripeServerUpload()

// State local
const fileInput = ref(null)
const selectedFiles = ref([])
const isDragOver = ref(false)

// Computed
const validationErrors = computed(() => {
  return selectedFiles.value.length > 0 ? validateFiles(selectedFiles.value) : []
})

// Watch les erreurs de validation
watch(validationErrors, (newErrors) => {
  if (newErrors.length > 0) {
    console.warn('Validation errors:', newErrors)
  }
})

// Methods
const triggerFileInput = () => {
  if (!isUploading.value) {
    fileInput.value?.click()
  }
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  addFiles(files)
  // Reset input pour permettre de sélectionner le même fichier
  event.target.value = ''
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  if (isUploading.value) return
  
  const files = Array.from(event.dataTransfer.files)
  addFiles(files)
}

const addFiles = (files) => {
  // Vérifier la limite de fichiers
  const totalFiles = selectedFiles.value.length + files.length
  if (totalFiles > 5) {
    alert('Maximum 5 fichiers autorisés')
    return
  }
  
  selectedFiles.value.push(...files)
  emit('files-selected', selectedFiles.value)
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
  emit('files-selected', selectedFiles.value)
}

const clearFiles = () => {
  selectedFiles.value = []
  reset()
  emit('files-selected', [])
}

const startUpload = async () => {
  if (selectedFiles.value.length === 0) {
    console.error('❌ Aucun fichier sélectionné')
    return
  }
  
  if (validationErrors.value.length > 0) {
    console.error('❌ Erreurs de validation:', validationErrors.value)
    emit('upload-error', new Error('Erreurs de validation: ' + validationErrors.value.map(e => `${e.file}: ${e.errors.join(', ')}`).join('; ')))
    return
  }
  
  try {
    console.log('🚀 Démarrage upload avec', selectedFiles.value.length, 'fichiers')
    const result = await uploadFiles(selectedFiles.value, props.purpose)
    
    // Émettre l'événement de succès
    emit('upload-complete', {
      uploadedFiles: result.uploaded_files,
      errors: result.errors,
      message: result.message
    })
    
    // Vider la sélection en cas de succès complet
    if (result.success && (!result.errors || result.errors.length === 0)) {
      selectedFiles.value = []
    }
    
  } catch (error) {
    console.error('❌ Upload failed:', error)
    emit('upload-error', error)
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<style scoped>
.stripe-server-upload {
  max-width: 600px;
  margin: 0 auto;
}

.upload-area {
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 40px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
}

.upload-area:hover {
  border-color: #007bff;
  background: #f0f8ff;
}

.upload-area.drag-over {
  border-color: #007bff;
  background: #e3f2fd;
  transform: scale(1.02);
}

.upload-area.uploading {
  cursor: not-allowed;
  opacity: 0.7;
}

.upload-prompt .upload-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.upload-prompt h3 {
  margin: 0 0 8px 0;
  color: #333;
}

.upload-prompt p {
  margin: 4px 0;
  color: #666;
}

.upload-info {
  font-size: 12px;
  color: #999;
}

.server-upload-badge {
  margin-top: 12px;
  display: inline-block;
}

.badge-text {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.upload-progress {
  padding: 20px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #eee;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 12px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #28a745, #20c997);
  transition: width 0.3s ease;
}

.selected-files, .upload-results {
  margin-top: 20px;
  padding: 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
}

.validation-errors {
  margin: 12px 0;
  padding: 12px;
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 6px;
}

.validation-errors h5 {
  margin: 0 0 8px 0;
  color: #856404;
}

.success-message {
  background: #f0f8f0;
  border: 1px solid #28a745;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 16px;
}

.success-message p {
  margin: 0;
  color: #155724;
  font-size: 14px;
}

.file-list {
  margin: 12px 0;
}

.file-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border: 1px solid #eee;
  border-radius: 4px;
  margin-bottom: 8px;
  background: #f9f9f9;
}

.file-item.success {
  background: #f0f8f0;
  border-color: #28a745;
}

.file-name {
  flex: 1;
  font-weight: 500;
}

.file-size {
  color: #666;
  font-size: 12px;
}

.stripe-id {
  color: #007bff;
  font-size: 10px;
  font-family: monospace;
}

.linked-badge {
  background: #28a745;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
}

.remove-btn {
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  cursor: pointer;
  font-size: 12px;
}

.upload-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.upload-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.upload-btn.primary {
  background: #28a745;
  color: white;
}

.upload-btn.primary:hover:not(:disabled) {
  background: #218838;
}

.upload-btn.primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.upload-btn.secondary {
  background: #6c757d;
  color: white;
}

.upload-btn.secondary:hover {
  background: #545b62;
}

.upload-errors {
  margin-top: 20px;
  padding: 16px;
  border: 1px solid #dc3545;
  border-radius: 8px;
  background: #f8d7da;
}

.error-item {
  margin-bottom: 8px;
  color: #721c24;
}

.error-item:last-child {
  margin-bottom: 0;
}

.error-item ul {
  margin: 4px 0 0 20px;
}
</style>