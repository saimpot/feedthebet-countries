<script setup lang="ts">
import { onMounted } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import CountryGrid from '@/components/CountryGrid.vue'
import EmptyState from '@/components/EmptyState.vue'
import ErrorState from '@/components/ErrorState.vue'
import LoadingGrid from '@/components/LoadingGrid.vue'
import SearchInput from '@/components/SearchInput.vue'
import { useAuth } from '@/composables/useAuth'
import { useCountries } from '@/composables/useCountries'

const { user, loadUser, logout } = useAuth()
const { state, search, filtered, total, load, retry } = useCountries()

onMounted(async () => {
  // Guests are being redirected to /login by the HTTP client; skip further API calls.
  if (await loadUser()) await load()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
    <AppHeader title="Countries of the World" :user="user" @logout="logout" />
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6">
      <LoadingGrid v-if="state.status === 'loading' || state.status === 'idle'" />
      <ErrorState v-else-if="state.status === 'error'" :message="state.message" @retry="retry" />
      <template v-else>
        <SearchInput v-model="search" />
        <p aria-live="polite" class="text-sm text-slate-600 dark:text-slate-400">
          Showing {{ filtered.length }} of {{ total }} countries
        </p>
        <CountryGrid v-if="filtered.length" :countries="filtered" />
        <EmptyState v-else :query="search" />
      </template>
    </main>
  </div>
</template>
