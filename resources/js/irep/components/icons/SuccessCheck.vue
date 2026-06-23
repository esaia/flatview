<template>
  <div class="ire-success-mark">
    <span class="ire-success-ring"></span>
    <svg
      class="ire-success-svg"
      viewBox="0 0 52 52"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <circle
        class="ire-success-circle"
        cx="26"
        cy="26"
        r="24"
        stroke="var(--primary-color, #16a34a)"
        stroke-width="2"
      />
      <path
        class="ire-success-check"
        d="M15 27 L23 35 L38 18"
        stroke="var(--primary-color, #16a34a)"
        stroke-width="3"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
    </svg>
  </div>
</template>

<style scoped>
.ire-success-mark {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 5rem;
  height: 5rem;
}

.ire-success-svg {
  position: relative;
  width: 5rem;
  height: 5rem;
}

.ire-success-circle {
  transform-origin: 50% 50%;
  stroke-dasharray: 151;
  stroke-dashoffset: 151;
  animation: ire-circle-draw 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.ire-success-check {
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: ire-check-draw 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
}

.ire-success-ring {
  position: absolute;
  inset: 0;
  border-radius: 9999px;
  background: var(--primary-color, #16a34a);
  opacity: 0.12;
  transform: scale(0);
  animation: ire-ring-pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.45s forwards;
}

@keyframes ire-circle-draw {
  to {
    stroke-dashoffset: 0;
  }
}

@keyframes ire-check-draw {
  to {
    stroke-dashoffset: 0;
  }
}

@keyframes ire-ring-pop {
  0% {
    transform: scale(0);
    opacity: 0.22;
  }
  100% {
    transform: scale(1);
    opacity: 0.12;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ire-success-circle,
  .ire-success-check,
  .ire-success-ring {
    animation-duration: 0.01ms;
    animation-delay: 0ms;
  }
}
</style>
