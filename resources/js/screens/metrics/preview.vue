<script type="text/ecmascript-6">
    import LineChart from '../../components/LineChart.vue';

    export default {
        components: {
            LineChart
        },


        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                rawData: {},
                metric: {}
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Metrics";

            this.loadMetric();
        },


        methods: {
            /**
             * Load the metric.
             */
            loadMetric() {
                this.ready = false;

                this.$http.get(Horizon.basePath + '/api/metrics/' + this.$route.params.type + '/' + encodeURIComponent(this.$route.params.slug))
                    .then(response => {
                        let data = this.prepareData(response.data);

                        this.rawData = response.data;

                        this.metric.throughPutChart = this.buildChartData(data, 'throughput', 'Times');

                        this.metric.runTimeChart = this.buildChartData(data, 'runtime', 'Seconds');

                        this.ready = true;
                    });
            },


            /**
             * Prepare the response data for charts.
             */
            prepareData(data) {
                return _.chain(data)
                    .map(value => {
                        value.time = this.formatDate(value.time).format("MMM-D hh:mmA");

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
            buildChartData(data, attribute, label) {
                return {
                    labels: _.map(data, 'time'),
                    datasets: [
                        {
                            label: label,
                            data: _.map(data, attribute),
                            lineTension: 0,
                            backgroundColor: 'transparent',
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#7746ec',
                            borderColor: '#7746ec',
                            borderWidth: 2,
                        },
                    ],
                };
            },
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Throughput - {{$route.params.slug}}</h5>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary" v-if="ready">
                <p class="text-center m-0 p-5" v-if="ready && !rawData.length">
                    Not Enough Data
                </p>

                <line-chart v-if="ready && rawData.length" :data="metric.throughPutChart"/>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Runtime - {{$route.params.slug}}</h5>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary" v-if="ready">
                <p class="text-center m-0 p-5" v-if="ready && !rawData.length">
                    Not Enough Data
                </p>

                <line-chart v-if="ready && rawData.length" :data="metric.runTimeChart"/>
            </div>
        </div>
    </div>
</template>
