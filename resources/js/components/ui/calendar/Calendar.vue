<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { 
  type DateValue, 
  startOfWeek, 
  endOfWeek, 
  startOfMonth, 
  endOfMonth, 
  isSameMonth, 
  isSameDay, 
  isToday,
  CalendarDate,
  getLocalTimeZone
} from '@internationalized/date'

interface Props {
  modelValue?: DateValue
  locale?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  locale: 'fr-FR'
})

const emits = defineEmits<{
  'update:modelValue': [value: DateValue | undefined]
}>()

// État du calendrier
const currentMonth = ref<DateValue>(props.modelValue || new CalendarDate(new Date().getFullYear(), new Date().getMonth() + 1, 1))

// Calculer les jours de la semaine
const weekDays = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di']

// Générer les dates du calendrier
const calendarDates = computed(() => {
  const monthStart = startOfMonth(currentMonth.value)
  const monthEnd = endOfMonth(currentMonth.value)
  const calendarStart = startOfWeek(monthStart, 'fr-FR')
  const calendarEnd = endOfWeek(monthEnd, 'fr-FR')
  
  const dates = []
  let current = calendarStart
  
  while (current.compare(calendarEnd) <= 0) {
    dates.push(current)
    current = current.add({ days: 1 })
  }
  
  // Grouper par semaines
  const weeks = []
  for (let i = 0; i < dates.length; i += 7) {
    weeks.push(dates.slice(i, i + 7))
  }
  
  return weeks
})

// Navigation
const previousMonth = () => {
  currentMonth.value = currentMonth.value.subtract({ months: 1 })
}

const nextMonth = () => {
  currentMonth.value = currentMonth.value.add({ months: 1 })
}

// Sélection de date
const selectDate = (date: DateValue) => {
  emits('update:modelValue', date)
}

// Formatage du mois/année
const monthYearLabel = computed(() => {
  const date = new Date(currentMonth.value.year, currentMonth.value.month - 1, 1)
  return date.toLocaleDateString(props.locale, { 
    month: 'long', 
    year: 'numeric' 
  })
})

// Classes CSS pour les dates
const getDateClasses = (date: DateValue) => {
  const baseClasses = "inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9 p-0 font-normal"
  
  const classes = [baseClasses]
  
  // Date sélectionnée
  if (props.modelValue && isSameDay(date, props.modelValue)) {
    classes.push("bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground")
  }
  
  // Aujourd'hui
  if (isToday(date, getLocalTimeZone())) {
    classes.push("bg-accent text-accent-foreground")
  }
  
  // Date en dehors du mois actuel
  if (!isSameMonth(date, currentMonth.value)) {
    classes.push("text-muted-foreground opacity-50")
  }
  
  return classes.join(" ")
}
</script>

<template>
  <div :class="cn('p-3', props.class)">
    <!-- Header avec navigation -->
    <div class="flex w-full items-center justify-between mb-4">
      <button
        @click="previousMonth"
        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100"
      >
        <ChevronLeft class="h-4 w-4" />
      </button>
      
      <div class="flex items-center text-sm font-medium">
        {{ monthYearLabel }}
      </div>
      
      <button
        @click="nextMonth"
        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100"
      >
        <ChevronRight class="h-4 w-4" />
      </button>
    </div>

    <!-- Grille du calendrier -->
    <div class="w-full border-collapse space-y-1">
      <!-- En-têtes des jours -->
      <div class="flex">
        <div
          v-for="day in weekDays"
          :key="day"
          class="text-muted-foreground rounded-md w-9 font-normal text-[0.8rem] text-center"
        >
          {{ day }}
        </div>
      </div>

      <!-- Corps du calendrier -->
      <div class="flex flex-col space-y-1 mt-2">
        <div
          v-for="(week, weekIndex) in calendarDates"
          :key="`week-${weekIndex}`"
          class="flex w-full"
        >
          <div
            v-for="date in week"
            :key="date.toString()"
            class="h-9 w-9 text-center text-sm p-0 relative"
          >
            <button
              @click="selectDate(date)"
              :class="getDateClasses(date)"
            >
              {{ date.day }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template> 