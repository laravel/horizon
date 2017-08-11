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
                loadingQueues: true,
                queues: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadQueues();
        },


        methods: {
            /**
             * Load the queues.
             */
            loadQueues() {
                this.loadingQueues = true;

                this.$http.get('/horizon/api/metrics/queues')
                        .then(response => {
                            this.queues = response.data;

                            this.loadingQueues = false;
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

        <message v-if="!loadingQueues && !queues.length" text="There aren't any queues."/>

        <table v-if="!loadingQueues && queues.length" class="table panel-table" cellpadding="0" cellspacing="0">
            <thead>
            <tr>
                <th class="ph2">Queue</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="queue in queues">
                <td class="ph2">
                    <router-link :to="{ name: 'metrics.detail', params: { type: 'queues', slug: queue }}" class="fw7">{{ queue }}</router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
