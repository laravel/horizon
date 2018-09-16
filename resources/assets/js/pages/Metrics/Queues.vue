<script>
export default {
    components: {},
    data() {
        return {
            loadingQueues: true,
            queues: []
        }
    },
    mounted() {
        this.loadQueues()
    },
    methods: {
        /**
         * Load the queues.
         */
        loadQueues() {
            this.loadingQueues = true

            axios.get('/horizon/api/metrics/queues')
                .then(({data}) => {
                    this.queues = data

                    this.loadingQueues = false
                })
        }
    }
}
</script>

<template>
    <div>
        <loader :yes="loadingQueues"/>

        <p v-if="!loadingQueues && !queues.length" class="text-center m-0 p-5">
            There aren't any queues.
        </p>

        <table v-if="!loadingQueues && queues.length" class="table card-table table-hover">
            <thead>
                <tr>
                    <th>Queue</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(queue, i) in queues" :key="i">
                    <td>
                        <router-link :to="{ name: 'metrics.detail', params: { type: 'queues', slug: queue }}">{{ queue }}</router-link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
