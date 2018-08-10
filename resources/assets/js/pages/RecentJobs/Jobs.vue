<script type="text/ecmascript-6">
    import Status from '../../components/Status/Status.vue'

    export default {
        components: {Status},


        /**
         * The component's data.
         */
        data() {
            return {
                page: 1,
                perPage: 50,
                totalPages: 1,
                loadState: true,
                jobs: []
            };
        },


        /**
         * Prepare the component.
         */
        created() {
            this.loadJobs();

            this.refreshJobsPeriodically();
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed(){
            clearInterval(this.interval);
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            '$route'() {
                this.page = 1;

                this.loadJobs();
            }
        },


        methods: {
            /**
             * Load the jobs.
             */
            loadJobs(starting = -1, preload = true) {
                if (preload) {
                    this.loadState = true;
                }

                return this.$http.get('/api/jobs/recent' + '?starting_at=' + starting + '&limit=' + this.perPage)
                    .then(response => {
                        this.jobs = response.data.jobs;

                        this.totalPages = Math.ceil(response.data.total / this.perPage);

                        this.loadState = false;

                        return response.data.jobs;
                    });
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
                this.loadJobs(
                    ((this.page - 2) * this.perPage) - 1
                );

                this.page -= 1;
            },


            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs(
                    (this.page * this.perPage) - 1
                );

                this.page += 1;
            }
        },
    }
</script>

<template>
    <div class="table-responsive">
        <loader :yes="loadState"/>

        <p class="text-center m-0 p-5" v-if="!loadState && !jobs.length">
            There aren't any recent jobs.
        </p>

        <table v-if="! loadState && jobs.length" class="table card-table table-hover">
            <thead>
            <tr>
                <th>Job</th>
                <th>On</th>
                <th>Tags</th>
                <th>Queued At</th>
                <th>Runtime</th>
                <th>Status</th>
            </tr>
            </thead>

            <tbody>
            <tr v-for="job in jobs" :key="job.id">
                <td>
                    <a v-if="job.status == 'failed'" :href="'/horizon/failed/'+job.id"
                       data-toggle="tooltip" :title="job.name">{{ jobBaseName(job.name) }}
                    </a>
                    <span data-toggle="tooltip" :title="job.name" v-else>{{ jobBaseName(job.name) }}</span>
                </td>
                <td>{{ job.queue }}</td>
                <td>
                    <span data-toggle="tooltip"
                          :title="displayableTagsList(job.payload.tags, false)">{{ displayableTagsList(job.payload.tags) }}</span>
                </td>
                <td class="text-nowrap">{{ readableTimestamp(job.payload.pushedAt) }}</td>
                <td>
                    <span v-if="job.status == 'failed'">{{ job.failed_at ? (job.failed_at - job.reserved_at).toFixed(3)+'s' : '-' }}</span>
                    <span v-else>{{ job.completed_at ? (job.completed_at - job.reserved_at).toFixed(3)+'s' : '-' }}</span>
                </td>
                <td>
                    <status :active="job.status == 'completed'" :pending="job.status == 'reserved' || job.status == 'pending'"/>
                </td>
            </tr>
            </tbody>
        </table>

        <div v-if="! loadState && jobs.length" class="p-3 mt-3 d-flex justify-content-between">
            <button @click="previous" class="btn btn-primary btn-md" :disabled="page==1">Previous</button>
            <button @click="next" class="btn btn-primary btn-md" :disabled="page>=totalPages">Next</button>
        </div>
    </div>
</template>
