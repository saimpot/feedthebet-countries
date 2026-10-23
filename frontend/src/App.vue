<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import CountryGrid from '@/components/CountryGrid.vue'
import EmptyState from '@/components/EmptyState.vue'
import ErrorState from '@/components/ErrorState.vue'
import LoadingGrid from '@/components/LoadingGrid.vue'
import SearchInput from '@/components/SearchInput.vue'
import { useAuth } from '@/composables/useAuth'
import { useCountries } from '@/composables/useCountries'
import { errorMessage } from '@/types/api'

const { user, loadUser, logout } = useAuth()
const { state, search, countries, filtered, total, load } = useCountries()
const visibleCodes = computed(() => new Set(filtered.value.map((country) => country.code)))
const userError = ref<string | null>(null)

async function boot(): Promise<void> {
  userError.value = null
  try {
    // 'unauthenticated' means a login redirect is in progress: keep the loading view.
    if ((await loadUser()) === 'unauthenticated') return
  } catch (error) {
    userError.value = errorMessage(error)
    return
  }
  await load()
}

onMounted(boot)

const error = computed(
  () => userError.value ?? (state.value.status === 'error' ? state.value.message : null),
)
const isLoading = computed(
  () => error.value === null && (state.value.status === 'idle' || state.value.status === 'loading'),
)
const isReady = computed(() => error.value === null && state.value.status === 'success')

const announcement = computed(() => {
  if (isLoading.value) return 'Loading countries…'
  if (isReady.value) return `Showing ${filtered.value.length} of ${total.value} countries`
  return ''
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
    <AppHeader title="Countries of the World" :user="user" @logout="logout" />
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6">
      <p role="status" class="sr-only">{{ announcement }}</p>

      <ErrorState v-if="error !== null" :message="error" @retry="boot" />
      <template v-else>
        <SearchInput v-model="search" :disabled="!isReady" />
        <LoadingGrid v-if="isLoading" />
        <template v-else>
          <p class="text-sm text-slate-600 dark:text-slate-400">
            Showing {{ filtered.length }} of {{ total }} countries
          </p>
          <CountryGrid :countries="countries" :visible="visibleCodes" />
          <EmptyState v-if="!filtered.length" :query="search" />
        </template>
      </template>
    </main>
  </div>
</template>
