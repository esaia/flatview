<script setup>
defineProps({
    // While the floating menu is open the panel (z-40) must cover this button,
    // so drop below it instead of sitting on top at z-50.
    menuOpen: { type: Boolean, default: false },
})

const PHONE = '995592246555'
const DEFAULT_MESSAGE = "Hi! I'd like to know more about FlatView."

function openWhatsApp() {
    const text = encodeURIComponent(DEFAULT_MESSAGE)
    window.open(`https://web.whatsapp.com/send?phone=${PHONE}&text=${text}`, '_blank', 'noopener,noreferrer')
}
</script>

<template>
    <button
        @click="openWhatsApp"
        :class="menuOpen ? 'z-30' : 'z-50'"
        class="fixed bottom-[calc(1.25rem+env(safe-area-inset-bottom))] right-5 md:bottom-8 md:right-8 flex items-center justify-center w-14 h-14 rounded-full shadow-lg transition-transform duration-300 hover:scale-105 active:scale-95"
        style="background-color: #25D366;"
        aria-label="Chat with us on WhatsApp"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="currentColor" class="relative h-7 w-7 text-white">
            <path d="M16.004 3C9.107 3 3.5 8.605 3.5 15.5c0 2.29.615 4.508 1.784 6.451L3 29l7.216-2.246A12.94 12.94 0 0 0 16.004 28C22.9 28 28.507 22.395 28.507 15.5S22.9 3 16.004 3Zm0 23.55a10.99 10.99 0 0 1-5.6-1.532l-.4-.237-4.283 1.334 1.371-4.17-.26-.428a10.99 10.99 0 0 1-1.68-5.8c0-6.077 4.947-11.024 11.023-11.024 2.945 0 5.713 1.147 7.795 3.23a10.943 10.943 0 0 1 3.229 7.794c0 6.077-4.947 11.024-11.023 11.024Zm6.03-8.257c-.33-.165-1.955-.965-2.258-1.076-.303-.11-.523-.165-.744.166-.22.33-.853 1.075-1.046 1.296-.193.22-.386.248-.716.083-.33-.165-1.393-.513-2.653-1.636-.981-.875-1.643-1.956-1.836-2.286-.193-.33-.02-.508.145-.672.149-.148.33-.386.496-.579.165-.193.22-.33.33-.55.11-.221.055-.414-.028-.58-.083-.165-.744-1.794-1.02-2.456-.269-.645-.542-.558-.744-.568l-.634-.011c-.22 0-.578.083-.881.414-.303.33-1.157 1.13-1.157 2.758s1.184 3.202 1.35 3.423c.164.22 2.33 3.557 5.646 4.988.789.34 1.404.543 1.884.695.792.252 1.513.216 2.083.131.635-.095 1.955-.8 2.23-1.572.275-.772.275-1.434.193-1.572-.083-.138-.303-.22-.634-.386Z"></path>
        </svg>
    </button>
</template>
