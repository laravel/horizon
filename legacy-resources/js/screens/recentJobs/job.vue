<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize';
    import moment from 'moment-timezone';
    import StackTrace from './../../components/Stacktrace.vue';

    export default {
        components: {
            'stack-trace': StackTrace,
        },

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
                let unserialized;

                try {
                    unserialized = phpunserialize(this.job.payload.data.command);
                }catch(err){
                    //
                }

                if (unserialized && unserialized.delay && unserialized.delay.date) {
                    return moment.tz(unserialized.delay.date, unserialized.delay.timezone)
                        .local()
                        .format('YYYY-MM-DD HH:mm:ss');
                } else if (unserialized && unserialized.delay) {
                    return this.formatDate(this.job.payload.pushedAt).add(unserialized.delay, 'seconds')
                        .local()
                        .format('YYYY-MM-DD HH:mm:ss');
                }

                return null;
            },
        },

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

                this.$http.get(Horizon.basePath + '/api/jobs/' + id)
                    .then(response => {
                        this.job = response.data;

                        this.ready = true;
                    });
            },

            /**
             * Pretty print serialized job.
             */
            prettyPrintJob(data) {
                try {
                    return data.command && !data.command.includes('CallQueuedClosure')
                        ? phpunserialize(data.command) : data;
                } catch (err) {
                    return data;
                }
            }
        }
    }
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 v-if="!ready" class="h6 m-0">Job Preview</h2>
                <h2 v-if="ready" class="h6 m-0">{{job.name}}</h2>

                <a data-bs-toggle="collapse" href="#collapseDetails" role="button">
                    Collapse
                </a>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div v-if="ready" id="collapseDetails" class="card-body card-bg-secondary collapse show">
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">ID</div>
                    <div class="col">{{job.id}}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Queue</div>
                    <div class="col">{{job.queue}}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Pushed</div>
                    <div class="col">{{ readableTimestamp(job.payload.pushedAt) }}</div>
                </div>

                <div v-if="prettyPrintJob(job.payload.data).batchId" class="row mb-2">
                    <div class="col-md-2 text-muted">Batch</div>
                    <div class="col">
                        <router-link :to="{ name: 'batches-preview', params: { batchId: prettyPrintJob(job.payload.data).batchId }}">
                            {{ prettyPrintJob(job.payload.data).batchId }}
                        </router-link>
                    </div>
                </div>

                <div v-if="delayed" class="row mb-2">
                    <div class="col-md-2 text-muted">Delayed Until</div>
                    <div class="col">{{delayed}}</div>
                </div>

                <div class="row">
                    <div class="col-md-2 text-muted">Completed</div>
                    <div v-if="job.completed_at" class="col">{{readableTimestamp(job.completed_at)}}</div>
                    <div v-else class="col">-</div>
                </div>
            </div>
        </div>

        <div v-if="ready" class="card overflow-hidden mt-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Data</h2>

                <a data-bs-toggle="collapse" href="#collapseData" role="button">
                    Collapse
                </a>
            </div>

            <div id="collapseData" class="card-body code-bg text-white collapse show">
                <vue-json-pretty :data="prettyPrintJob(job.payload.data)"></vue-json-pretty>
            </div>
        </div>

        <div v-if="ready && job.payload.tags.length" class="card overflow-hidden mt-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Tags</h2>

                <a data-bs-toggle="collapse" href="#collapseTags" role="button">
                    Collapse
                </a>
            </div>

            <div id="collapseTags" class="card-body code-bg text-white collapse show">
                <vue-json-pretty :data="job.payload.tags"></vue-json-pretty>
            </div>
        </div>
    </div>
</template>
