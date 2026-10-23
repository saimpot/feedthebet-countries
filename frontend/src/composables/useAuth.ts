import { computed, ref } from 'vue'
import { fetchCurrentUser } from '@/api/authApi'
import { logoutUrl } from '@/api/routes'
import { UnauthenticatedError } from '@/types/api'
import type { User } from '@/types/user'

export type AuthStatus = 'authenticated' | 'unauthenticated'

export function useAuth() {
  const user = ref<User | null>(null)
  const isAuthenticated = computed(() => user.value !== null)

  /**
   * 'unauthenticated' means the HTTP client is already redirecting to login.
   * Any other failure (5xx, network) is rethrown for the caller to surface.
   */
  async function loadUser(): Promise<AuthStatus> {
    try {
      user.value = await fetchCurrentUser()
      return 'authenticated'
    } catch (error) {
      if (error instanceof UnauthenticatedError) {
        user.value = null
        return 'unauthenticated'
      }
      throw error
    }
  }

  function logout(): void {
    window.location.assign(logoutUrl)
  }

  return { user, isAuthenticated, loadUser, logout }
}
