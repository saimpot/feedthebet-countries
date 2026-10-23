import type { Mock } from 'vitest'
import type { HttpClient } from '@/api/httpClient'
import { describe, expect, it, vi } from 'vitest'
import { fetchCountries } from '@/api/countriesApi'
import { fetchCurrentUser, loginUrl, logoutUrl } from '@/api/authApi'

type MockedGet = HttpClient['get'] & Mock

const stubGet = (body: unknown) =>
  vi.fn<() => Promise<unknown>>().mockResolvedValue(body) as unknown as MockedGet

describe('countriesApi', () => {
  it('unwraps data', async () => {
    const countries = [{ code: 'DE', name: 'Germany', flagUrl: 'x.svg', flagAlt: null }]
    const get = stubGet({ data: countries })
    await expect(fetchCountries({ get })).resolves.toEqual(countries)
    expect(get).toHaveBeenCalledWith('/api/countries')
  })
})

describe('authApi', () => {
  it('unwraps the current user', async () => {
    const user = { name: 'A', email: 'a@b.c', picture: 'p' }
    const get = stubGet({ data: user })
    await expect(fetchCurrentUser({ get })).resolves.toEqual(user)
    expect(get).toHaveBeenCalledWith('/api/me')
  })

  it('exposes login and logout urls', () => {
    expect(loginUrl).toBe('/login')
    expect(logoutUrl).toBe('/logout')
  })
})
