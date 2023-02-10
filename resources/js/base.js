import moment from 'moment-timezone';

export default {
    computed: {
        Horizon() {
            return Horizon;
        },
    },

    methods: {
        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime) {
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60);
        },

        /**
         * Format the given date with respect to timezone.
         */
        formatDateIso(date) {
            return moment(date).add(new Date().getTimezoneOffset() / 60);
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
         * Uppercase the first character of the string.
         */
        upperFirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },

        /**
         * Group array entries by a given key.
         */
        groupBy(array, key) {
            return array.reduce(
                (grouped, entry) => ({
                    ...grouped,
                    [entry[key]]: [...(grouped[entry[key]] || []), entry],
                }),
                {}
            );
        },
    },
};
