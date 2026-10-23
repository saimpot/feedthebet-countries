<script setup lang="ts">
withDefaults(defineProps<{ count?: number }>(), { count: 18 })
</script>

<template>
  <div role="status" aria-live="polite" aria-busy="true">
    <span class="sr-only">Loading countries...</span>
    <ul
      aria-hidden="true"
      class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"
    >
      <li
        v-for="n in count"
        :key="n"
        data-testid="skeleton-card"
        class="flex animate-pulse flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900"
      >
        <div class="aspect-3/2 w-full rounded-md bg-slate-200 dark:bg-slate-800"></div>
        <div class="mx-auto h-4 w-2/3 rounded bg-slate-200 dark:bg-slate-800"></div>
      </li>
    </ul>
  </div>
</template>
