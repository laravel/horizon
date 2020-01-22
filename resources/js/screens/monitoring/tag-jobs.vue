<script type="text/ecmascript-6">
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
                perPage: 3,
                totalPages: 1,
                jobs: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Monitoring";

            this.loadJobs(this.$route.params.tag);

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

                this.loadJobs(this.$route.params.tag);
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

                this.$http.get(Horizon.basePath + '/api/monitoring/' + encodeURIComponent(tag) + '?starting_at=' + starting + '&limit=' + this.perPage)
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


            /**
             * Load new entries.
             */
            loadNewEntries() {
                this.jobs = [];

                this.loadJobs(this.$route.params.tag, 0, false);

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

                    this.loadJobs(this.$route.params.tag, 0, true);
                }, 3000);
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
        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>

            <span>Loading...</span>
        </div>


        <div v-if="ready && jobs.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <span>There aren't any jobs for this tag.</span>
        </div>

        <table v-if="ready && jobs.length > 0" class="table table-hover table-sm mb-0">
            <thead>
            <tr>
                <th>Job</th>
                <th>Queued At</th>
                <th v-if="type == 'jobs'">Runtime</th>
                <th class="text-right" v-if="type == 'jobs'">Status</th>
                <th class="text-right" v-if="type == 'failed'">Failed At</th>
            </tr>
            </thead>

            <tbody>
            <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                <td colspan="100" class="text-center card-bg-secondary py-1">
                    <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                    <small v-if="loadingNewEntries">Loading...</small>
                </td>
            </tr>

            <tr v-for="job in jobs" :key="job.id">
                <td>
                    <span v-if="job.status != 'failed'" :title="job.name">{{jobBaseName(job.name)}}</span>
                    <router-link v-if="job.status === 'failed'" :title="job.name" :to="{ name: 'failed-jobs-preview', params: { jobId: job.id }}">
                        {{ jobBaseName(job.name) }}
                    </router-link><br>

                    <small class="text-muted">
                        Queue: {{job.queue}} | Tags: {{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}
                    </small>
                </td>
                <td class="table-fit">
                    {{ readableTimestamp(job.payload.pushedAt) }}
                </td>

                <td class="table-fit" v-if="type == 'jobs'">
                    <span>{{ job.completed_at ? (job.completed_at - job.reserved_at).toFixed(2)+'s' : '-' }}</span>
                </td>

                <td class="text-right table-fit" v-if="type == 'jobs'">
                    <svg v-if="job.status == 'completed'" class="fill-success" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"></path>
                    </svg>

                    <svg v-if="job.status == 'reserved' || job.status == 'pending'" class="fill-warning" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM7 6h2v8H7V6zm4 0h2v8h-2V6z"/>
                    </svg>

                    <svg v-if="job.status == 'failed'" class="fill-danger" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/>
                    </svg>
                </td>

                <td class="text-right table-fit" v-if="type == 'failed'">
                    {{ readableTimestamp(job.failed_at) }}
                </td>
            </tr>
            </tbody>
        </table>

        <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
            <button @click="previous" class="btn btn-secondary btn-md" :disabled="page==1">Previous</button>
            <button @click="next" class="btn btn-secondary btn-md" :disabled="page>=totalPages">Next</button>
        </div>
    </div>

</template>
