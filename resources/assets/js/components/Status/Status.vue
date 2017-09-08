<script type="text/ecmascript-6">
    import PauseQueue from './PauseQueue.vue';
    import ResumeQueue from './ResumeQueue.vue';

    export default {
        components: {
            PauseQueue: require('./PauseQueue'),
            ResumeQueue: require('./ResumeQueue'),
        },

        props: {
            status: {},
        },

        computed: {
            isPaused() {
                return this.status == 'paused';
            },

            isRunning() {
                return this.status == 'running';
            },


            isInactive() {
                return !this.isPaused && !this.isRunning;
            },

            statusText() {
                let status = {
                    running: 'Running',
                    paused: 'Paused',
                }[this.status];

                if(status) {
                    return status;
                }

                return 'Inactive';
            },
        },
    }
</script>

<template>

    <div>
        <pause-queue v-if="isRunning">
            <i class="ico20">
                <svg class="fcc fsuccess">
                    <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-checkmark-outline"></use>
                </svg>
            </i>
        </pause-queue>


        <resume-queue v-else-if="isPaused">
            <i class="ico20">
                <svg class="fcc fwarning">
                    <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-pause-outline"></use>
                </svg>
            </i>
        </resume-queue>

        <i class="ico20" v-else>
            <svg class="fcc fdanger">
                <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-close-outline"></use>
            </svg>
        </i>

        <span class="stat-value">
            {{statusText}}
        </span>
    </div>

</template>
