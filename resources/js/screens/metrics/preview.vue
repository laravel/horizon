<script type="text/ecmascript-6">
    import EmptyState from '../../components/EmptyState.vue';
    import LineChart from '../../components/LineChart.vue';

    export default {
        components: {
            EmptyState,
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
                return Object.values(this.groupBy(data.map(value => ({
                    ...value,
                    time: this.formatDate(value.time).format("MMM-D hh:mmA"),
                })), 'time')).map(value => value.reduce((sum, value) => ({
                    runtime: parseFloat(sum.runtime) + parseFloat(value.runtime),
                    throughput: parseInt(sum.throughput) + parseInt(value.throughput),
                    time: value.time
                })))
            },


            /**
             * Build the given chart data.
             */
            buildChartData(data, attribute, label) {
                return {
                    labels: data.map(entry => entry.time),
                    datasets: [
                        {
                            label: label,
                            data: data.map(entry => entry[attribute]),
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
        <div class="card overflow-hidden">
            <div class="card-header job-detail-header border-bottom-0 d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0 job-detail-title" :title="`Throughput - ${$route.params.slug}`">
                    Throughput - {{$route.params.slug}}
                </h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body pt-0 pe-0 pb-3 ps-4" v-if="ready">
                <empty-state
                    v-if="!rawData.length"
                    title="No throughput data yet"
                    description="Horizon needs more metric snapshots before it can draw this chart."
                    icon="metrics"
                ></empty-state>

                <line-chart v-if="rawData.length" :data="metric.throughPutChart"/>
            </div>
        </div>

        <div class="card overflow-hidden mt-3">
            <div class="card-header job-detail-header border-bottom-0 d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0 job-detail-title" :title="`Runtime - ${$route.params.slug}`">
                    Runtime - {{$route.params.slug}}
                </h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body pt-0 pe-0 pb-3 ps-4" v-if="ready">
                <empty-state
                    v-if="!rawData.length"
                    title="No runtime data yet"
                    description="Horizon needs more metric snapshots before it can draw this chart."
                    icon="metrics"
                ></empty-state>

                <line-chart v-if="rawData.length" :data="metric.runTimeChart"/>
            </div>
        </div>
    </div>
</template>
