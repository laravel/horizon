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
            if (!this.$root.autoLoadsNewEntries) {
                this.$root.autoLoadsNewEntries = true;
                localStorage.autoLoadsNewEntries = 1;
            } else {
                this.$root.autoLoadsNewEntries = false;
                localStorage.autoLoadsNewEntries = 0;
            }
        },

        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp) {
            return this.formatDate(timestamp).format('YYYY-MM-DD HH:mm:ss');
        },
    },
};
