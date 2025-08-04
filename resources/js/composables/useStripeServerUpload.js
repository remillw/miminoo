/**
 * 🚀 Composable pour upload de fichiers vers Stripe via le serveur
 * 
 * Avantages:
 * - Upload avec la clé secrète côté serveur (plus sécurisé)
 * - Liaison automatique au compte Connect
 * - Résout immédiatement les requirements Stripe
 */

import { ref } from 'vue'

export function useStripeServerUpload() {
    const isUploading = ref(false)
    const uploadProgress = ref(0)
    const uploadedFiles = ref([])
    const errors = ref([])

    /**
     * Upload des fichiers via le serveur (avec clé secrète)
     */
    const uploadFiles = async (files, purpose = 'identity_document', onProgress = null) => {
        // Reset state
        isUploading.value = true
        uploadProgress.value = 0
        uploadedFiles.value = []
        errors.value = []

        try {
            const formData = new FormData()
            
            // Ajouter les fichiers
            Array.from(files).forEach((file) => {
                formData.append('files[]', file)
            })
            
            formData.append('purpose', purpose)

            const response = await fetch('/stripe/upload-files', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })

            const result = await response.json()

            if (!response.ok) {
                throw new Error(result.error || 'Upload failed')
            }

            uploadedFiles.value = result.uploaded_files || []
            errors.value = result.errors || []
            uploadProgress.value = 100

            console.log('✅ Server upload completed:', {
                uploaded: result.total_uploaded,
                errors: result.total_errors,
                files: result.uploaded_files
            })

            return {
                success: result.success,
                uploaded_files: result.uploaded_files,
                errors: result.errors,
                message: result.message
            }

        } catch (error) {
            console.error('❌ Server upload failed:', error)
            errors.value = [{ error: error.message }]
            throw error
        } finally {
            isUploading.value = false
        }
    }

    /**
     * Valider un fichier avant upload
     */
    const validateFile = (file) => {
        const errors = []
        
        // Types autorisés
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf']
        if (!allowedTypes.includes(file.type)) {
            errors.push(`Type de fichier non autorisé: ${file.type}`)
        }
        
        // Extensions autorisées
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf']
        const extension = file.name.split('.').pop().toLowerCase()
        if (!allowedExtensions.includes(extension)) {
            errors.push(`Extension non autorisée: .${extension}`)
        }
        
        // Taille max 10MB
        const maxSize = 10 * 1024 * 1024 // 10MB
        if (file.size > maxSize) {
            errors.push(`Fichier trop volumineux: ${(file.size / 1024 / 1024).toFixed(1)}MB (max 10MB)`)
        }
        
        // Taille min 1KB
        if (file.size < 1024) {
            errors.push('Fichier trop petit (minimum 1KB)')
        }
        
        return errors
    }

    /**
     * Valider tous les fichiers
     */
    const validateFiles = (files) => {
        const fileArray = Array.from(files)
        const validationErrors = []
        
        // Limite de 5 fichiers
        if (fileArray.length > 5) {
            validationErrors.push({
                file: 'Général',
                errors: ['Maximum 5 fichiers autorisés']
            })
        }
        
        // Valider chaque fichier
        fileArray.forEach((file) => {
            const fileErrors = validateFile(file)
            if (fileErrors.length > 0) {
                validationErrors.push({
                    file: file.name,
                    errors: fileErrors
                })
            }
        })
        
        return validationErrors
    }

    /**
     * Reset de l'état
     */
    const reset = () => {
        isUploading.value = false
        uploadProgress.value = 0
        uploadedFiles.value = []
        errors.value = []
    }

    return {
        // State
        isUploading,
        uploadProgress,
        uploadedFiles,
        errors,
        
        // Methods
        uploadFiles,
        validateFile,
        validateFiles,
        reset
    }
}