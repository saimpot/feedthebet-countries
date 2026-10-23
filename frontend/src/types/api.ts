export interface ApiResource<T> {
  data: T
}

export class ApiError extends Error {
  readonly status: number

  constructor(status: number, message: string) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

export class UnauthenticatedError extends ApiError {
  constructor(message = 'Unauthenticated.') {
    super(401, message)
    this.name = 'UnauthenticatedError'
  }
}

export function errorMessage(error: unknown): string {
  return error instanceof Error ? error.message : 'Something went wrong.'
}
