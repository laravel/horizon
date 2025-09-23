import axios from 'axios';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createRouter, createWebHistory } from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';
import Base from './base';
import Routes from './routes';
import Alert from './components/Alert.vue';
import SchemeToggler from './components/SchemeToggler.vue';
import Poll from './components/Poll.vue';

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
            autoLoadsNewEntries: localStorage.autoLoadsNewEntries === '1',
        };
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
app.component('scheme-toggler', SchemeToggler);
app.component('poll', Poll);

app.mixin(Base);

app.mount('#horizon');
