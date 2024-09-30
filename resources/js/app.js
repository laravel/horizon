import axios from 'axios';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createRouter, createWebHistory } from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import 'vue-json-pretty/lib/styles.css';
import Base from './base';
import Routes from './routes';
import Alert from './components/Alert.vue';
import SchemeToggler from './components/SchemeToggler.vue';

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

const appUrl = window.Horizon.appUrl || '';
const horizonPath = window.Horizon.path || 'horizon';

// Construct the base path dynamically using APP_URL and HORIZON_PATH
// Check if appUrl ends with a slash and handle accordingly
window.Horizon.basePath = appUrl.replace(/\/+$/, '') + '/' + horizonPath.replace(/^\/+/, '');

// Construct the router base path using the newly set basePath
let routerBasePath = window.Horizon.basePath + '/';

// Adjust the base path if Horizon's path is empty or set to root
if (horizonPath === '' || horizonPath === '/') {
    routerBasePath = appUrl + '/';
    window.Horizon.basePath = appUrl;
}

// Use the constructed base path in the router configuration
const router = createRouter({
    history: createWebHistory(routerBasePath),
    routes: Routes,
});

// Use the router in the Vue app
app.use(router);

app.component('vue-json-pretty', VueJsonPretty);
app.component('alert', Alert);
app.component('scheme-toggler', SchemeToggler);

app.mixin(Base);

app.mount('#horizon');
