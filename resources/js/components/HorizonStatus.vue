<script>
    import Tooltip from './Tooltip.vue';

    export default {
        components: {
            Tooltip,
        },

        props: {
            status: {
                type: String,
                default: 'unavailable',
            },

            processing: {
                type: Boolean,
                default: false,
            },

            showTooltip: {
                type: Boolean,
                default: true,
            },
        },

        computed: {
            currentStatus() {
                return ['running', 'paused', 'inactive'].includes(this.status)
                    ? this.status
                    : 'unavailable';
            },

            isProcessing() {
                return this.currentStatus === 'running' && this.processing;
            },

            label() {
                if (this.isProcessing) {
                    return 'Processing jobs';
                }

                return {
                    running: 'Idle, no jobs to process',
                    paused: 'Horizon is paused',
                    inactive: 'Horizon is inactive',
                    unavailable: 'Horizon is unavailable',
                }[this.currentStatus];
            },

            tooltipLabel() {
                if (this.currentStatus === 'running') {
                    return this.label;
                }

                return {
                    paused: 'Paused',
                    inactive: 'Inactive — run php artisan horizon',
                    unavailable: 'Unavailable',
                }[this.currentStatus];
            },
        },
    };
</script>

<template>
    <tooltip class="horizon-status-wrapper" :disabled="!showTooltip">
        <template #default="{ tooltipId }">
            <span
                class="horizon-status"
                :class="'horizon-status-' + currentStatus"
                :data-activity="currentStatus === 'running' ? (isProcessing ? 'working' : 'idle') : undefined"
                :aria-label="label"
                :aria-describedby="tooltipId"
                :tabindex="showTooltip ? 0 : undefined"
                role="status"
            >
                <svg
                    v-if="currentStatus === 'running'"
                    class="horizon-status-spinner"
                    viewBox="0 0 16 16"
                    fill="none"
                    aria-hidden="true"
                >
                    <circle class="horizon-status-track" cx="8" cy="8" r="6.5" stroke-width="2.5" />
                    <circle
                        class="horizon-status-progress"
                        cx="8"
                        cy="8"
                        r="6.5"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-dasharray="40.84"
                        stroke-dashoffset="30.63"
                        transform="rotate(-90 8 8)"
                    />
                </svg>

                <svg v-else-if="currentStatus === 'paused'" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7.75 6.5a.75.75 0 00-.75.75v5.5a.75.75 0 001.5 0v-5.5a.75.75 0 00-.75-.75zm4.5 0a.75.75 0 00-.75.75v5.5a.75.75 0 001.5 0v-5.5a.75.75 0 00-.75-.75z" clip-rule="evenodd" />
                </svg>

                <svg v-else-if="currentStatus === 'inactive'" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 1.75a.75.75 0 01.75.75v6a.75.75 0 01-1.5 0v-6a.75.75 0 01.75-.75zM6.43 4.35a.75.75 0 01-.04 1.06 6 6 0 108.22 0 .75.75 0 111.02-1.1 7.5 7.5 0 11-10.26 0 .75.75 0 011.06.04z" clip-rule="evenodd" />
                </svg>

                <svg v-else viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.26 3.1c.77-1.33 2.7-1.33 3.48 0l6.05 10.48c.77 1.34-.2 3.02-1.74 3.02H3.95c-1.54 0-2.51-1.68-1.74-3.02L8.26 3.1zM10 7a.75.75 0 01.75.75v3a.75.75 0 01-1.5 0v-3A.75.75 0 0110 7zm0 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </span>
        </template>

        <template #content>
            {{ tooltipLabel }}
        </template>
    </tooltip>
</template>
