import axios from 'axios';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createRouter, createWebHistory } from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';
import Base from './base';
import Routes from './routes';
import Alert from './components/Alert.vue';
import HorizonStatus from './components/HorizonStatus.vue';
import SchemeToggler from './components/SchemeToggler.vue';
import Poll from './components/Poll.vue';
import TableEmpty from './components/TableEmpty.vue';
import NewEntries from './components/NewEntries.vue';

const LOCALSTORAGE_AUTOLOAD_KEY = 'horizonAutoLoadsNewEntries';

let token = document.head.querySelector("meta[name='csrf-token']");

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

const app = createApp({
    data() {
        return {
            alert: {
                type: null,
                autoClose: 0,
                message: '',
                confirmationProceed: null,
                confirmationCancel: null,
            },
            autoLoadsNewEntries: localStorage[LOCALSTORAGE_AUTOLOAD_KEY] === '1',
            stats: {
                status: null,
                processing: false,
                navigation: {
                    monitoring: null,
                    metrics: null,
                    batches: null,
                    pending: null,
                    completed: null,
                    silenced: null,
                    failed: null,
                },
            },
            statsReady: false,
            statsUnavailable: false,
        };
    },

    methods: {
        loadStats() {
            return this.$http
                .get(Horizon.basePath + '/api/stats')
                .then((response) => {
                    // /api/stats is the sole authority for shell-wide processing.
                    this.stats = response.data;
                    this.statsReady = true;
                    this.statsUnavailable = false;
                })
                .catch(() => {
                    this.statsUnavailable = true;
                    this.statsReady = false;
                    this.stats = {
                        status: null,
                        processing: false,
                        navigation: {
                            monitoring: null,
                            metrics: null,
                            batches: null,
                            pending: null,
                            completed: null,
                            silenced: null,
                            failed: null,
                        },
                    };
                });
        },

        navigationCount(key) {
            const value = this.stats.navigation?.[key];

            return Number.isInteger(value) ? value.toLocaleString() : null;
        },
    },
});

app.config.globalProperties.$http = axios.create();

let proxyPath = window.Horizon.proxy_path;
window.Horizon.basePath = proxyPath + '/' + window.Horizon.path;

let routerBasePath = window.Horizon.basePath + '/';

if (window.Horizon.path === '' || window.Horizon.path === '/') {
    routerBasePath = proxyPath + '/';
    window.Horizon.basePath = proxyPath;
}

const router = createRouter({
    history: createWebHistory(routerBasePath),
    routes: Routes,
});

app.use(router);

app.component('vue-json-pretty', VueJsonPretty);
app.component('alert', Alert);
app.component('horizon-status', HorizonStatus);
app.component('scheme-toggler', SchemeToggler);
app.component('poll', Poll);
app.component('table-empty', TableEmpty);
app.component('new-entries', NewEntries);

app.mixin(Base);

app.mount('#horizon');
