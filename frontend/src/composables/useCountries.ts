import { computed, ref } from 'vue'
import { fetchCountries } from '@/api/countriesApi'
import { errorMessage } from '@/types/api'
import type { Country } from '@/types/country'

export type CountriesState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'success'; countries: Country[] }
  | { status: 'error'; message: string }

const normalize = (value: string): string =>
  value
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
    .trim()

export function useCountries() {
  const state = ref<CountriesState>({ status: 'idle' })
  const search = ref('')

  async function load(): Promise<void> {
    if (state.value.status === 'loading') return
    state.value = { status: 'loading' }
    try {
      state.value = { status: 'success', countries: await fetchCountries() }
    } catch (error) {
      state.value = {
        status: 'error',
        message: errorMessage(error),
      }
    }
  }

  const countries = computed<Country[]>(() =>
    state.value.status === 'success' ? state.value.countries : [],
  )

  const filtered = computed<Country[]>(() => {
    const query = normalize(search.value)
    if (!query) return countries.value
    return countries.value.filter((country) => normalize(country.name).includes(query))
  })

  return {
    state,
    search,
    filtered,
    countries,
    total: computed(() => countries.value.length),
    load,
    retry: load,
  }
}
