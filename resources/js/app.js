import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';

const pages = {
    'Horizon.Home': 'Home',
};

createInertiaApp({
    // title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const page = pages[name] != null ? pages[name] : 'Loading';

        console.log(page, name, `./pages/${page}.vue`);

        const inertiaPages = import.meta.glob('./pages/**/*.vue', { eager: true });

        return inertiaPages[`./pages/${page}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
