<script setup lang="ts">
import type { Country } from '@/types/country'

defineProps<{ country: Country }>()
</script>

<template>
  <li
    class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
  >
    <div class="aspect-3/2 w-full overflow-hidden rounded-md bg-slate-100 dark:bg-slate-800">
      <img
        :src="country.flagUrl"
        :alt="country.flagAlt ?? `Flag of ${country.name}`"
        loading="lazy"
        decoding="async"
        class="size-full object-contain"
      />
    </div>
    <p class="text-center text-sm font-medium text-slate-900 dark:text-slate-100">
      {{ country.name }}
    </p>
  </li>
</template>
