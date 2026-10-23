<script setup lang="ts">
import CardGrid from '@/components/CardGrid.vue'

withDefaults(defineProps<{ count?: number }>(), { count: 18 })
</script>

<template>
  <CardGrid aria-hidden="true">
    <li
      v-for="n in count"
      :key="n"
      data-testid="skeleton-card"
      class="card-surface motion-safe:animate-pulse"
    >
      <div class="aspect-3/2 w-full rounded-md bg-slate-200 dark:bg-slate-800"></div>
      <div class="mx-auto h-4 w-2/3 rounded bg-slate-200 dark:bg-slate-800"></div>
    </li>
  </CardGrid>
</template>
