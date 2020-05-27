<script type="text/ecmascript-6">
    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                retrying: false,
                batch: {},
                failedJobs : []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadBatch();

            document.title = "Horizon - Batches";

            this.interval = setInterval(() => {
                this.loadBatch(false);
            }, 3000);
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearInterval(this.interval);
        },


        methods: {
            loadBatch(reload = true) {
                if (reload) {
                    this.ready = false;
                }

                this.$http.get(Horizon.basePath + '/api/batches/' + this.$route.params.batchId)
                    .then(response => {
                        this.batch = response.data.batch;
                        this.failedJobs = response.data.failedJobs;

                        this.ready = true;
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

                this.$http.post(Horizon.basePath + '/api/batches/retry/' + id)
                    .then(() => {
                        setTimeout(() => {
                            this.loadBatch(false);

                            this.retrying = false;
                        }, 3000);
                    });
            },

        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 v-if="!ready">Batch Preview</h5>
                <h5 v-if="ready">{{batch.name || batch.id}}</h5>

                <button class="btn btn-outline-primary" v-on:click.prevent="retry(batch.id)">
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
                    <div class="col">
                        {{batch.id}}

                        <small class="badge badge-danger badge-sm" v-if="batch.failedJobs > 0 && batch.progress < 100">
                            Failures
                        </small>
                        <small class="badge badge-success badge-sm" v-if="batch.progress == 100">
                            Finished
                        </small>
                        <small class="badge badge-secondary badge-sm" v-if="batch.pendingJobs > 0 && !batch.failedJobs">
                            Pending
                        </small>
                    </div>
                </div>
                <div class="row mb-2" v-if="batch.name">
                    <div class="col-md-2"><strong>Name</strong></div>
                    <div class="col">{{batch.name}}</div>
                </div>
                <div class="row mb-2" v-if="batch.options.queue">
                    <div class="col-md-2"><strong>Queue</strong></div>
                    <div class="col">{{batch.options.queue}}</div>
                </div>
                <div class="row mb-2" v-if="batch.options.connection">
                    <div class="col-md-2"><strong>Connection</strong></div>
                    <div class="col">{{batch.options.connection}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Created At</strong></div>
                    <div class="col">{{ formatDateIso(batch.createdAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div class="row mb-2" v-if="batch.finishedAt">
                    <div class="col-md-2"><strong>Finished At</strong></div>
                    <div class="col">{{ formatDateIso(batch.finishedAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div class="row mb-2" v-if="batch.cancelledAt">
                    <div class="col-md-2"><strong>Cancelled At</strong></div>
                    <div class="col">{{ formatDateIso(batch.cancelledAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Total Jobs</strong></div>
                    <div class="col">{{batch.totalJobs}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Pending Jobs</strong></div>
                    <div class="col">{{batch.pendingJobs}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Failed Jobs</strong></div>
                    <div class="col">{{batch.failedJobs}}</div>
                </div>
                <div class="row">
                    <div class="col-md-2"><strong>Completion</strong></div>
                    <div class="col">{{batch.progress}}%</div>
                </div>
            </div>
        </div>


        <div class="card mt-4" v-if="ready && failedJobs.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Failed Jobs</h5>
            </div>

            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Job</th>
                    <th>Runtime</th>
                    <th class="text-right">Failed At</th>
                </tr>
                </thead>

                <tbody>

                <tr v-for="failedJob in failedJobs">
                    <td>
                        <router-link :to="{ name: 'failed-jobs-preview', params: { jobId: failedJob.id }}">
                            {{ jobBaseName(failedJob.name) }}
                        </router-link>
                    </td>

                    <td class="table-fit">
                        <span>{{ failedJob.failed_at ? String((failedJob.failed_at - failedJob.reserved_at).toFixed(2))+'s' : '-' }}</span>
                    </td>

                    <td class="text-right table-fit">
                        {{ readableTimestamp(failedJob.failed_at) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>
