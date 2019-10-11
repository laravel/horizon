<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'

    export default {

        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                job: {}
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadJob(this.$route.params.jobId);

            document.title = `Horizon - Job ${this.$route.params.jobId} details`;
        },


        methods: {
            loadJob(id) {
                this.ready = false;

                this.$http.get('/' + Horizon.path + '/api/jobs/recent/' + id)
                    .then(response => {
                        this.job = response.data;

                        this.ready = true;
                    });
            },


            /**
             * Pretty print serialized job.
             *
             * @param data
             * @returns {string}
             */
            prettyPrintJob(data) {
                return data.command ? phpunserialize(data.command) : data;
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
                    <div class="col-md-2"><strong>Status</strong></div>
                    <div class="col">{{job.status}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2"><strong>Tags</strong></div>
                    <div class="col">{{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}</div>
                </div>
                <div class="row mb-2" v-if="job.reserved_at">
                    <div class="col-md-2"><strong>Reserved At</strong></div>
                    <div class="col">{{readableTimestamp(job.reserved_at)}}</div>
                </div>
                <div class="row" v-if="job.completed_at">
                    <div class="col-md-2"><strong>Completed At</strong></div>
                    <div class="col">{{readableTimestamp(job.completed_at)}}</div>
                </div>
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

    </div>
</template>
