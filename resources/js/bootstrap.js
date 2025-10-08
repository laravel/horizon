import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import url from '@/util/url';

export default class Horizon {
    constructor(config) {
        /** @readonly */
        this.appConfig = config;

        /**
         * @protected
         * @type {{[key: string]: VueComponent|DefineComponent}}
         */
        this.pages = {
            'Horizon.Dashboard': 'Dashboard',
        };
    }

    /**
     * Return configuration value from a key.
     *
     * @param  {string} key
     * @returns {any}
     */
    config(key) {
        return this.appConfig[key];
    }

    /**
     * Start the Horizon app by creating the underlying Vue instance.
     */
    liftOff() {
        createInertiaApp({
            resolve: (name) => {
                const page = this.pages[name] != null ? this.pages[name] : 'Loading';

                const inertiaPages = import.meta.glob('./pages/**/*.vue', { eager: true });

                return inertiaPages[`./pages/${page}.vue`];
            },
            setup: ({ el, App, props, plugin }) => {
                /** @protected */
                this.mountTo = el;

                /**
                 * @protected
                 * @type VueApp
                 */
                this.app = createApp({ render: () => h(App, props) });
                this.app.use(plugin);

                this.app.mixin({
                    methods: {
                        $url: (path, parameters) => this.url(path, parameters),
                    },
                });

                this.app.mount(el);
            },
            progress: {
                color: '#4B5563',
            },
        });
    }

    /**
     * Get the URL from base Horizon prefix.
     *
     * @param {string} path
     * @param {any} parameters
     * @returns {string}
     */
    url(path, parameters) {
        return url(this.config('base'), path, parameters);
    }
}
