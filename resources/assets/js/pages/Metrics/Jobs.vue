<script type="text/ecmascript-6">
    export default {
        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                loadingJobs: true,
                jobs: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadJobs();
        },


        methods: {
            /**
             * Load the jobs.
             */
            loadJobs() {
                this.loadingJobs = true;

                this.$http.get('/api/metrics/jobs')
                    .then(response => {
                        this.jobs = response.data;

                        this.loadingJobs = false;
                    });
            }
        }
    }
</script>

<template>
    <div class="table-responsive">
        <loader :yes="loadingJobs"/>

        <p class="text-center m-0 p-5" v-if="!loadingJobs && !jobs.length">
            There aren't any jobs.
        </p>

        <table v-if="!loadingJobs && jobs.length" class="table card-table table-hover">
            <thead>
            <tr>
                <th>Job</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="job in jobs" :key="job.id">
                <td>
                    <router-link :to="{ name: 'metrics.detail', params: { type: 'jobs', slug: job }}">{{ job }}</router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
