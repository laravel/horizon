<script>
export default {
    components: {},
    data() {
        return {
            loadingJobs: true,
            jobs: []
        }
    },
    mounted() {
        this.loadJobs()
    },

    methods: {
        /**
         * Load the jobs.
         */
        loadJobs() {
            this.loadingJobs = true

            axios.get('/horizon/api/metrics/jobs')
                .then(({data}) => {
                    this.jobs = data
                    this.loadingJobs = false
                })
        }
    }
}
</script>

<template>
    <div class="table-responsive">
        <loader :yes="loadingJobs"/>

        <p v-if="!loadingJobs && !jobs.length" class="text-center m-0 p-5">
            There aren't any jobs.
        </p>

        <table v-if="!loadingJobs && jobs.length" class="table card-table table-hover">
            <thead>
                <tr>
                    <th>Job</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(job, i) in jobs" :key="i">
                    <td>
                        <router-link :to="{ name: 'metrics.detail', params: { type: 'jobs', slug: job }}">{{ job }}</router-link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
