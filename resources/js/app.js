import '../css/app.css';
// NOTE: Fancybox + irep.css (≈87 KiB) are NOT imported here. They used to be
// global, which bundled them into the render-blocking app.css on every page
// even though `ire-` classes only appear inside the async-loaded 360 viewer.
// They now load with that viewer's chunk (see IreProject360.vue), keeping the
// critical CSS on non-viewer pages small.
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Flatview';

createInertiaApp({
    title: (title) =>
        title && title !== appName ? `${title} - ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
