<script type="text/ecmascript-6">
    import EmptyState from '../../components/EmptyState.vue';

    export default {
        components: {
            EmptyState,
        },

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
            document.title = "Horizon - Batches";
        },


        methods: {
            loadBatch(reload = true) {
                if (reload) {
                    this.ready = false;
                }

                this.$http.get(Horizon.basePath + '/api/batches/' + this.$route.params.batchId)
                    .then(response => {
                        this.batch = response.data.batch;
                        this.failedJobs = response.data.failedJobs || [];

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
        <poll @poll="loadBatch(false)" />

        <div class="card overflow-hidden">
            <div class="card-header job-detail-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0 job-detail-title" v-if="!ready || !batch">Batch Preview</h2>
                <h2 class="h6 m-0 job-detail-title" v-if="ready && batch" :title="batch.name || batch.id">{{batch.name || batch.id}}</h2>

                <button class="btn btn-primary" v-if="batch && failedJobs.length > 0" v-on:click.prevent="retry(batch.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon me-2" fill="currentColor" :class="{spin: retrying}">
                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                    </svg>

                    Retry Failed Jobs
                </button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <empty-state
                v-if="ready && !batch"
                title="Batch no longer available"
                description="This batch may have been pruned from the application database."
                icon="batches"
            ></empty-state>

            <dl class="job-detail-properties" v-if="ready && batch">
                <div class="job-detail-property">
                    <dt>ID</dt>
                    <dd :title="batch.id">
                        {{batch.id}}

                        <small class="ms-1 badge badge-danger badge-sm rounded-pill" v-if="!batch.cancelledAt && batch.failedJobs > 0 && batch.totalJobs - batch.pendingJobs < batch.totalJobs">
                            Failures
                        </small>
                        <small class="ms-1 badge badge-success badge-sm rounded-pill" v-if="!batch.cancelledAt && batch.totalJobs - batch.pendingJobs == batch.totalJobs">
                            Finished
                        </small>
                        <small class="ms-1 badge badge-secondary badge-sm rounded-pill" v-if="!batch.cancelledAt && batch.pendingJobs > 0 && !batch.failedJobs">
                            Pending
                        </small>
                        <small class="ms-1 badge badge-warning badge-sm rounded-pill" v-if="batch.cancelledAt">
                            Cancelled
                        </small>
                    </dd>
                </div>

                <div class="job-detail-property" v-if="batch.name">
                    <dt>Name</dt>
                    <dd>{{batch.name}}</dd>
                </div>

                <div class="job-detail-property" v-if="batch.options.queue">
                    <dt>Queue</dt>
                    <dd>{{batch.options.queue}}</dd>
                </div>

                <div class="job-detail-property" v-if="batch.options.connection">
                    <dt>Connection</dt>
                    <dd>{{batch.options.connection}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Created</dt>
                    <dd>{{ formatDateIso(batch.createdAt).format('YYYY-MM-DD HH:mm:ss') }}</dd>
                </div>

                <div class="job-detail-property" v-if="batch.finishedAt">
                    <dt>Finished</dt>
                    <dd>{{ formatDateIso(batch.finishedAt).format('YYYY-MM-DD HH:mm:ss') }}</dd>
                </div>

                <div class="job-detail-property" v-if="batch.cancelledAt">
                    <dt>Cancelled</dt>
                    <dd>{{ formatDateIso(batch.cancelledAt).format('YYYY-MM-DD HH:mm:ss') }}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Total Jobs</dt>
                    <dd>{{batch.totalJobs}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Pending Jobs</dt>
                    <dd>{{batch.pendingJobs}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Failed Jobs</dt>
                    <dd>{{batch.failedJobs}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Processed Jobs</dt>
                    <dd>{{ batch.processedJobs }} ({{batch.progress}}%)</dd>
                </div>
            </dl>
        </div>

        <div class="card overflow-hidden horizon-table-card mt-3" v-if="ready && batch && failedJobs.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Failed Jobs</h2>
            </div>

            <table class="table table-hover mb-0 horizon-table">
                <thead>
                <tr>
                    <th>Job</th>
                    <th class="text-end">Runtime</th>
                    <th class="text-end">Failed</th>
                </tr>
                </thead>

                <tbody>

                <tr v-for="failedJob in failedJobs">
                    <td class="horizon-linked-cell">
                        <router-link class="horizon-row-link horizon-cell-link" :title="failedJob.name" :to="{ name: 'failed-jobs-preview', params: { jobId: failedJob.id }}">
                            {{ jobBaseName(failedJob.name) }}
                        </router-link>
                    </td>

                    <td class="text-end text-muted table-fit">
                        <span>{{ failedJob.failed_at && failedJob.reserved_at ? String(( failedJob.failed_at - failedJob.reserved_at ).toFixed(2))+'s' : '-' }}</span>
                    </td>

                    <td class="text-end text-muted table-fit">
                        {{ readableTimestamp(failedJob.failed_at) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>
