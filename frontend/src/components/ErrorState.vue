<script setup lang="ts">
defineProps<{ message: string }>()
const emit = defineEmits<{ retry: [] }>()
</script>

<template>
  <div
    role="alert"
    class="mx-auto max-w-md rounded-xl border border-red-200 bg-red-50 p-6 text-center dark:border-red-900 dark:bg-red-950"
  >
    <p class="font-medium text-red-800 dark:text-red-200">{{ message }}</p>
    <button
      type="button"
      class="mt-4 rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
      @click="emit('retry')"
    >
      Retry
    </button>
  </div>
</template>
