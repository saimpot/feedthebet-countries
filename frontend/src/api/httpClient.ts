import { loginUrl } from '@/api/routes'
import { ApiError, UnauthenticatedError } from '@/types/api'

export type UnauthenticatedHandler = () => void

export interface HttpClient {
  get<T>(path: string): Promise<T>
}

/**
 * Every /login request starts a new server session with a fresh OAuth state, so
 * concurrent redirects (one per failing request) make the Auth0 callback fail
 * with "Invalid state". Redirect at most once.
 */
export function createLoginRedirect(
  redirect: (url: string) => void = (url) => window.location.assign(url),
): UnauthenticatedHandler {
  let redirecting = false
  return () => {
    if (redirecting) return
    redirecting = true
    redirect(loginUrl)
  }
}

async function readMessage(response: Response): Promise<string> {
  try {
    const body: unknown = await response.json()
    if (
      typeof body === 'object' &&
      body !== null &&
      'message' in body &&
      typeof body.message === 'string' &&
      body.message
    ) {
      return body.message
    }
  } catch {
    // not JSON; use the generic message
  }
  return `Request failed with status ${response.status}`
}

/** The 401 policy is injected so it is explicit and testable. */
export function createHttpClient(
  onUnauthenticated: UnauthenticatedHandler = createLoginRedirect(),
  fetchImpl: typeof fetch = (...args) => fetch(...args),
): HttpClient {
  return {
    async get<T>(path: string): Promise<T> {
      let response: Response
      try {
        response = await fetchImpl(path, {
          method: 'GET',
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
      } catch {
        throw new ApiError(0, 'Network error. Check your connection and try again.')
      }

      if (response.status === 401) {
        onUnauthenticated()
        throw new UnauthenticatedError(await readMessage(response))
      }
      if (!response.ok) {
        throw new ApiError(response.status, await readMessage(response))
      }
      return (await response.json()) as T
    },
  }
}

export const httpClient = createHttpClient()
