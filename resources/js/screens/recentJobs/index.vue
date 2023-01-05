<script type="text/ecmascript-6">
    import JobRow from './job-row';

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
            this.updatePageTitle();

            this.loadJobs();

            this.refreshJobsPeriodically();
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearInterval(this.interval);
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            '$route'() {
                this.updatePageTitle();

                this.page = 1;

                this.loadJobs();
            }
        },


        methods: {
            /**
             * Load the jobs of the given tag.
             */
            loadJobs(starting = -1, refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                this.$http.get(Horizon.basePath + '/api/jobs/' + this.$route.params.type + '?starting_at=' + starting + '&limit=' + this.perPage)
                    .then(response => {
                        if (!this.$root.autoLoadsNewEntries && refreshing && this.jobs.length && _.first(response.data.jobs).id !== _.first(this.jobs).id) {
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
             * Refresh the jobs every period of time.
             */
            refreshJobsPeriodically() {
                this.interval = setInterval(() => {
                    if (this.page != 1) {
                        return;
                    }

                    this.loadJobs(-1, true);
                }, 3000);
            },


            /**
             * Load the jobs for the previous page.
             */
            previous() {
                this.loadJobs(
                    (this.page - 2) * this.perPage
                );

                this.page -= 1;

                this.hasNewEntries = false;
            },


            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs(
                    this.page * this.perPage
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
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 v-if="$route.params.type == 'pending'">Pending Jobs</h5>
                <h5 v-if="$route.params.type == 'completed'">Completed Jobs</h5>
                <h5 v-if="$route.params.type == 'silenced'">Silenced Jobs</h5>
            </div>

            <div v-if="!ready"
                 class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div v-if="ready && jobs.length == 0"
                 class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any jobs.</span>
            </div>

            <table v-if="ready && jobs.length > 0" class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th v-if="$route.params.type=='pending'" class="text-right">Queued At</th>
                        <th v-if="$route.params.type=='completed' || $route.params.type=='silenced'">Queued At</th>
                        <th v-if="$route.params.type=='completed' || $route.params.type=='silenced'">Completed At</th>
                        <th v-if="$route.params.type=='completed' || $route.params.type=='silenced'" class="text-right">Runtime</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                        <td colspan="100" class="text-center card-bg-secondary py-1">
                            <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New
                                Entries</a></small>

                            <small v-if="loadingNewEntries">Loading...</small>
                        </td>
                    </tr>

                    <tr v-for="job in jobs" :key="job.id" :job="job" is="job-row">
                    </tr>
                </tbody>
            </table>

            <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-md" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-md" :disabled="page>=totalPages">Next</button>
            </div>
        </div>
    </div>
</template>
