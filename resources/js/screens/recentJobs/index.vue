<script type="text/ecmascript-6">
    import JobRow from './job-row.vue';

    export default {
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
                jobs: [],
                jobId: '',
                findingJob: false,
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
            this.updatePageTitle();

            this.loadJobs();
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            '$route'() {
                this.updatePageTitle();

                this.page = 1;
                this.jobId = '';

                this.loadJobs();
            },

            '$root.autoLoadsNewEntries'(autoLoadsNewEntries) {
                if (autoLoadsNewEntries && this.hasNewEntries) {
                    this.hasNewEntries = false;
                }
            }
        },


        methods: {
            /**
             * Find a retained job by its ID.
             */
            findJob() {
                var jobId = this.jobId.trim();

                if (!jobId || this.findingJob) {
                    return;
                }

                this.findingJob = true;

                this.$http.get(Horizon.basePath + '/api/jobs/' + encodeURIComponent(jobId))
                    .then(response => {
                        if (!response.data.id) {
                            this.showJobSearchError('The job could not be found. It may have expired according to your trim configuration.');

                            return;
                        }

                        if (response.data.status === 'failed') {
                            this.$router.push({
                                name: 'failed-jobs-preview',
                                params: { jobId: response.data.id },
                            });

                            return;
                        }

                        this.$router.push({
                            name: 'job-preview',
                            params: {
                                jobId: response.data.id,
                                type: ['pending', 'reserved'].includes(response.data.status)
                                    ? 'pending'
                                    : (this.$route.params.type === 'silenced' ? 'silenced' : 'completed'),
                            },
                        });
                    })
                    .catch(() => {
                        this.showJobSearchError('The job could not be loaded. Please try again.');
                    })
                    .finally(() => {
                        this.findingJob = false;
                    });
            },


            /**
             * Show an error from the job search.
             */
            showJobSearchError(message) {
                this.$root.alert.message = message;
                this.$root.alert.type = 'error';
            },


            /**
             * Load the jobs of the given tag.
             */
            loadJobs(starting = -1, refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                this.$http.get(Horizon.basePath + '/api/jobs/' + this.$route.params.type + '?starting_at=' + starting + '&limit=' + this.perPage)
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


            loadNewEntries() {
                this.jobs = [];

                this.loadJobs(-1, false);

                this.hasNewEntries = false;
            },


            /**
             * Poll handler to refresh the jobs at regular intervals.
             */
            refreshJobsPeriodically() {
                if (this.page != 1) {
                    return;
                }

                this.loadJobs(-1, true);
            },


            /**
             * Load the jobs for the previous page.
             */
            previous() {
                this.loadJobs(
                    (this.page - 2) * this.perPage - 1
                );

                this.page -= 1;

                this.hasNewEntries = false;
            },


            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs(
                    this.page * this.perPage - 1
                );

                this.page += 1;

                this.hasNewEntries = false;
            },


            /**
             * Update the page title.
             */
            updatePageTitle() {
                document.title = this.$route.params.type == 'pending'
                    ? 'Horizon - Pending Jobs'
                    : (
                        this.$route.params.type == 'silenced'
                            ? 'Horizon - Silenced Jobs'
                            : 'Horizon - Completed Jobs'
                    );
            }
        }
    }
</script>

<template>
    <div>
        <poll @poll="refreshJobsPeriodically" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0" v-if="$route.params.type == 'pending'">Pending Jobs</h2>
                <h2 class="h6 m-0" v-if="$route.params.type == 'completed'">Completed Jobs</h2>
                <h2 class="h6 m-0" v-if="$route.params.type == 'silenced'">Silenced Jobs</h2>

                <form class="form-control-with-icon" @submit.prevent="findJob">
                    <button type="submit" class="icon-wrapper border-0 bg-transparent p-0" title="Find Job" :disabled="findingJob">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <input type="search" class="form-control w-100" v-model="jobId" placeholder="Find Job by ID" aria-label="Find Job by ID" :disabled="findingJob">
                </form>
            </div>

            <div v-if="!ready"
                 class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div v-if="ready && jobs.length == 0"
                 class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span v-if="$route.params.type == 'pending'">There aren't any pending jobs.</span>
                <span v-else-if="$route.params.type == 'completed'">There aren't any completed jobs.</span>
                <span v-else-if="$route.params.type == 'silenced'">There aren't any silenced jobs.</span>
                <span v-else>There aren't any jobs.</span>
            </div>

            <table v-if="ready && jobs.length > 0" class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th v-if="$route.params.type=='pending'" class="text-end">Queued</th>
                        <th v-if="$route.params.type=='completed' || $route.params.type=='silenced'">Queued</th>
                        <th v-if="$route.params.type=='completed' || $route.params.type=='silenced'">Completed</th>
                        <th v-if="$route.params.type=='completed' || $route.params.type=='silenced'" class="text-end">Runtime</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="hasNewEntries && !this.$root.autoLoadsNewEntries" key="newEntries" class="dontanimate">
                        <td colspan="100" class="text-center card-bg-secondary py-1">
                            <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                            <small v-if="loadingNewEntries">Loading...</small>
                        </td>
                    </tr>

                    <component v-for="job in jobs" :key="job.id" :job="job" is="job-row">
                    </component>
                </tbody>
            </table>

            <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-sm" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-sm" :disabled="page>=totalPages">Next</button>
            </div>
        </div>
    </div>
</template>
