import { describe, expect, it, vi } from 'vitest'
import { createHttpClient, createLoginRedirect } from '@/api/httpClient'
import { ApiError, UnauthenticatedError } from '@/types/api'

// A Response body can be read only once, so each call must get a fresh Response.
const respond = (status: number, body: unknown) =>
  vi
    .fn<typeof fetch>()
    .mockImplementation(() => Promise.resolve(new Response(JSON.stringify(body), { status })))

describe('httpClient', () => {
  it('parses a successful response and sends the expected options', async () => {
    const fetchImpl = respond(200, { data: [1] })
    const client = createHttpClient(vi.fn<() => void>(), fetchImpl)

    await expect(client.get('/api/x')).resolves.toEqual({ data: [1] })
    expect(fetchImpl).toHaveBeenCalledWith(
      '/api/x',
      expect.objectContaining({
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      }),
    )
  })

  it('throws UnauthenticatedError and calls the handler on 401', async () => {
    const handler = vi.fn<() => void>()
    const client = createHttpClient(handler, respond(401, { message: 'Unauthenticated.' }))

    await expect(client.get('/api/me')).rejects.toBeInstanceOf(UnauthenticatedError)
    expect(handler).toHaveBeenCalledOnce()
  })

  it('throws ApiError with the server message on 503', async () => {
    const handler = vi.fn<() => void>()
    const client = createHttpClient(
      handler,
      respond(503, { message: 'Country data is temporarily unavailable.' }),
    )

    const error = await client.get('/api/countries').catch((e: unknown) => e)
    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({
      status: 503,
      message: 'Country data is temporarily unavailable.',
    })
    expect(handler).not.toHaveBeenCalled()
  })

  it('falls back to a generic message for non-JSON errors', async () => {
    const fetchImpl = vi
      .fn<typeof fetch>()
      .mockImplementation(() => Promise.resolve(new Response('boom', { status: 500 })))
    const error = await createHttpClient(vi.fn<() => void>(), fetchImpl)
      .get('/x')
      .catch((e: unknown) => e)
    expect(error).toMatchObject({ status: 500, message: 'Request failed with status 500' })
  })

  it('ignores a non-string message in the error body', async () => {
    const error = await createHttpClient(vi.fn<() => void>(), respond(500, { message: 42 }))
      .get('/x')
      .catch((e: unknown) => e)
    expect(error).toMatchObject({ message: 'Request failed with status 500' })
  })

  it('maps a network failure to a friendly ApiError', async () => {
    const fetchImpl = vi.fn<typeof fetch>().mockRejectedValue(new TypeError('Failed to fetch'))
    const error = await createHttpClient(vi.fn<() => void>(), fetchImpl)
      .get('/x')
      .catch((e: unknown) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({
      message: 'Network error. Check your connection and try again.',
    })
  })

  it('redirects to login only once for concurrent 401 responses', async () => {
    const redirect = vi.fn<(url: string) => void>()
    const client = createHttpClient(
      createLoginRedirect(redirect),
      respond(401, { message: 'Unauthenticated.' }),
    )

    const results = await Promise.allSettled([client.get('/api/me'), client.get('/api/countries')])

    expect(redirect).toHaveBeenCalledExactlyOnceWith('/login')
    for (const result of results) {
      expect(result).toMatchObject({
        status: 'rejected',
        reason: { message: 'Unauthenticated.' },
      })
    }
  })
})
