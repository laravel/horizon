<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'
    import EmptyState from '@/components/EmptyState.vue'
    import StackTrace from '@/components/Stacktrace.vue'

    export default {
        components: {
            EmptyState,
            'stack-trace': StackTrace,
        },


        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                retrying: false,
                exceptionExpanded: true,
                exceptionContextExpanded: true,
                dataExpanded: true,
                job: {}
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadFailedJob(this.$route.params.jobId);

            document.title = "Horizon - Failed Jobs";
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
                if (!this.job.id) {
                    return;
                }

                this.$http.get(Horizon.basePath + '/api/jobs/failed/' + this.$route.params.jobId)
                    .then(response => {
                        if (!response.data || !response.data.id) {
                            this.job = response.data;

                            return;
                        }

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
        <poll @poll="reloadRetries" :immediate="false" />

        <div class="card overflow-hidden">
            <div class="card-header job-detail-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0 job-detail-title" v-if="!ready || !job.id">Job Preview</h2>
                <h2 class="h6 m-0 job-detail-title" v-if="ready && job.id" :title="job.name">{{job.name}}</h2>

                <button class="btn btn-primary" v-if="ready && job.id" v-on:click.prevent="retry(job.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon me-2" fill="currentColor" :class="{spin: retrying}">
                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                    </svg>

                    Retry
                </button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <empty-state
                v-if="ready && !job.id"
                title="Job no longer available"
                description="This job may have been trimmed from Horizon."
            ></empty-state>

            <dl class="job-detail-properties" v-if="ready && job.id">
                <div class="job-detail-property">
                    <dt>ID</dt>
                    <dd :title="job.id">{{job.id}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Connection</dt>
                    <dd>{{job.connection}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Queue</dt>
                    <dd>{{job.queue}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Attempts</dt>
                    <dd>{{job.payload.attempts}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Retries</dt>
                    <dd>{{job.retried_by.length}}</dd>
                </div>

                <div class="job-detail-property" v-if="job.payload.retry_of">
                    <dt>Retry of ID</dt>
                    <dd>
                        <a :href="Horizon.basePath + '/failed/' + job.payload.retry_of">
                            {{ job.payload.retry_of }}
                        </a>
                    </dd>
                </div>

                <div class="job-detail-property">
                    <dt>Tags</dt>
                    <dd>{{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}</dd>
                </div>

                <div class="job-detail-property" v-if="prettyPrintJob(job.payload.data).batchId">
                    <dt>Batch</dt>
                    <dd>
                        <router-link :to="{ name: 'batches-preview', params: { batchId: prettyPrintJob(job.payload.data).batchId }}">
                            {{ prettyPrintJob(job.payload.data).batchId }}
                        </router-link>
                    </dd>
                </div>

                <div class="job-detail-property">
                    <dt>Pushed</dt>
                    <dd>{{ readableTimestamp(job.payload.pushedAt) }}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Failed</dt>
                    <dd>{{readableTimestamp(job.failed_at)}}</dd>
                </div>
            </dl>
        </div>

        <div class="card overflow-hidden mt-3" v-if="ready && job.id">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Exception</h2>

                <button
                    type="button"
                    class="job-detail-collapse"
                    :aria-expanded="exceptionExpanded"
                    aria-controls="collapseException"
                    @click="exceptionExpanded = !exceptionExpanded"
                >
                    {{ exceptionExpanded ? 'Collapse' : 'Expand' }}
                </button>
            </div>
            <div id="collapseException" v-show="exceptionExpanded">
                <stack-trace :trace="job.exception.split('\n')"></stack-trace>
            </div>
        </div>

        <div class="card overflow-hidden mt-3" v-if="ready && job.id">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Exception Context</h2>

                <button
                    type="button"
                    class="job-detail-collapse"
                    :aria-expanded="exceptionContextExpanded"
                    aria-controls="collapseExceptionContext"
                    @click="exceptionContextExpanded = !exceptionContextExpanded"
                >
                    {{ exceptionContextExpanded ? 'Collapse' : 'Expand' }}
                </button>
            </div>

            <div class="card-body code-bg text-white job-detail-code" id="collapseExceptionContext" v-show="exceptionContextExpanded">
                <vue-json-pretty :data="prettyPrintJob(job.context)"></vue-json-pretty>
            </div>
        </div>


        <div class="card overflow-hidden mt-3" v-if="ready && job.id">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Data</h2>

                <button
                    type="button"
                    class="job-detail-collapse"
                    :aria-expanded="dataExpanded"
                    aria-controls="collapseData"
                    @click="dataExpanded = !dataExpanded"
                >
                    {{ dataExpanded ? 'Collapse' : 'Expand' }}
                </button>
            </div>

            <div class="card-body code-bg text-white job-detail-code" id="collapseData" v-show="dataExpanded">
                <vue-json-pretty :data="prettyPrintJob(job.payload.data)"></vue-json-pretty>
            </div>
        </div>

        <div class="card overflow-hidden horizon-table-card mt-3" v-if="ready && job.id && job.retried_by.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Recent Retries</h2>
            </div>

            <table class="table table-hover mb-0 horizon-table">
                <thead>
                <tr>
                    <th class="table-fit">Status</th>
                    <th>ID</th>
                    <th class="text-end table-fit">Retry Time</th>
                </tr>
                </thead>

                <tbody>

                <tr v-for="retry in job.retried_by">
                    <td class="table-fit">
                        <small class="badge badge-success badge-sm rounded-pill" v-if="retry.status == 'completed'">
                            Completed
                        </small>
                        <small class="badge badge-danger badge-sm rounded-pill" v-if="retry.status == 'failed'">
                            Failed
                        </small>
                        <small class="badge badge-info badge-sm rounded-pill" v-if="retry.status == 'reserved'">
                            Reserved
                        </small>
                        <small class="badge badge-warning badge-sm rounded-pill" v-if="retry.status == 'pending'">
                            Pending
                        </small>
                    </td>

                    <td :class="{ 'horizon-linked-cell': retry.status == 'failed' }">
                        <a v-if="retry.status == 'failed'" class="horizon-row-link horizon-cell-link" :href="Horizon.basePath + '/failed/'+retry.id">
                            {{ retry.id }}
                        </a>
                        <span v-else>{{ retry.id }}</span>
                    </td>

                    <td class="text-end table-fit text-muted">
                        {{readableTimestamp(retry.retried_at)}}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>
