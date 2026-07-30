<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header job-detail-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0 job-detail-title" v-if="!ready || !job.id">Job Preview</h2>
                <h2 class="h6 m-0 job-detail-title" v-if="ready && job.id" :title="job.name">{{job.name}}</h2>

                <button
                    type="button"
                    class="job-detail-collapse"
                    v-if="ready && job.id"
                    :aria-expanded="detailsExpanded"
                    aria-controls="collapseDetails"
                    @click="detailsExpanded = !detailsExpanded"
                >
                    {{ detailsExpanded ? 'Collapse' : 'Expand' }}
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

            <dl class="job-detail-properties" id="collapseDetails" v-if="ready && job.id" v-show="detailsExpanded">
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
                    <dt>Pushed</dt>
                    <dd>{{ readableTimestamp(job.payload.pushedAt) }}</dd>
                </div>

                <div class="job-detail-property" v-if="jobData.batchId">
                    <dt>Batch</dt>
                    <dd>
                        <router-link :to="{ name: 'batches-preview', params: { batchId: jobData.batchId }}">
                            {{ jobData.batchId }}
                        </router-link>
                    </dd>
                </div>

                <div class="job-detail-property" v-if="delayed">
                    <dt>Delayed Until</dt>
                    <dd>{{delayed}}</dd>
                </div>

                <div class="job-detail-property">
                    <dt>Completed</dt>
                    <dd v-if="job.completed_at">{{readableTimestamp(job.completed_at)}}</dd>
                    <dd v-else>—</dd>
                </div>
            </dl>
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

        <div class="card overflow-hidden mt-3" v-if="ready && job.id && job.payload.tags.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Tags</h2>

                <button
                    type="button"
                    class="job-detail-collapse"
                    :aria-expanded="tagsExpanded"
                    aria-controls="collapseTags"
                    @click="tagsExpanded = !tagsExpanded"
                >
                    {{ tagsExpanded ? 'Collapse' : 'Expand' }}
                </button>
            </div>

            <div class="card-body code-bg text-white job-detail-code" id="collapseTags" v-show="tagsExpanded">
                <vue-json-pretty :data="job.payload.tags"></vue-json-pretty>
            </div>
        </div>
    </div>
</template>

<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize';
    import moment from 'moment-timezone';
    import EmptyState from './../../components/EmptyState.vue';
    import StackTrace from './../../components/Stacktrace.vue';

    export default {
        components: {
            EmptyState,
            'stack-trace': StackTrace,
        },

        data() {
            return {
                ready: false,
                detailsExpanded: true,
                dataExpanded: true,
                tagsExpanded: true,
                job: {}
            };
        },

        computed: {
            unserialized() {
                return phpunserialize(this.job.payload.data.command);
            },


            jobData() {
                return this.ready && this.job.payload ? this.prettyPrintJob(this.job.payload.data) : {};
            },


            delayed() {
                if (!this.job.payload) {
                    return null;
                }

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
