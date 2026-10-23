export interface ApiResource<T> {
  data: T
}

export interface ApiErrorBody {
  message?: string
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
