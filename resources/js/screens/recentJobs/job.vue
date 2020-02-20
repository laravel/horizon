<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 v-if="!ready">Job Preview</h5>
                <h5 v-if="ready">{{job.name}}</h5>

                <a data-toggle="collapse" href="#collapseDetails" role="button">
                    Collapse
                </a>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary collapse show" id="collapseDetails" v-if="ready">
                <div class="row mb-2">
                    <div class="col-md-2"><strong>ID</strong></div>
                    <div class="col">{{job.id}}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2"><strong>Queue</strong></div>
                    <div class="col">{{job.queue}}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2"><strong>Pushed At</strong></div>
                    <div class="col">{{ readableTimestamp(job.payload.pushedAt) }}</div>
                </div>

                <div class="row mb-2" v-if="delayed">
                    <div class="col-md-2"><strong>Delayed Until</strong></div>
                    <div class="col">{{delayed}}</div>
                </div>

                <div class="row">
                    <div class="col-md-2"><strong>Completed At</strong></div>
                    <div class="col" v-if="job.completed_at">{{readableTimestamp(job.completed_at)}}</div>
                    <div class="col" else>-</div>
                </div>
            </div>
        </div>


        <div class="card mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Data</h5>

                <a data-toggle="collapse" href="#collapseData" role="button">
                    Collapse
                </a>
            </div>

            <div class="card-body code-bg text-white collapse show" id="collapseData">
                <vue-json-pretty :data="prettyPrintJob(job.payload.data)"></vue-json-pretty>
            </div>
        </div>

        <div class="card mt-4" v-if="ready && job.payload.tags.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Tags</h5>

                <a data-toggle="collapse" href="#collapseTags" role="button">
                    Collapse
                </a>
            </div>

            <div class="card-body code-bg text-white collapse show" id="collapseTags">
                <vue-json-pretty :data="job.payload.tags"></vue-json-pretty>
            </div>
        </div>
    </div>
</template>

<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'
    import moment from "moment-timezone";

    export default {
        components: {
            'stack-trace': require('./../../components/Stacktrace').default
        },

        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                job: {}
            };
        },

        computed: {
            unserialized() {
                return phpunserialize(this.job.payload.data.command);
            },

            delayed() {
                let unserialized = phpunserialize(this.job.payload.data.command);

                if (unserialized && unserialized.delay) {
                    return moment.tz(unserialized.delay.date, unserialized.delay.timezone)
                        .local()
                        .format('YYYY-MM-DD HH:mm:ss');
                }

                return null;
            },
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadJob(this.$route.params.jobId);

            document.title = "Horizon - Job Detail";
        },

        methods: {
            /**
             * Load a job by the given ID.
             */
            loadJob(id) {
                this.ready = false;

                this.$http.get(Horizon.basePath + '/api/jobs/recent/' + id)
                    .then(response => {
                        this.job = response.data;

                        this.ready = true;
                    });
            },

            /**
             * Pretty print serialized job.
             */
            prettyPrintJob(data) {
                return data.command && !data.command.includes('CallQueuedClosure')
                    ? phpunserialize(data.command) : data;
            }
        }
    }
</script>
