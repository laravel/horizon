import Vue from 'vue';
import Base from './base';
import _ from 'lodash';
import axios from 'axios';
import Routes from './routes';
import VueRouter from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';
import moment from 'moment-timezone';

require('bootstrap');

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

Vue.use(VueRouter);

window.Popper = require('popper.js').default;

Vue.prototype.$http = axios.create();

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: '/' + window.Horizon.path + '/',
});

Vue.component('vue-json-pretty', VueJsonPretty);
Vue.component('alert', require('./components/Alert.vue').default);

Vue.mixin(Base);

new Vue({
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

            autoLoadsNewEntries: localStorage.autoLoadsNewEntries === '1',
        };
    },
});
