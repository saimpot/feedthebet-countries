<script setup lang="ts">
import CardGrid from '@/components/CardGrid.vue'
import CountryCard from '@/components/CountryCard.vue'
import type { Country } from '@/types/country'

defineProps<{
  countries: Country[]
  /** Codes of the cards to show; the rest stay mounted but hidden so flags are not reloaded. */
  visible?: ReadonlySet<string>
}>()
</script>

<template>
  <CardGrid>
    <CountryCard
      v-for="country in countries"
      v-show="!visible || visible.has(country.code)"
      :key="country.code"
      :country="country"
    />
  </CardGrid>
</template>
