<script type="text/ecmascript-6">
    import JobRow from './job-row.vue';

    export default {
        props: ['type'],

        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                loadingNewEntries: false,
                hasNewEntries: false,
                page: 1,
                perPage: 50,
                totalPages: 1,
                jobs: []
            };
        },


        /**
         * Components
         */
        components: {
            JobRow,
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Monitoring";

            this.loadJobs(this.$route.params.tag);
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            '$route'() {
                this.page = 1;

                this.loadJobs(this.$route.params.tag);
            },

            '$root.autoLoadsNewEntries'(autoLoadsNewEntries) {
                if (autoLoadsNewEntries && this.hasNewEntries) {
                    this.hasNewEntries = false;
                }
            }
        },


        methods: {
            /**
             * Load the jobs of the given tag.
             */
            loadJobs(tag, starting = 0, refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                tag = this.type == 'failed' ? 'failed:' + tag : tag;

                this.$http.get(Horizon.basePath + '/api/monitoring/' + encodeURIComponent(tag) + '?starting_at=' + starting + '&limit=' + this.perPage + '&tag=' + encodeURIComponent(tag))
                    .then(response => {
                        if (!this.$root.autoLoadsNewEntries && refreshing && this.jobs.length && response.data.jobs[0]?.id !== this.jobs[0]?.id) {
                            this.hasNewEntries = true;
                        } else {
                            this.jobs = response.data.jobs;

                            this.totalPages = Math.ceil(response.data.total / this.perPage);
                        }

                        this.ready = true;
                    });
            },


            /**
             * Load new entries.
             */
            loadNewEntries() {
                this.jobs = [];

                this.loadJobs(this.$route.params.tag, 0, false);

                this.hasNewEntries = false;
            },


            /**
             * Poll handler to refresh the jobs at regular intervals.
             */
            refreshJobsPeriodically() {
                if (this.page != 1) {
                    return;
                }

                this.loadJobs(this.$route.params.tag, 0, true);
            },


            /**
             * Load the jobs for the previous page.
             */
            previous() {
                this.loadJobs(this.$route.params.tag,
                    (this.page - 2) * this.perPage
                );

                this.page -= 1;

                this.hasNewEntries = false;
            },


            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs(this.$route.params.tag,
                    this.page * this.perPage
                );

                this.page += 1;

                this.hasNewEntries = false;
            }
        }
    }
</script>

<template>
    <div>
        <poll @poll="refreshJobsPeriodically" />

        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>

            <span>Loading...</span>
        </div>


        <table v-if="ready" class="table table-hover mb-0 horizon-table">
            <thead>
            <tr>
                <th>Job</th>
                <th>Queued</th>
                <th v-if="type == 'jobs'">Completed</th>
                <th class="text-end" v-if="type == 'jobs'">Runtime</th>
                <th class="text-end" v-if="type == 'failed'">Failed</th>
            </tr>
            </thead>

            <tbody>
            <table-empty
                v-if="jobs.length === 0"
                :columns="type === 'jobs' ? 4 : 3"
                title="No jobs for this tag"
                description="There aren't any jobs for this tag yet. Monitored history starts after jobs are processed with this exact tag."
                icon="monitoring"
            ></table-empty>

            <new-entries
                v-if="hasNewEntries && !$root.autoLoadsNewEntries"
                :columns="type === 'jobs' ? 4 : 3"
                :loading="loadingNewEntries"
                @load="loadNewEntries"
            ></new-entries>

            <component v-for="job in jobs" :key="job.id" :job="job" is="job-row">
            </component>
            </tbody>
        </table>

        <div v-if="ready && jobs.length && totalPages > 1" class="horizon-table-pagination d-flex justify-content-between border-top">
            <button @click="previous" class="btn btn-sm" :disabled="page==1">Previous</button>
            <button @click="next" class="btn btn-sm" :disabled="page>=totalPages">Next</button>
        </div>
    </div>

</template>
