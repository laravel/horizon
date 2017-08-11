<script type="text/ecmascript-6">
    import Spinner from '../../components/Loaders/Spinner.vue'
    import Message from '../../components/Messages/Message.vue'

    export default {
        components: {Message, Spinner},


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

                this.$http.get('/horizon/api/metrics/jobs')
                        .then(response => {
                            this.jobs = response.data;

                            this.loadingJobs = false;
                        });
            }
        }
    }
</script>

<template>
    <div>
        <div v-if="loadingJobs" style="text-align: center; margin: 50px;">
            <spinner/>
        </div>

        <message v-if="!loadingJobs && !jobs.length" text="There aren't any jobs."/>

        <table v-if="!loadingJobs && jobs.length" class="table panel-table" cellpadding="0" cellspacing="0">
            <thead>
            <tr>
                <th class="ph2">Job</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="job in jobs">
                <td class="ph2">
                    <router-link :to="{ name: 'metrics.detail', params: { type: 'jobs', slug: job }}" class="fw7">{{ job }}</router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
