import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fetchCurrentUser } from '@/api/authApi'
import { useAuth } from '@/composables/useAuth'
import { ApiError, UnauthenticatedError } from '@/types/api'

vi.mock('@/api/authApi')

const ann = { name: 'Ann', email: 'a@b.c', picture: 'p.png' }

describe('useAuth', () => {
  beforeEach(() => {
    vi.mocked(fetchCurrentUser).mockReset()
  })
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('reports authenticated and exposes the user', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(ann)
    const { user, isAuthenticated, loadUser } = useAuth()
    expect(isAuthenticated.value).toBe(false)

    await expect(loadUser()).resolves.toBe('authenticated')
    expect(user.value).toEqual(ann)
    expect(isAuthenticated.value).toBe(true)
  })

  it('reports unauthenticated on a 401', async () => {
    vi.mocked(fetchCurrentUser).mockRejectedValue(new UnauthenticatedError())
    const { user, loadUser } = useAuth()

    await expect(loadUser()).resolves.toBe('unauthenticated')
    expect(user.value).toBeNull()
  })

  it('rethrows other failures', async () => {
    const failure = new ApiError(503, 'down')
    vi.mocked(fetchCurrentUser).mockRejectedValue(failure)
    const { user, loadUser } = useAuth()

    await expect(loadUser()).rejects.toBe(failure)
    expect(user.value).toBeNull()
  })

  it('logout navigates to the logout url', () => {
    const assign = vi.fn<(url: string) => void>()
    vi.stubGlobal('location', { assign })
    useAuth().logout()
    expect(assign).toHaveBeenCalledExactlyOnceWith('/logout')
  })
})
