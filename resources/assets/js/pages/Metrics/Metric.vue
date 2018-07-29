<script>
import _chain from 'lodash/chain'
import _reduce from 'lodash/reduce'
import _map from 'lodash/map'

import moment from 'moment'
import Layout from '../../layouts/MainLayout.vue'
import LineChart from '../../components/Charts/LineChart.vue'

export default {
    components: {Layout, LineChart},
    props: ['type', 'slug'],
    data() {
        return {
            loading: true,
            rawData: {},
            metric: {}
        }
    },
    mounted() {
        this.loadMetric()
    },

    methods: {
        /**
         * Load the metric.
         */
        loadMetric() {
            this.loading = true

            axios.get('/horizon/api/metrics/' + this.type + '/' + encodeURIComponent(this.slug))
                .then(({data}) => {
                    let res = this.prepareData(data)

                    this.rawData = res
                    this.metric.throughPutChart = this.buildChartData(res, 'throughput', 'Times')
                    this.metric.runTimeChart = this.buildChartData(res, 'runtime', 'Seconds')
                    this.loading = false
                })
        },

        /**
         * Prepare the response data for charts.
         */
        prepareData(data) {
            return _chain(data)
                .map((value) => {
                    value.time = this.formatDate(value.time).format('hh:mmA')

                    return value
                })
                .groupBy((value) => value.time)
                .map((value) => {
                    return _reduce(value, (sum, value) => {
                        return {
                            runtime: parseFloat(sum.runtime) + parseFloat(value.runtime),
                            throughput: parseInt(sum.throughput) + parseInt(value.throughput),
                            time: value.time
                        }
                    })
                })
                .value()
        },

        /**
         * Build the given chart data.
         */
        buildChartData(data, attribute, label) {
            return {
                labels: _map(data, 'time'),
                datasets: [
                    {
                        label: label,
                        data: _map(data, attribute),
                        lineTension: 0,
                        backgroundColor: 'rgba(235, 243, 249, 0.4)',
                        pointBackgroundColor: '#3981B4',
                        borderColor: '#3981B4',
                        borderWidth: 4
                    }
                ]
            }
        }
    }
}
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card mb-4">
                <div class="card-header">Throughput - {{ slug }}</div>
                <div class="card-body">
                    <loader :yes="loading"/>

                    <p v-if="!loading && !rawData.length" class="text-center m-0 p-5">
                        Not Enough Data
                    </p>

                    <line-chart v-if="!loading && rawData.length" :data="metric.throughPutChart"/>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Runtime - {{ slug }}</div>
                <div class="card-body">
                    <loader :yes="loading"/>

                    <p v-if="!loading && !rawData.length" class="text-center m-0 p-5">
                        Not Enough Data
                    </p>

                    <line-chart v-if="!loading && rawData.length" :data="metric.runTimeChart"/>
                </div>
            </div>
        </section>
    </layout>
</template>
