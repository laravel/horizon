<script type="text/ecmascript-6">
    import moment from 'moment';
    import Layout from '../../layouts/MainLayout.vue'

    export default {
        components: {Layout},


        /**
         * The component's data.
         */
        data() {
            return {
                tagSearchPhrase: '',
                searchTimeout: null,
                page: 1,
                perPage: 50,
                totalPages: 1,
                loadingJobs: true,
                retryingJobs: [],
                jobs: []
            };
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            tagSearchPhrase() {
                clearTimeout(this.searchTimeout);

                this.searchTimeout = setTimeout(() => {
                    this.loadJobs();
                }, 500);
            }
        },


        /**
         * Prepare the component.
         */
        created() {
            document.title = "Horizon - Failed Jobs";

            this.loadJobs();

            this.refreshJobsPeriodically();
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed(){
            clearInterval(this.interval);
        },


        methods: {
            /**
             * Load the failed jobs.
             */
            loadJobs(starting = -1, preload = true) {
                if (preload) {
                    this.loadingJobs = true;
                }

                var tagQuery = this.tagSearchPhrase ? 'tag=' + this.tagSearchPhrase + '&' : '';

                this.$http.get('/api/jobs/failed?' + tagQuery + 'starting_at=' + starting)
                    .then(response => {
                        this.jobs = response.data.jobs;

                        this.totalPages = Math.ceil(response.data.total / this.perPage);

                        this.loadingJobs = false;
                    });
            },


            /**
             * Retry the given failed job.
             */
            retry(id) {
                if (this.isRetrying(id)) {
                    return;
                }

                this.retryingJobs.push(id);

                this.$http.post('/api/jobs/retry/' + id)
                    .then(() => {
                        setTimeout(() => {
                            this.retryingJobs = _.reject(this.retryingJobs, job => job == id);
                        }, 3000);
                    });
            },


            /**
             * Determine if the given job is currently retrying.
             */
            isRetrying(id) {
                return _.includes(this.retryingJobs, id);
            },


            /**
             * Determine if the given job has completed.
             */
            hasCompleted(job){
                return _.find(job.retried_by, retry => retry.status == 'completed');
            },


            /**
             * Refresh the jobs every period of time.
             */
            refreshJobsPeriodically() {
                this.interval = setInterval(() => {
                    if (this.page != 1) {
                        return;
                    }

                    this.loadJobs(-1, false);
                }, 3000);
            },


            /**
             * Load the jobs for the previous page.
             */
            previous() {
                this.loadJobs(((this.page - 2) * this.perPage) - 1);

                this.page -= 1;
            },


            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs((this.page * this.perPage) - 1);

                this.page += 1;
            }
        }
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <span class="mr-auto">Failed Jobs</span>
                    <div class="search">
                        <input type="text" class="form-control" v-model="tagSearchPhrase" placeholder="Search Tags">
                    </div>
                </div>

                <div class="table-responsive">
                    <loader :yes="loadingJobs"/>

                    <p class="text-center m-0 p-5" v-if="!loadingJobs && !jobs.length">
                        There aren't any recent failed jobs.
                    </p>

                    <table v-if="! loadingJobs && jobs.length" class="table card-table table-hover">
                        <thead>
                        <tr>
                            <th>Job</th>
                            <th>On</th>
                            <th>Tags</th>
                            <th>Runtime</th>
                            <th>Failed At</th>
                            <th>Retry</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="job in jobs" :key="job.id">
                            <td>
                                <router-link :to="{ name: 'failed.detail', params: { jobId: job.id }}" data-toggle="tooltip" :title="job.name">
                                    {{ jobBaseName(job.name) }}
                                </router-link>
                            </td>
                            <td>{{ job.queue }}</td>
                            <td>{{ displayableTagsList(job.payload.tags) }}</td>
                            <td>{{ job.failed_at ? String((job.failed_at - job.reserved_at).toFixed(3))+'s' : '-' }}</td>
                            <td class="text-nowrap">{{ readableTimestamp(job.failed_at) }}</td>
                            <td>
                                <span @click="retry(job.id)" v-if="!hasCompleted(job)">
                                    <i class="icon">
                                        <svg class="fill-primary" :class="{spin: isRetrying(job.id)}">
                                            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-refresh"></use>
                                        </svg>
                                    </i>
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div v-if="! loadingJobs && jobs.length" class="p-3 mt-3 d-flex justify-content-between">
                        <button @click="previous" class="btn btn-primary btn-md" :disabled="page==1">Previous</button>
                        <button @click="next" class="btn btn-primary btn-md" :disabled="page>=totalPages">Next</button>
                    </div>
                </div>
            </div>
        </section>
    </layout>
</template>
