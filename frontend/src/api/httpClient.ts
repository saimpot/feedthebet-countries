import { ApiError, UnauthenticatedError } from '@/types/api'
import type { ApiErrorBody } from '@/types/api'

export type UnauthenticatedHandler = () => void

export interface HttpClient {
  get<T>(path: string): Promise<T>
}

const redirectToLogin: UnauthenticatedHandler = () => window.location.assign('/login')

async function readMessage(response: Response): Promise<string> {
  try {
    const body = (await response.json()) as ApiErrorBody
    if (body.message) return body.message
  } catch {
    // body was not JSON; fall through to the generic message
  }
  return `Request failed with status ${response.status}`
}

/**
 * Minimal fetch wrapper. The policy for an expired session (401) is injected,
 * so it is explicit and testable rather than buried in components.
 */
export function createHttpClient(
  onUnauthenticated: UnauthenticatedHandler = redirectToLogin,
  fetchImpl: typeof fetch = (...args) => fetch(...args),
): HttpClient {
  return {
    async get<T>(path: string): Promise<T> {
      const response = await fetchImpl(path, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })

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
