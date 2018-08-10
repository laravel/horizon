<script type="text/ecmascript-6">
    import _ from 'lodash';
    import moment from 'moment';
    import Layout from '../../layouts/MainLayout.vue';
    import LineChart from '../../components/Charts/LineChart.vue';

    export default {
        props: ['type', 'slug'],


        components: {Layout, LineChart},


        /**
         * The component's data.
         */
        data() {
            return {
                loading: true,
                rawData: {},
                metric: {}
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadMetric();
        },


        methods: {
            /**
             * Load the metric.
             */
            loadMetric() {
                this.loading = true;

                this.$http.get('/api/metrics/' + this.type + '/' + encodeURIComponent(this.slug))
                    .then(response => {
                        let data = this.prepareData(response.data);

                        this.rawData = response.data;

                        this.metric.throughPutChart = this.buildChartData(data, 'throughput', 'Times');

                        this.metric.runTimeChart = this.buildChartData(data, 'runtime', 'Seconds');

                        this.loading = false;
                    });
            },


            /**
             * Prepare the response data for charts.
             */
            prepareData(data){
                return _.chain(data)
                    .map(value => {
                        value.time = this.formatDate(value.time).format("hh:mmA");

                        return value;
                    })
                    .groupBy(value => value.time)
                    .map(value => {
                        return _.reduce(value, (sum, value) => {
                            return {
                                runtime: parseFloat(sum.runtime) + parseFloat(value.runtime),
                                throughput: parseInt(sum.throughput) + parseInt(value.throughput),
                                time: value.time
                            };
                        })
                    })
                    .value();
            },


            /**
             * Build the given chart data.
             */
            buildChartData(data, attribute, label){
                return {
                    labels: _.map(data, 'time'),
                    datasets: [
                        {
                            label: label,
                            data: _.map(data, attribute),
                            lineTension: 0,
                            backgroundColor: 'rgba(235, 243, 249, 0.4)',
                            pointBackgroundColor: '#3981B4',
                            borderColor: '#3981B4',
                            borderWidth: 4,
                        },
                    ],
                };
            },
        }
    };
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card mb-4">
                <div class="card-header">Throughput - {{slug}}</div>
                <div class="card-body">
                    <loader :yes="loading"/>

                    <p class="text-center m-0 p-5" v-if="!loading && !rawData.length">
                        Not Enough Data
                    </p>

                    <line-chart v-if="!loading && rawData.length" :data="metric.throughPutChart"/>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Runtime - {{slug}}</div>
                <div class="card-body">
                    <loader :yes="loading"/>

                    <p class="text-center m-0 p-5" v-if="!loading && !rawData.length">
                        Not Enough Data
                    </p>

                    <line-chart v-if="!loading && rawData.length" :data="metric.runTimeChart"/>
                </div>
            </div>
        </section>
    </layout>
</template>
