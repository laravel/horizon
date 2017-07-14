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

                axios.get('/horizon/api/metrics/queues')
                        .then(response => {
                            this.queues = response.data;

                            this.loadingQueues = false;
                        });
            }
        }
    }
</script>

<template>
    <message v-if="!queues.length" text="There aren't any queues."/>

    <table v-else class="table panel-table" cellpadding="0" cellspacing="0">
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
</template>

