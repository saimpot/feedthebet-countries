<script setup lang="ts">
defineProps<{ query: string }>()
</script>

<template>
  <p class="py-12 text-center text-slate-600 dark:text-slate-400">
    No countries match &ldquo;{{ query }}&rdquo;.
  </p>
</template>
