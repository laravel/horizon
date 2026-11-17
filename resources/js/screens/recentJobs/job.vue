<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0" v-if="!ready">Job Preview</h2>
                <h2 class="h6 m-0" v-if="ready">{{job.name}}</h2>

                <div class="d-flex align-items-center">
                    <template v-if="ready && job.status == 'pending'">
                        <button v-if="delayed" class="btn btn-primary btn-sm me-2" :disabled="busy" @click.prevent="runNow">
                            Run Now
                        </button>

                        <button v-if="! batchId" class="btn btn-outline-danger btn-sm me-3" :disabled="busy" @click.prevent="deleteJob">
                            Delete
                        </button>
                    </template>

                    <a data-bs-toggle="collapse" href="#collapseDetails" role="button">
                        Collapse
                    </a>
                </div>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary collapse show" id="collapseDetails" v-if="ready">
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">ID</div>
                    <div class="col">{{job.id}}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Connection</div>
                    <div class="col">{{job.connection}}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Queue</div>
                    <div class="col">{{job.queue}}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Pushed</div>
                    <div class="col">{{ readableTimestamp(job.payload.pushedAt) }}</div>
                </div>

                <div class="row mb-2" v-if="prettyPrintJob(job.payload.data).batchId">
                    <div class="col-md-2 text-muted">Batch</div>
                    <div class="col">
                        <router-link :to="{ name: 'batches-preview', params: { batchId: prettyPrintJob(job.payload.data).batchId }}">
                            {{ prettyPrintJob(job.payload.data).batchId }}
                        </router-link>
                    </div>
                </div>

                <div class="row mb-2" v-if="delayed">
                    <div class="col-md-2 text-muted">Delayed Until</div>
                    <div class="col">{{delayed}}</div>
                </div>

                <div class="row">
                    <div class="col-md-2 text-muted">Completed</div>
                    <div class="col" v-if="job.completed_at">{{readableTimestamp(job.completed_at)}}</div>
                    <div class="col" v-else>-</div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Data</h2>

                <a data-bs-toggle="collapse" href="#collapseData" role="button">
                    Collapse
                </a>
            </div>

            <div class="card-body code-bg text-white collapse show" id="collapseData">
                <vue-json-pretty :data="prettyPrintJob(job.payload.data)"></vue-json-pretty>
            </div>
        </div>

        <div class="card overflow-hidden mt-4" v-if="ready && job.payload.tags.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Tags</h2>

                <a data-bs-toggle="collapse" href="#collapseTags" role="button">
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
                busy: false,
                job: {}
            };
        },

        computed: {
            unserialized() {
                return phpunserialize(this.job.payload.data.command);
            },

            batchId() {
                return this.prettyPrintJob(this.job.payload.data).batchId;
            },

            delayed() {
                let unserialized;

                try {
                    unserialized = phpunserialize(this.job.payload.data.command);
                }catch(err){
                    //
                }

                if (!unserialized || !unserialized.delay) {
                    return null;
                }

                if (unserialized.delay.date) {
                    return moment.tz(unserialized.delay.date, unserialized.delay.timezone)
                        .local()
                        .format('YYYY-MM-DD HH:mm:ss');
                }

                if (typeof unserialized.delay === 'object') {
                    // A DateInterval / CarbonInterval instance serializes with
                    // y/m/d/h/i/s keys, which don't line up with moment's own
                    // shorthand unit keys (e.g. its "m" means minutes, not
                    // months), so they must be mapped explicitly.
                    return this.formatDate(this.job.payload.pushedAt).add({
                        years: unserialized.delay.y,
                        months: unserialized.delay.m,
                        days: unserialized.delay.d,
                        hours: unserialized.delay.h,
                        minutes: unserialized.delay.i,
                        seconds: unserialized.delay.s,
                    })
                        .local()
                        .format('YYYY-MM-DD HH:mm:ss');
                }

                return this.formatDate(this.job.payload.pushedAt).add(unserialized.delay, 'seconds')
                    .local()
                    .format('YYYY-MM-DD HH:mm:ss');
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
             * Push the delayed job onto its queue so it runs immediately.
             */
            runNow() {
                this.busy = true;

                this.$http.post(Horizon.basePath + '/api/jobs/pending/' + this.job.id + '/run')
                    .then(() => this.loadJob(this.job.id))
                    .catch(error => this.showError(error))
                    .finally(() => this.busy = false);
            },


            /**
             * Delete the pending job from its queue.
             */
            deleteJob() {
                this.$root.alert.type = 'confirmation';
                this.$root.alert.message = 'Are you sure you want to delete this job? It will be removed from the queue and will not be processed.';
                this.$root.alert.confirmationProceed = () => {
                    this.busy = true;

                    this.$http.delete(Horizon.basePath + '/api/jobs/pending/' + this.job.id)
                        .then(() => this.$router.push({ name: 'jobs', params: { type: 'pending' } }))
                        .catch(error => this.showError(error))
                        .finally(() => this.busy = false);
                };
            },


            /**
             * Show the error returned by the given failed request.
             */
            showError(error) {
                this.$root.alert.type = 'error';
                this.$root.alert.message = (error.response && error.response.data && error.response.data.message)
                    || 'Something went wrong.';

                this.loadJob(this.job.id);
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
