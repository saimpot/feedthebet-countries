import { computed, ref } from 'vue'
import { fetchCurrentUser, logoutUrl } from '@/api/authApi'
import type { User } from '@/types/user'

export function useAuth() {
  const user = ref<User | null>(null)
  const isAuthenticated = computed(() => user.value !== null)

  /** Resolves to whether a user is signed in. */
  async function loadUser(): Promise<boolean> {
    try {
      user.value = await fetchCurrentUser()
    } catch {
      user.value = null
    }
    return user.value !== null
  }

  function logout(): void {
    window.location.assign(logoutUrl)
  }

  return { user, isAuthenticated, loadUser, logout }
}
