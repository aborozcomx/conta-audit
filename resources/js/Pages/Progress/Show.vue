<!-- resources/js/Pages/CompanyVariables/Components/ProgressTracker.vue -->
<template>
    <AuthenticatedLayout>
        <Head title="Progreso " />

        <div class="container">

        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Procesando {{ currentProgress?.type }}</h2>
            <span
              class="px-3 py-1 text-sm font-medium rounded-full capitalize"
              :class="statusClasses[currentProgress?.status]"
            >
              {{ statusLabels[currentProgress?.status] }}
            </span>
          </div>

          <!-- Progress Bar -->
          <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
              <span>{{ currentProgress?.message || 'Cargando...' }}</span>
              <span>{{ currentProgress?.percentage || 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div
                class="h-3 rounded-full transition-all duration-500 ease-out"
                :class="progressBarClass"
                :style="{ width: `${currentProgress?.percentage || 0}%` }"
              ></div>
            </div>
          </div>

          <!-- Estadísticas detalladas -->
          <div v-if="currentProgress?.metadata" class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
            <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-100">
              <div class="text-2xl font-bold text-blue-600">
                {{ currentProgress.metadata.empleados_procesados }}/{{ currentProgress.metadata.total_empleados }}
              </div>
              <div class="text-gray-600">{{ currentProgress?.type }}</div>
              <div class="text-xs text-blue-500 mt-1">
                {{ Math.round((currentProgress.metadata.empleados_procesados / currentProgress.metadata.total_empleados) * 100)  || 0}}% completado
              </div>
            </div>

            <div class="text-center p-3 bg-green-50 rounded-lg border border-green-100">
              <div class="text-2xl font-bold text-green-600">
                {{ currentProgress.metadata.chunk_actual }}/{{ currentProgress.metadata.total_chunks }}
              </div>
              <div class="text-gray-600">Lotes</div>
              <div class="text-xs text-green-500 mt-1">
                {{ Math.round((currentProgress.metadata.chunk_actual / currentProgress.metadata.total_chunks) * 100) || 0 }}% completado
              </div>
            </div>

            <div class="text-center p-3 bg-purple-50 rounded-lg border border-purple-100">
              <div class="text-2xl font-bold text-purple-600">{{ currentProgress.percentage }}%</div>
              <div class="text-gray-600">Progreso Total</div>
              <div class="text-xs text-purple-500 mt-1">General</div>
            </div>

            <div class="text-center p-3 bg-gray-50 rounded-lg border border-gray-100">
              <div class="text-lg font-semibold text-gray-700">{{ formatTime(currentProgress.updated_at) }}</div>
              <div class="text-gray-600">Última actualización</div>
              <div class="text-xs text-gray-500 mt-1">Hace {{ timeAgo(currentProgress.updated_at) }}</div>
            </div>
          </div>

          <!-- Estimación de tiempo restante -->
          <div v-if="currentProgress?.metadata && currentProgress?.status === 'processing'" class="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex items-center text-sm text-yellow-800">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
              </svg>
              <span>Tiempo estimado restante: {{ calculateRemainingTime(currentProgress) }}</span>
            </div>
          </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { toast } from 'vue-sonner'

// Props
const props = defineProps({
  progressId: {
    type: [String, Number],
    required: true
  }
})

// Emits
const emit = defineEmits(['job-completed'])

// Estado reactivo
const currentProgress = ref(null)
const progressInterval = ref(null)
const jobStartTime = ref(null)
const lastProcessedCount = ref(0)
const lastUpdateTime = ref(null)
const processingSpeed = ref('Calculando...')

// Constantes para estilos
const statusLabels = {
  processing: 'Procesando',
  completed: 'Completado',
  failed: 'Error'
}

const statusClasses = {
  processing: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
  completed: 'bg-green-100 text-green-800 border border-green-200',
  failed: 'bg-red-100 text-red-800 border border-red-200'
}

// Computed
const progressBarClass = computed(() => {
  if (!currentProgress.value) return 'bg-gray-400'

  return {
    processing: 'bg-blue-500',
    completed: 'bg-green-500',
    failed: 'bg-red-500'
  }[currentProgress.value.status] || 'bg-gray-400'
})

// Métodos
const startProgressTracking = () => {
  jobStartTime.value = Date.now()
  lastProcessedCount.value = 0
  lastUpdateTime.value = Date.now()
  processingSpeed.value = 'Calculando...'

  progressInterval.value = setInterval(async () => {
    try {
      const { data } = await axios.get(`/company-variables/progress/${props.progressId}/bar`)
      currentProgress.value = data

      // Calcular velocidad de procesamiento
      if (data.metadata && data.status === 'processing') {
        calculateProcessingSpeed(data.metadata.empleados_procesados)
      }

      // Si el job terminó, limpiar el intervalo
      if (['completed', 'failed'].includes(data.status)) {
        toast.success(`Se ha terminado la importación de ${currentProgress?.value.type}`);
        clearInterval(progressInterval.value)
        progressInterval.value = null
        emit('job-completed')
      }
    } catch (error) {
      console.error('Error al obtener progreso:', error)
      clearInterval(progressInterval.value)
      progressInterval.value = null
    }
  }, 3000)
}

const calculateProcessingSpeed = (currentProcessed) => {
  const now = Date.now()
  const timeDiff = (now - lastUpdateTime.value) / 1000 // segundos

  // Validaciones para evitar NaN
  if (timeDiff < 0.1) return // Evitar cálculos con intervalos muy pequeños

  const processedDiff = currentProcessed - lastProcessedCount.value

  // Solo calcular si hay datos válidos
  if (timeDiff > 0.5 && processedDiff >= 0) {
    const speed = processedDiff / timeDiff

    // Solo actualizar si el cálculo es válido
    if (!isNaN(speed) && isFinite(speed)) {
      processingSpeed.value = speed > 0 ? speed.toFixed(2) : '0.00'
    } else {
      processingSpeed.value = 'Calculando...'
    }

    lastProcessedCount.value = currentProcessed
    lastUpdateTime.value = now
  }
}

const calculateRemainingTime = (progress) => {
  if (!progress.metadata || !jobStartTime.value) return 'Calculando...'

  const empleadosProcesados = progress.metadata.empleados_procesados
  const totalEmpleados = progress.metadata.total_empleados

  // Validaciones para evitar divisiones por cero
  if (empleadosProcesados === 0 || totalEmpleados === 0) {
    return 'Calculando...'
  }

  const tiempoTranscurrido = (Date.now() - jobStartTime.value) / 1000 // segundos

  // Evitar división por cero
  if (tiempoTranscurrido < 1) {
    return 'Calculando...'
  }

  const empleadosPorSegundo = empleadosProcesados / tiempoTranscurrido

  // Si no hay velocidad de procesamiento válida
  if (empleadosPorSegundo <= 0 || !isFinite(empleadosPorSegundo)) {
    return 'Calculando...'
  }

  const empleadosRestantes = totalEmpleados - empleadosProcesados

  // Si ya se completó
  if (empleadosRestantes <= 0) {
    return 'Completado'
  }

  const segundosRestantes = empleadosRestantes / empleadosPorSegundo

  // Validar que el tiempo restante sea válido
  if (segundosRestantes <= 0 || !isFinite(segundosRestantes)) {
    return 'Calculando...'
  }

  if (segundosRestantes < 60) {
    return `${Math.round(segundosRestantes)} segundos`
  } else if (segundosRestantes < 3600) {
    const minutos = Math.round(segundosRestantes / 60)
    return `${minutos} minuto${minutos > 1 ? 's' : ''}`
  } else {
    const horas = Math.round(segundosRestantes / 3600)
    return `${horas} hora${horas > 1 ? 's' : ''}`
  }
}

const closeProgress = () => {
  if (progressInterval.value) {
    clearInterval(progressInterval.value)
    progressInterval.value = null
  }
  emit('job-completed')
}

// Helpers
const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('es-MX', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const timeAgo = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffSecs = Math.floor(diffMs / 1000)
  const diffMins = Math.floor(diffSecs / 60)

  if (diffSecs < 10) return 'ahora mismo'
  if (diffSecs < 60) return `hace ${diffSecs} segundos`
  if (diffMins < 60) return `hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`

  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`

  const diffDays = Math.floor(diffHours / 24)
  return `hace ${diffDays} día${diffDays > 1 ? 's' : ''}`
}

// Lifecycle
onMounted(() => {
  startProgressTracking()
})

onUnmounted(() => {
  if (progressInterval.value) {
    clearInterval(progressInterval.value)
  }
})
</script>
