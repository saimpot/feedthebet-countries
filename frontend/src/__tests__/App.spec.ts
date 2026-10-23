import type { User } from '@/types/user'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import App from '@/App.vue'
import { fetchCountries } from '@/api/countriesApi'
import { fetchCurrentUser } from '@/api/authApi'
import { UnauthenticatedError } from '@/types/api'

vi.mock('@/api/countriesApi')
vi.mock('@/api/authApi', () => ({
  fetchCurrentUser: vi.fn<() => Promise<User>>(),
  loginUrl: '/login',
  logoutUrl: '/logout',
}))

const countries = [
  { code: 'DE', name: 'Germany', flagUrl: 'de.svg', flagAlt: null },
  { code: 'FR', name: 'France', flagUrl: 'fr.svg', flagAlt: null },
]
const flags = (w: ReturnType<typeof mount>) => w.findAll('ul > li img')

describe('App', () => {
  beforeEach(() => {
    vi.mocked(fetchCurrentUser).mockResolvedValue({
      name: 'Ann',
      email: 'a@b.c',
      picture: 'p.png',
    })
  })

  it('shows loading, then the grid, count, search and empty state', async () => {
    vi.mocked(fetchCountries).mockResolvedValue(countries)
    const wrapper = mount(App)
    expect(wrapper.find('[aria-busy="true"]').exists()).toBe(true)

    await flushPromises()
    expect(flags(wrapper)).toHaveLength(2)
    expect(wrapper.text()).toContain('Showing 2 of 2 countries')
    expect(wrapper.text()).toContain('Ann')

    await wrapper.get('input').setValue('fra')
    expect(flags(wrapper)).toHaveLength(1)
    expect(wrapper.text()).toContain('Showing 1 of 2 countries')

    await wrapper.get('input').setValue('xyz')
    expect(wrapper.text()).toContain('No countries match')
  })

  it('shows the error state and retries', async () => {
    vi.mocked(fetchCountries).mockRejectedValueOnce(
      new Error('Country data is temporarily unavailable.'),
    )
    const wrapper = mount(App)
    await flushPromises()
    expect(wrapper.get('[role="alert"]').text()).toContain('temporarily unavailable')

    vi.mocked(fetchCountries).mockResolvedValueOnce(countries)
    await wrapper.get('[role="alert"] button').trigger('click')
    await flushPromises()
    expect(flags(wrapper)).toHaveLength(2)
  })

  it('does not request countries when the user is not signed in', async () => {
    vi.mocked(fetchCountries).mockClear()
    vi.mocked(fetchCurrentUser).mockRejectedValueOnce(new UnauthenticatedError('Unauthenticated.'))
    mount(App)
    await flushPromises()
    expect(fetchCountries).not.toHaveBeenCalled()
  })
})
