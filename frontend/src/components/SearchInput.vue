<script setup lang="ts">
const model = defineModel<string>({ required: true })
</script>

<template>
  <div>
    <label for="country-search" class="sr-only">Search countries</label>
    <input
      id="country-search"
      v-model="model"
      type="search"
      placeholder="Search countries..."
      autocomplete="off"
      class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-slate-900 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-400"
    />
  </div>
</template>
