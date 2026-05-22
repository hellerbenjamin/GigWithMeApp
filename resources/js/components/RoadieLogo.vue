<script setup>
/**
 * Roadie wordmark.
 *
 * The word inherits `currentColor`, so set it with a text color utility
 * (e.g. `class="text-stage-indigo"`). The cable + jack-plug tip are driven
 * by CSS variables with brand defaults, and the `variant` prop sets the
 * three common pairings.
 *
 *   <RoadieLogo class="h-10 text-stage-indigo" />          // light
 *   <RoadieLogo class="h-10 text-canvas" variant="dark" /> // on indigo
 *   <RoadieLogo class="h-8" variant="mono" />              // single-color
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
    class="roadie-logo"
    :class="`roadie-logo--${variant}`"
    viewBox="0 0 580 230"
    role="img"
    aria-label="Roadie"
  >
    <title>Roadie</title>
    <!-- coiled patch cable plugging into a 1/4" jack -->
    <path
      class="roadie-logo__cable"
      d="M74 176 c6 -16 24 -16 30 0 c6 16 24 16 30 0 c6 -16 24 -16 30 0 C238 168 330 182 392 174"
      fill="none"
      stroke-width="9"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
    <rect class="roadie-logo__sleeve" x="388" y="161" width="40" height="26" rx="9" />
    <rect class="roadie-logo__tip" x="432" y="164" width="26" height="20" rx="9" />
    <text class="roadie-logo__word" x="46" y="150" font-size="130" font-weight="600" letter-spacing="-3">
      Roadie
    </text>
  </svg>
</template>

<style scoped>
.roadie-logo {
  /* brand defaults — override per-instance if needed */
  --roadie-cable: #6c4fd8; /* Amp Violet */
  --roadie-tip: #ff6b5c;   /* Encore Coral */
  display: block;
  height: 1em;
  width: auto;
  overflow: visible;
}
.roadie-logo--dark { --roadie-cable: #9b86e8; }
.roadie-logo--mono { --roadie-cable: currentColor; --roadie-tip: currentColor; }

.roadie-logo__word {
  fill: currentColor;
  font-family: 'Fredoka', 'Baloo 2', system-ui, sans-serif;
}
.roadie-logo__cable  { stroke: var(--roadie-cable); }
.roadie-logo__sleeve { fill: var(--roadie-cable); }
.roadie-logo__tip    { fill: var(--roadie-tip); }
</style>
