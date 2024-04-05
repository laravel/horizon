<script type="text/ecmascript-6">
    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                tagSearchPhrase: '',
                searchTimeout: null,
                ready: false,
                loadingNewEntries: false,
                hasNewEntries: false,
                page: 1,
                perPage: 50,
                totalPages: 1,
                jobs: [],
                retryingJobs: [],
            };
        },

        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Failed Jobs";

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
                this.page = 1;

                this.loadJobs();
            },

            tagSearchPhrase() {
                clearTimeout(this.searchTimeout);
                clearInterval(this.interval);

                this.searchTimeout = setTimeout(() => {
                    this.loadJobs();
                    this.refreshJobsPeriodically();
                }, 500);
            }
        },


        methods: {
            /**
             * Load the jobs of the given tag.
             */
            loadJobs(starting = 0, refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                var tagQuery = this.tagSearchPhrase ? 'tag=' + this.tagSearchPhrase + '&' : '';

                this.$http.get(Horizon.basePath + '/api/jobs/failed?' + tagQuery + 'starting_at=' + starting)
                    .then(response => {
                        if (!this.$root.autoLoadsNewEntries && refreshing && !response.data.jobs.length) {
                            return;
                        }

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

                this.loadJobs(0, false);

                this.hasNewEntries = false;
            },


            /**
             * Retry the given failed job.
             */
            retry(id) {
                if (this.isRetrying(id)) {
                    return;
                }

                this.retryingJobs.push(id);

                this.$http.post(Horizon.basePath + '/api/jobs/retry/' + id)
                    .then((response) => {
                        setTimeout(() => {
                            this.retryingJobs = this.retryingJobs.filter(job => job != id);
                        }, 5000);
                    }).catch(error => {
                        this.retryingJobs = this.retryingJobs.filter(job => job != id);
                    });
            },


            /**
             * Determine if the given job is currently retrying.
             */
            isRetrying(id) {
                return this.retryingJobs.includes(id);
            },


            /**
             * Determine if the given job has completed.
             */
            hasCompleted(job) {
                return job.retried_by.find(retry => retry.status === 'completed');
            },


            /**
             * Determine if the given job was retried.
             */
            wasRetried(job) {
                return job.retried_by && job.retried_by.length;
            },


            /**
             * Determine if the given job is a retry.
             */
            isRetry(job) {
                return job.payload.retry_of;
            },

            /**
             * Construct the tooltip label for a retried job.
             */
            retriedJobTooltip(job) {
                let lastRetry = job.retried_by[job.retried_by.length - 1];

                return `Total retries: ${job.retried_by.length}, Last retry status: ${this.upperFirst(lastRetry.status)}`;
            },

            /**
             * Refresh the jobs every period of time.
             */
            refreshJobsPeriodically() {
                this.interval = setInterval(() => {
                    this.loadJobs((this.page - 1) * this.perPage, true);
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
            }
        }
    }
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Failed Jobs</h2>

                <div class="form-control-with-icon">
                    <div class="icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <input type="text" class="form-control w-100" v-model="tagSearchPhrase" placeholder="Search Tags">
                </div>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && jobs.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any failed jobs.</span>
            </div>

            <table v-if="ready && jobs.length > 0" class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Job</th>
                    <th class="text-end">Runtime</th>
                    <th>Failed</th>
                    <th class="text-end">Retry</th>
                </tr>
                </thead>

                <tbody>
                <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-2">
                        <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                        <small v-if="loadingNewEntries">Loading...</small>
                    </td>
                </tr>

                <tr v-for="job in jobs" :key="job.id">
                    <td>
                        <router-link :title="job.name" :to="{ name: 'failed-jobs-preview', params: { jobId: job.id }}">{{ jobBaseName(job.name) }}</router-link>

                        <small class="ms-1 badge bg-secondary badge-sm"
                               :title="retriedJobTooltip(job)"
                               v-if="wasRetried(job)">
                            Retried
                        </small>

                        <br>

                        <small class="text-muted">
                            Queue: {{job.queue}}
                            | Attempts: {{ job.payload.attempts }}
                            <span v-if="isRetry(job)">
                            | Retry of
                            <router-link :title="job.name" :to="{ name: 'failed-jobs-preview', params: { jobId: job.payload.retry_of }}">
                                {{ job.payload.retry_of.split('-')[0] }}
                            </router-link>
                            </span>
                            <span v-if="job.payload.tags && job.payload.tags.length" class="text-break">
                            | Tags: {{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}
                            </span>
                        </small>
                    </td>

                    <td class="table-fit text-muted text-end">
                        <span>{{ job.failed_at ? String((job.failed_at - job.reserved_at).toFixed(2))+'s' : '-' }}</span>
                    </td>

                    <td class="table-fit text-muted">
                        {{ readableTimestamp(job.failed_at) }}
                    </td>

                    <td class="text-end table-fit">
                        <a href="#" title="Retry Job" @click.prevent="retry(job.id)" v-if="!hasCompleted(job)">
                            <svg class="fill-primary" viewBox="0 0 20 20" style="width: 1.25rem; height: 1.25rem;" :class="{spin: isRetrying(job.id)}">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-sm" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-sm" :disabled="page>=totalPages">Next</button>
            </div>
        </div>

    </div>
</template>
