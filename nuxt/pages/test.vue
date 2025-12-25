<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4">TLS Test Page</h1>
    <p class="mb-4">This page tests resource loading without TLS errors.</p>

    <div class="space-y-2">
      <div class="p-2 bg-green-100 text-green-800 rounded">
        ✅ Page loaded successfully
      </div>

      <div class="p-2 bg-blue-100 text-blue-800 rounded">
        ✅ CSS should load without TLS errors
      </div>

      <div class="p-2 bg-purple-100 text-purple-800 rounded">
        ✅ JavaScript should load without TLS errors
      </div>
    </div>

    <div class="mt-4">
      <NuxtLink to="/" class="text-blue-500 hover:underline">
        ← Back to home
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
// Test page for TLS issues
</script>
