import _ from 'lodash';
import moment from 'moment-timezone';

export default {
    computed: {
        Horizon() {
            return Horizon;
        },
    },

    methods: {
        /**
         * Show the time ago format for the given time.
         */
        timeAgo(time) {
            moment.updateLocale('en', {
                relativeTime: {
                    future: 'in %s',
                    past: '%s ago',
                    s: number => number + 's ago',
                    ss: '%ds ago',
                    m: '1m ago',
                    mm: '%dm ago',
                    h: '1h ago',
                    hh: '%dh ago',
                    d: '1d ago',
                    dd: '%dd ago',
                    M: 'a month ago',
                    MM: '%d months ago',
                    y: 'a year ago',
                    yy: '%d years ago',
                },
            });

            let secondsElapsed = moment().diff(time, 'seconds');
            let dayStart = moment('2018-01-01')
                .startOf('day')
                .seconds(secondsElapsed);

            if (secondsElapsed > 300) {
                return moment(time).fromNow(true);
            } else if (secondsElapsed < 60) {
                return dayStart.format('s') + 's ago';
            } else {
                return dayStart.format('m:ss') + 'm ago';
            }
        },

        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime) {
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60);
        },

        /**
         * Extract the job base name.
         */
        jobBaseName(name) {
            if (!name.includes('\\')) return name;

            var parts = name.split('\\');

            return parts[parts.length - 1];
        },

        /**
         * Autoload new entries in listing screens.
         */
        autoLoadNewEntries() {
            if (!this.autoLoadsNewEntries) {
                this.autoLoadsNewEntries = true;
                localStorage.autoLoadsNewEntries = 1;
            } else {
                this.autoLoadsNewEntries = false;
                localStorage.autoLoadsNewEntries = 0;
            }
        },

        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp) {
            return this.formatDate(timestamp).format('YYYY-MM-DD HH:mm:ss');
        },

        /**
         * Format the tags.
         */
        displayableTagsList(tags, truncate = true) {
            if (!tags || !tags.length) return '';

            return _.reduce(
                tags,
                (s, n) => {
                    return (s ? s + ', ' : '') + (truncate ? _.truncate(n) : n);
                },
                ''
            );
        },

        /**
         * Show the time in local time.
         */
        localTime(time) {
            return moment(time + ' Z')
                .utc()
                .local()
                .format('MMMM Do YYYY, h:mm:ss A');
        },

        /**
         * Truncate the given string.
         */
        truncate(string, length = 70) {
            return _.truncate(string, {
                length: length,
                separator: /,? +/,
            });
        },

        /**
         * Creates a debounced function that delays invoking a callback.
         */
        debouncer: _.debounce(callback => callback(), 500),

        /**
         * Show an error message.
         */
        alertError(message) {
            this.$root.alert.type = 'error';
            this.$root.alert.autoClose = false;
            this.$root.alert.message = message;
        },

        /**
         * Show a success message.
         */
        alertSuccess(message, autoClose) {
            this.$root.alert.type = 'success';
            this.$root.alert.autoClose = autoClose;
            this.$root.alert.message = message;
        },

        /**
         * Show confirmation message.
         */
        alertConfirm(message, success, failure) {
            this.$root.alert.type = 'confirmation';
            this.$root.alert.autoClose = false;
            this.$root.alert.message = message;
            this.$root.alert.confirmationProceed = success;
            this.$root.alert.confirmationCancel = failure;
        },
    },
};
