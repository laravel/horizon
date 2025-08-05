import moment from 'moment-timezone';
import type { ComponentOptionsMixin } from 'vue';

interface BaseMixinData {
    autoLoadsNewEntries?: boolean;
}

const baseMixin: ComponentOptionsMixin = {
    computed: {
        Horizon() {
            return window.Horizon;
        },
    },

    methods: {
        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime: number): moment.Moment {
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60);
        },

        /**
         * Format the given date with respect to timezone.
         */
        formatDateIso(date: string | Date): moment.Moment {
            return moment(date).add(new Date().getTimezoneOffset() / 60);
        },

        /**
         * Extract the job base name.
         */
        jobBaseName(name: string): string {
            if (!name.includes('\\')) return name;

            const parts = name.split('\\');

            return parts[parts.length - 1];
        },

        /**
         * Autoload new entries in listing screens.
         */
        autoLoadNewEntries(this: BaseMixinData): void {
            if (!this.autoLoadsNewEntries) {
                this.autoLoadsNewEntries = true;
                localStorage.autoLoadsNewEntries = '1';
            } else {
                this.autoLoadsNewEntries = false;
                localStorage.autoLoadsNewEntries = '0';
            }
        },

        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp: number): string {
            return this.formatDate(timestamp).format('YYYY-MM-DD HH:mm:ss');
        },

        /**
         * Uppercase the first character of the string.
         */
        upperFirst(string: string): string {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },

        /**
         * Group array entries by a given key.
         */
        groupBy<T extends Record<string, any>>(array: T[], key: keyof T): Record<string, T[]> {
            return array.reduce(
                (grouped, entry) => ({
                    ...grouped,
                    [entry[key] as string]: [...(grouped[entry[key] as string] || []), entry],
                }),
                {} as Record<string, T[]>
            );
        },
    },
};

export default baseMixin;