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
        formatDate(unixTime) {
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60)
        },
        /**
         * Check if the timestamp is from the current day
         */
        isCurrentDay(timestamp) {
            return moment().isSame(timestamp, 'day');
        },
        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp) {
            let finalTimestamp = this.formatDate(timestamp);
            let formatTemplate = 'YYYY-MM-DD HH:mm:ss';

            if (this.isCurrentDay(finalTimestamp)) {
                formatTemplate = 'HH:mm:ss';
            }
            return finalTimestamp.format(formatTemplate);
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
