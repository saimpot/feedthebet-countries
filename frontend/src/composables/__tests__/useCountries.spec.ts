import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useCountries } from '@/composables/useCountries'
import { fetchCountries } from '@/api/countriesApi'
import type { Country } from '@/types/country'

vi.mock('@/api/countriesApi')

const make = (code: string, name: string): Country => ({
  code,
  name,
  flagUrl: `${code}.svg`,
  flagAlt: null,
})
const data = [make('CI', "Côte d'Ivoire"), make('DE', 'Germany'), make('FR', 'France')]

describe('useCountries', () => {
  beforeEach(() => {
    vi.mocked(fetchCountries).mockReset()
  })

  it('goes idle -> loading -> success', async () => {
    vi.mocked(fetchCountries).mockResolvedValue(data)
    const { state, load, total } = useCountries()
    expect(state.value.status).toBe('idle')

    const pending = load()
    expect(state.value.status).toBe('loading')
    await pending

    expect(state.value).toEqual({ status: 'success', countries: data })
    expect(total.value).toBe(3)
  })

  it('goes to error and recovers on retry', async () => {
    vi.mocked(fetchCountries).mockRejectedValueOnce(new Error('down'))
    const { state, load, retry } = useCountries()
    await load()
    expect(state.value).toEqual({ status: 'error', message: 'down' })

    vi.mocked(fetchCountries).mockResolvedValueOnce(data)
    await retry()
    expect(state.value.status).toBe('success')
  })

  it('filters case- and diacritic-insensitively', async () => {
    vi.mocked(fetchCountries).mockResolvedValue(data)
    const { search, filtered, load } = useCountries()
    await load()

    expect(filtered.value).toHaveLength(3)
    search.value = 'cote'
    expect(filtered.value.map((c) => c.code)).toEqual(['CI'])
    search.value = 'GERM'
    expect(filtered.value.map((c) => c.code)).toEqual(['DE'])
    search.value = 'zzz'
    expect(filtered.value).toEqual([])
  })

  it('uses a generic message when the rejection is not an Error', async () => {
    vi.mocked(fetchCountries).mockRejectedValueOnce('nope')
    const { state, load } = useCountries()
    await load()
    expect(state.value).toEqual({ status: 'error', message: 'Something went wrong.' })
  })

  it('treats a whitespace-only search as no filter', async () => {
    vi.mocked(fetchCountries).mockResolvedValue(data)
    const { search, filtered, load } = useCountries()
    await load()
    search.value = '   '
    expect(filtered.value).toHaveLength(3)
  })

  it('ignores load calls while a load is in flight', async () => {
    vi.mocked(fetchCountries).mockResolvedValue(data)
    const { load } = useCountries()
    await Promise.all([load(), load()])
    expect(fetchCountries).toHaveBeenCalledTimes(1)
  })
})
