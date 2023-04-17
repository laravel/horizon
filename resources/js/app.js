import Vue from 'vue';
import Base from './base';
import axios from 'axios';
import Routes from './routes';
import VueRouter from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import VueI18n from 'vue-i18n';

import en from '../lang/en.json';
import zh from '../lang/zh.json';

Vue.use(VueI18n);
const messages = {
    en,
    zh,
};

const i18n = new VueI18n({
    locale: 'en',
    fallbackLocale: 'en',
    messages,
});

window.Popper = require('popper.js').default;

try {
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');
} catch (e) {}

let token = document.head.querySelector('meta[name="csrf-token"]');

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

Vue.use(VueRouter);

Vue.prototype.$http = axios.create();

window.Horizon.basePath = '/' + window.Horizon.path;

let routerBasePath = window.Horizon.basePath + '/';

if (window.Horizon.path === '' || window.Horizon.path === '/') {
    routerBasePath = '/';
    window.Horizon.basePath = '';
}

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: routerBasePath,
});

Vue.component('vue-json-pretty', VueJsonPretty);
Vue.component('alert', require('./components/Alert.vue').default);

Vue.mixin(Base);

Vue.directive('tooltip', function (el, binding) {
    $(el).tooltip({
        title: binding.value,
        placement: binding.arg,
        trigger: 'hover',
    });
});

new Vue({
    i18n,
    el: '#horizon',

    router,

    data() {
        return {
            alert: {
                type: null,
                autoClose: 0,
                message: '',
                confirmationProceed: null,
                confirmationCancel: null,
            },
            selectedLanguage: 'en',
            language: {
                en: '🇺🇸 English',
                zh: '🇨🇳 中文',
            },
            autoLoadsNewEntries: localStorage.autoLoadsNewEntries === '1',
        };
    },
    methods: {
        changeLanguage(lang) {
            this.selectedLanguage = lang;
            this.$i18n.locale = this.selectedLanguage;
        },
    },
});
