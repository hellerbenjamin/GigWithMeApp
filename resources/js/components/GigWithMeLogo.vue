<script setup>
/**
 * GigWithMe wordmark.
 *
 * Wordmark-only — the word inherits `currentColor`, so set it with a text color
 * utility (e.g. `class="text-stage-indigo"`). The `variant` prop is kept for
 * call-site compatibility with the previous logo; rendering is the same for all
 * variants now (color comes from `currentColor`), but it gives us a hook if we
 * reintroduce a graphic mark later.
 *
 *   <GigWithMeLogo class="h-10 text-stage-indigo" />          // light
 *   <GigWithMeLogo class="h-10 text-canvas" variant="dark" /> // on indigo
 *   <GigWithMeLogo class="h-8" variant="mono" />              // single-color
 */
defineProps({
  variant: {
    type: String,
    default: 'light', // 'light' | 'dark' | 'mono'
    validator: v => ['light', 'dark', 'mono'].includes(v),
  },
})
</script>

<template>
  <svg
    class="gigwithme-logo"
    :class="`gigwithme-logo--${variant}`"
    viewBox="0 0 760 190"
    role="img"
    aria-label="GigWithMe"
  >
    <title>GigWithMe</title>
    <text class="gigwithme-logo__word" x="8" y="148" font-size="140" font-weight="600" letter-spacing="-4">
      GigWithMe
    </text>
  </svg>
</template>

<style scoped>
.gigwithme-logo {
  display: block;
  height: 1em;
  width: auto;
  overflow: visible;
}

.gigwithme-logo__word {
  fill: currentColor;
  font-family: 'Fredoka', 'Baloo 2', system-ui, sans-serif;
}
</style>
