import Vue from 'vue';
import _ from 'lodash';
import axios from 'axios'
import moment from 'moment';
import router from './router';
import App from './components/App.vue';

window.$ = window.jQuery = require('jquery');
window.Popper = require('popper.js').default;

require('bootstrap');

$('body').tooltip({
    selector: '[data-toggle=tooltip]'
});

Vue.prototype.$http = axios.create({
  baseURL: `${location.protocol}//${location.host}/${horizon.uri}`
});

window.Bus = new Vue({name: 'Bus'});

Vue.component('loader', require('./components/Status/Loader.vue'));

Vue.config.errorHandler = function (err, vm, info) {
    console.error(err);
};

Vue.mixin({
    methods: {
        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime){
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60)
        },


        /**
         * Extract the job base name.
         */
        jobBaseName(name){
            if (!name.includes('\\')) return name;

            var parts = name.split("\\");

            return parts[parts.length - 1];
        },


        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp){
            return this.formatDate(timestamp).format('YY-MM-DD HH:mm:ss');
        },


        /**
         * Format the tags.
         */
        displayableTagsList(tags, truncate = true){
            if (!tags || !tags.length) return '';

            return _.reduce(tags, (s, n)=> {
                return (s ? s + ', ' : '') + (truncate ? _.truncate(n) : n);
            }, '');
        }
    }
});

new Vue({
    el: '#root',

    router,

    /**
     * The component's data.
     */
    data() {
        return {}
    },

    render: h => h(App),
});
