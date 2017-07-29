import Vue from 'vue';
import axios from 'axios'
import moment from 'moment';
import router from './router/';
import App from './components/App.vue';

Vue.prototype.$http = axios.create();

window.Bus = new Vue({name: 'Bus'});

Vue.mixin({
    methods: {
        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime){
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60)
        },

        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp){
            return this.formatDate(timestamp).format('HH:mm:ss');
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
        return {
            showModal: false
        }
    },

    render: h => h(App),
});
