<script setup lang="ts">
import type { User } from '@/types/user'

defineProps<{ title: string; user: User | null }>()
const emit = defineEmits<{ logout: [] }>()
</script>

<template>
  <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
      <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h1>
      <div v-if="user" class="flex items-center gap-3">
        <img
          :src="user.picture"
          :alt="`Avatar of ${user.name}`"
          referrerpolicy="no-referrer"
          class="size-8 rounded-full bg-slate-200 object-cover"
        />
        <span class="hidden text-sm text-slate-700 sm:inline dark:text-slate-300">{{
          user.name
        }}</span>
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-300 dark:hover:bg-slate-800"
          @click="emit('logout')"
        >
          Log out
        </button>
      </div>
    </div>
  </header>
</template>
