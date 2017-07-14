<script type="text/ecmascript-6">
    import axios from 'axios'
    import Message from '../../components/Messages/Message.vue'

    export default {
        components: {Message},


        /**
         * The component's data.
         */
        data(){
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

                axios.get('/horizon/api/metrics/jobs')
                        .then(response => {
                            this.jobs = response.data;

                            this.loadingJobs = false;
                        });
            }
        }
    }
</script>

<template>
    <message v-if="!jobs.length" text="There aren't any jobs."/>

    <table v-else class="table panel-table" cellpadding="0" cellspacing="0">
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
</template>

