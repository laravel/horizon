require('./bootstrap')

import _reduce from 'lodash/reduce'
import _truncate from 'lodash/truncate'
import moment from 'moment'
import router from './router'
import App from './components/App.vue'

Vue.mixin({
    methods: {
        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime) {
            return moment(unixTime * 1000)
                .add(new Date().getTimezoneOffset() / 60)
        },

        /**
         * Extract the job base name.
         */
        jobBaseName(name) {
            if (!name.includes('\\')) return name

            let parts = name.split('\\')

            return parts[parts.length - 1]
        },

        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp) {
            return this.formatDate(timestamp).format('YY-MM-DD HH:mm:ss')
        },

        /**
         * Format the tags.
         */
        displayableTagsList(tags, truncate = true) {
            if (!tags || !tags.length) return ''

            return _reduce(tags, (s, n) => {
                return (s ? s + ', ' : '') + (truncate ? _truncate(n) : n)
            }, '')
        }
    }
})

new Vue({
    el: '#root',
    router,
    render: (h) => h(App)
})
