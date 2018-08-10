<script type="text/ecmascript-6">
    export default {
        components: {},


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

                this.$http.get('/api/metrics/queues')
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
        <loader :yes="loadingQueues"/>

        <p class="text-center m-0 p-5" v-if="!loadingQueues && !queues.length">
            There aren't any queues.
        </p>

        <table v-if="!loadingQueues && queues.length" class="table card-table table-hover">
            <thead>
            <tr>
                <th>Queue</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="queue in queues" :key="queue">
                <td>
                    <router-link :to="{ name: 'metrics.detail', params: { type: 'queues', slug: queue }}">{{ queue }}</router-link>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
