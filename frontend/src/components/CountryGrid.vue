<script setup lang="ts">
import CountryCard from '@/components/CountryCard.vue'
import type { Country } from '@/types/country'

defineProps<{ countries: Country[] }>()
</script>

<template>
  <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
    <CountryCard v-for="country in countries" :key="country.code" :country="country" />
  </ul>
</template>
