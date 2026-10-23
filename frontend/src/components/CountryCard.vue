<script setup lang="ts">
import { ref, watch } from 'vue'
import type { Country } from '@/types/country'

const props = defineProps<{ country: Country }>()

type ImageStatus = 'loading' | 'loaded' | 'error'

const imageStatus = ref<ImageStatus>('loading')

watch(
  () => props.country.flagUrl,
  () => (imageStatus.value = 'loading'),
)
</script>

<template>
  <li
    class="card-surface shadow-sm hover:shadow-md motion-safe:transition motion-safe:hover:-translate-y-0.5"
  >
    <div
      class="relative aspect-3/2 w-full overflow-hidden rounded-md bg-slate-100 dark:bg-slate-800"
    >
      <div
        v-if="imageStatus === 'loading'"
        data-testid="flag-skeleton"
        aria-hidden="true"
        class="absolute inset-0 bg-slate-200 motion-safe:animate-pulse dark:bg-slate-700"
      ></div>

      <div
        v-if="imageStatus === 'error'"
        data-testid="flag-fallback"
        role="img"
        :aria-label="`Flag of ${country.name} unavailable`"
        class="absolute inset-0 flex flex-col items-center justify-center gap-1 text-slate-600 dark:text-slate-300"
      >
        <svg
          aria-hidden="true"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          class="size-8"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4m0 0h11l-1.5 4L15 12H4" />
        </svg>
        <span class="text-xs">Flag unavailable</span>
      </div>

      <img
        v-else
        :src="country.flagUrl"
        :alt="country.flagAlt ?? `Flag of ${country.name}`"
        loading="lazy"
        decoding="async"
        class="size-full object-contain transition-opacity duration-300 motion-reduce:transition-none"
        :class="imageStatus === 'loaded' ? 'opacity-100' : 'opacity-0'"
        @load="imageStatus = 'loaded'"
        @error="imageStatus = 'error'"
      />
    </div>
    <p class="text-center text-sm font-medium text-slate-900 dark:text-slate-100">
      {{ country.name }}
    </p>
  </li>
</template>
