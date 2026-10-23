import { computed, ref } from 'vue'
import { fetchCurrentUser, logoutUrl } from '@/api/authApi'
import type { User } from '@/types/user'

export function useAuth() {
  const user = ref<User | null>(null)
  const isAuthenticated = computed(() => user.value !== null)

  async function loadUser(): Promise<void> {
    try {
      user.value = await fetchCurrentUser()
    } catch {
      user.value = null
    }
  }

  function logout(): void {
    window.location.assign(logoutUrl)
  }

  return { user, isAuthenticated, loadUser, logout }
}
