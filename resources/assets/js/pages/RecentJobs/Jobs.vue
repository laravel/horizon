<script>
import Status from '../../components/Status/Status.vue'

export default {
    components: {Status},
    data() {
        return {
            page: 1,
            perPage: 50,
            totalPages: 1,
            loadState: true,
            jobs: []
        }
    },
    created() {
        this.loadJobs()
        this.refreshJobsPeriodically()
    },
    destroyed() {
        clearInterval(this.interval)
    },
    methods: {
        /**
         * Load the jobs.
         */
        loadJobs(starting = -1, preload = true) {
            if (preload) {
                this.loadState = true
            }

            return axios.get('/horizon/api/jobs/recent' + '?starting_at=' + starting + '&limit=' + this.perPage)
                .then(({data}) => {
                    this.jobs = data.jobs

                    this.totalPages = Math.ceil(data.total / this.perPage)

                    this.loadState = false

                    return data.jobs
                })
        },

        /**
         * Refresh the jobs every period of time.
         */
        refreshJobsPeriodically() {
            this.interval = setInterval(() => {
                if (this.page != 1) {
                    return
                }

                this.loadJobs(-1, false)
            }, 3000)
        },

        /**
         * Load the jobs for the previous page.
         */
        previous() {
            this.loadJobs(
                (this.page - 2) * this.perPage - 1
            )

            this.page -= 1
        },

        /**
         * Load the jobs for the next page.
         */
        next() {
            this.loadJobs(
                this.page * this.perPage - 1
            )

            this.page += 1
        }
    },
    watch: {
        '$route'() {
            this.page = 1
            this.loadJobs()
        }
    }
}
</script>

<template>
    <div class="table-responsive">
        <loader :yes="loadState"/>

        <p v-if="!loadState && !jobs.length" class="text-center m-0 p-5">
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
                        <a v-tippy v-if="job.status == 'failed'"
                           :href="'/horizon/failed/'+job.id" :title="job.name">{{ jobBaseName(job.name) }}
                        </a>
                        <span v-tippy v-else :title="job.name">{{ jobBaseName(job.name) }}</span>
                    </td>
                    <td>{{ job.queue }}</td>
                    <td>
                        <span v-tippy :title="displayableTagsList(job.payload.tags, false)">
                            {{ displayableTagsList(job.payload.tags) }}
                        </span>
                    </td>
                    <td class="text-nowrap">{{ readableTimestamp(job.payload.pushedAt) }}</td>
                    <td>
                        <span v-if="job.status == 'failed'">{{ job.failed_at ? (job.failed_at - job.reserved_at).toFixed(3)+'s' : '-' }}</span>
                        <span v-else="">{{ job.completed_at ? (job.completed_at - job.reserved_at).toFixed(3)+'s' : '-' }}</span>
                    </td>
                    <td>
                        <status :active="job.status == 'completed'" :pending="job.status == 'reserved' || job.status == 'pending'"/>
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="! loadState && jobs.length" class="p-3 mt-3 d-flex justify-content-between">
            <button :disabled="page==1" class="btn btn-primary btn-md" @click="previous">Previous</button>
            <button :disabled="page>=totalPages" class="btn btn-primary btn-md" @click="next">Next</button>
        </div>
    </div>
</template>
