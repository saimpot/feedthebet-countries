import { httpClient } from '@/api/httpClient'
import type { HttpClient } from '@/api/httpClient'
import type { ApiResource } from '@/types/api'
import type { User } from '@/types/user'

export const loginUrl = '/login'
export const logoutUrl = '/logout'

export async function fetchCurrentUser(client: HttpClient = httpClient): Promise<User> {
  const body = await client.get<ApiResource<User>>('/api/me')
  return body.data
}
