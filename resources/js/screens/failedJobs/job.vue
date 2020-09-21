<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'

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
                retrying: false,
                job: {}
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadFailedJob(this.$route.params.jobId);

            document.title = "Horizon - Failed Jobs";

            this.interval = setInterval(() => {
                this.reloadRetries();
            }, 3000);
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearInterval(this.interval);
        },


        methods: {
            loadFailedJob(id) {
                this.ready = false;

                this.$http.get(Horizon.basePath + '/api/jobs/failed/' + id)
                    .then(response => {
                        this.job = response.data;

                        this.ready = true;
                    });
            },


            /**
             * Reload the job retries.
             */
            reloadRetries() {
                this.$http.get(Horizon.basePath + '/api/jobs/failed/' + this.$route.params.jobId)
                    .then(response => {
                        this.job.retried_by = response.data.retried_by;

                    });
            },


            /**
             * Retry the given failed job.
             */
            retry(id) {
                if (this.retrying) {
                    return;
                }

                this.retrying = true;

                this.$http.post(Horizon.basePath + '/api/jobs/retry/' + id)
                    .then(() => {
                        setTimeout(() => {
                            this.reloadRetries();

                            this.retrying = false;
                        }, 3000);
                    });
            },


            /**
             * Convert exception to a more readable format.
             */
            prettyPrintException(exception) {
                var lines = _.split(exception, "\n"),
                    output = '';

                lines.forEach(line => {
                    output += '<span>' + line + '</span>';
                });

                return output;
            },


            /**
             * Pretty print serialized job.
             *
             * @param data
             * @returns {string}
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
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 v-if="!ready">Job Preview</h5>
                <h5 v-if="ready">{{job.name}}</h5>

                <button class="btn btn-outline-primary" v-on:click.prevent="retry(job.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary" :class="{spin: retrying}">
                        <path d="M10 3v2a5 5 0 0 0-3.54 8.54l-1.41 1.41A7 7 0 0 1 10 3zm4.95 2.05A7 7 0 0 1 10 17v-2a5 5 0 0 0 3.54-8.54l1.41-1.41zM10 20l-4-4 4-4v8zm0-12V0l4 4-4 4z"/>
                    </svg>
                </button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary" v-if="ready">
                <div class="row mb-2">
                    <div class="col-md-2"><strong>ID</strong></div>
                    <div class="col">{{job.id}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Queue</strong></div>
                    <div class="col">{{job.queue}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Attempts</strong></div>
                    <div class="col">{{job.payload.attempts}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Retries</strong></div>
                    <div class="col">{{job.retried_by.length}}</div>
                </div>
                <div class="row mb-2" v-if="job.payload.retry_of">
                    <div class="col-md-2"><strong>Retry of ID</strong></div>
                    <div class="col">
                         <a :href="Horizon.basePath + '/failed/' + job.payload.retry_of">
                            {{ job.payload.retry_of }}
                        </a>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Tags</strong></div>
                    <div class="col">{{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}</div>
                </div>
                <div class="row mb-2" v-if="prettyPrintJob(job.payload.data).batchId">
                    <div class="col-md-2"><strong>Batch</strong></div>
                    <div class="col">
                        <router-link :to="{ name: 'batches-preview', params: { batchId: prettyPrintJob(job.payload.data).batchId }}">
                            {{ prettyPrintJob(job.payload.data).batchId }}
                        </router-link>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2"><strong>Failed At</strong></div>
                    <div class="col">{{readableTimestamp(job.failed_at)}}</div>
                </div>
            </div>
        </div>

        <div class="card mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Exception</h5>
            </div>
            <div>
                <stack-trace :trace="job.exception.split('\n')"></stack-trace>
            </div>
        </div>


        <div class="card mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Data</h5>
            </div>

            <div class="card-body code-bg text-white">
                <vue-json-pretty :data="prettyPrintJob(job.payload.data)"></vue-json-pretty>
            </div>
        </div>

        <div class="card mt-4" v-if="ready && job.retried_by.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Recent Retries</h5>
            </div>

            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Job</th>
                    <th>ID</th>
                    <th class="text-right">Retry Time</th>
                </tr>
                </thead>

                <tbody>

                <tr v-for="retry in job.retried_by">
                    <td>
                        <svg v-if="retry.status == 'completed'" class="fill-success" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"></path>
                        </svg>

                        <svg v-if="retry.status == 'reserved' || retry.status == 'pending'" class="fill-warning" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM7 6h2v8H7V6zm4 0h2v8h-2V6z"/>
                        </svg>

                        <svg v-if="retry.status == 'failed'" class="fill-danger" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/>
                        </svg>

                        <span class="ml-2">{{ retry.status.charAt(0).toUpperCase() + retry.status.slice(1) }}</span>
                    </td>

                    <td class="table-fit">
                        <a v-if="retry.status == 'failed'" :href="Horizon.basePath + '/failed/'+retry.id">
                            {{ retry.id }}
                        </a>
                        <span v-else>{{ retry.id }}</span>
                    </td>

                    <td class="text-right table-fit">
                        {{readableTimestamp(retry.retried_at)}}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>
