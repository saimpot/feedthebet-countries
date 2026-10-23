import { httpClient } from '@/api/httpClient'
import type { HttpClient } from '@/api/httpClient'
import type { ApiResource } from '@/types/api'
import type { Country } from '@/types/country'

export async function fetchCountries(client: HttpClient = httpClient): Promise<Country[]> {
  const body = await client.get<ApiResource<Country[]>>('/api/countries')
  return body.data
}
