<script>
import { Fancybox } from "@fancyapps/ui";

export default {
    props: {
        options: Object,
    },
    mounted() {
        Fancybox.bind(this.$refs.container, "[data-fancybox]", {
            Hash: false,
            Images: {
                zoom: false,
                Panzoom: {
                    maxScale: 2,
                },
            },

            ...(this.options || {}),
        });
    },
    updated() {
        Fancybox.unbind(this.$refs.container);
        Fancybox.close();

        Fancybox.bind(this.$refs.container, "[data-fancybox]", {
            Hash: false,
            Images: {
                zoom: false,
                Panzoom: {
                    maxScale: 2,
                },
            },
            ...(this.options || {}),
        });
    },
    unmounted() {
        Fancybox.destroy();
    },
};
</script>

<template>
    <div ref="container" class="irep-fancybox">
        <slot></slot>
    </div>
</template>
