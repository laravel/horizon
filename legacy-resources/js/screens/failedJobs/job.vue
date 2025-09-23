<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'
    import StackTrace from '@/components/Stacktrace.vue'

    export default {
        components: {
            'stack-trace': StackTrace,
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
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0" v-if="!ready">Job Preview</h2>
                <h2 class="h6 m-0" v-if="ready">{{job.name}}</h2>

                <button class="btn btn-primary" v-on:click.prevent="retry(job.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon" fill="currentColor" :class="{spin: retrying}">
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

            <div class="card-body card-bg-secondary" v-if="ready">
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">ID</div>
                    <div class="col">{{job.id}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Queue</div>
                    <div class="col">{{job.queue}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Attempts</div>
                    <div class="col">{{job.payload.attempts}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Retries</div>
                    <div class="col">{{job.retried_by.length}}</div>
                </div>
                <div class="row mb-2" v-if="job.payload.retry_of">
                    <div class="col-md-2 text-muted">Retry of ID</div>
                    <div class="col">
                         <a :href="Horizon.basePath + '/failed/' + job.payload.retry_of">
                            {{ job.payload.retry_of }}
                        </a>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Tags</div>
                    <div class="col">{{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}</div>
                </div>
                <div class="row mb-2" v-if="prettyPrintJob(job.payload.data).batchId">
                    <div class="col-md-2 text-muted">Batch</div>
                    <div class="col">
                        <router-link :to="{ name: 'batches-preview', params: { batchId: prettyPrintJob(job.payload.data).batchId }}">
                            {{ prettyPrintJob(job.payload.data).batchId }}
                        </router-link>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Pushed</div>
                    <div class="col">{{ readableTimestamp(job.payload.pushedAt) }}</div>
                </div>
                <div class="row">
                    <div class="col-md-2 text-muted">Failed</div>
                    <div class="col">{{readableTimestamp(job.failed_at)}}</div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Exception</h2>
            </div>
            <div>
                <stack-trace :trace="job.exception.split('\n')"></stack-trace>
            </div>
        </div>

        <div class="card overflow-hidden mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Exception Context</h2>
            </div>

            <div class="card-body code-bg text-white">
                <vue-json-pretty :data="prettyPrintJob(job.context)"></vue-json-pretty>
            </div>
        </div>


        <div class="card overflow-hidden mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Data</h2>
            </div>

            <div class="card-body code-bg text-white">
                <vue-json-pretty :data="prettyPrintJob(job.payload.data)"></vue-json-pretty>
            </div>
        </div>

        <div class="card overflow-hidden mt-4" v-if="ready && job.retried_by.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Recent Retries</h2>
            </div>

            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Job</th>
                    <th>ID</th>
                    <th class="text-end">Retry Time</th>
                </tr>
                </thead>

                <tbody>

                <tr v-for="retry in job.retried_by">
                    <td>
                        <svg v-if="retry.status == 'completed'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fill-success" style="width: 1.5rem; height: 1.5rem;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>

                        <svg v-if="retry.status == 'reserved' || retry.status == 'pending'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fill-warning" style="width: 1.5rem; height: 1.5rem;">
                            <path fill-rule="evenodd" d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm5-2.25A.75.75 0 017.75 7h.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-.5a.75.75 0 01-.75-.75v-4.5zm4 0a.75.75 0 01.75-.75h.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-.5a.75.75 0 01-.75-.75v-4.5z" clip-rule="evenodd" />
                        </svg>

                        <svg v-if="retry.status == 'failed'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fill-danger" style="width: 1.5rem; height: 1.5rem;">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>

                        <span class="ms-2">{{ retry.status.charAt(0).toUpperCase() + retry.status.slice(1) }}</span>
                    </td>

                    <td class="table-fit">
                        <a v-if="retry.status == 'failed'" :href="Horizon.basePath + '/failed/'+retry.id">
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
