<script type="text/ecmascript-6">
    import _ from 'lodash';
    import moment from 'moment';
    import Layout from '../../layouts/MainLayout.vue';
    import Panel from '../../components/Panels/Panel.vue';
    import LineChart from '../../components/Charts/LineChart';
    import Message from '../../components/Messages/Message.vue'
    import PanelContent from '../../components/Panels/PanelContent.vue';
    import PanelHeading from '../../components/Panels/PanelHeading.vue';

    export default {
        props: ['type', 'slug'],


        components: {Message, Layout, LineChart, Panel, PanelContent, PanelHeading},


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

                this.$http.get('/horizon/api/metrics/' + this.type + '/' + encodeURIComponent(this.slug))
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
                                    runtime: parseInt(sum.runtime) + parseInt(value.runtime),
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
            <panel :loading="loading">
                <panel-heading>Throughput - {{slug}}</panel-heading>
                <panel-content>
                    <message v-if="!loading && !rawData.length" text="Not Enough Data."/>

                    <line-chart v-if="!loading && rawData.length" :chart-data="metric.throughPutChart" id="throughPutChart" :width="400" :height="150"/>
                </panel-content>
            </panel>

            <panel :loading="loading">
                <panel-heading>Runtime - {{slug}}</panel-heading>
                <panel-content>
                    <message v-if="!loading && !rawData.length" text="Not Enough Data."/>

                    <line-chart v-if="!loading && rawData.length" :chart-data="metric.runTimeChart" id="runTimeChart" :width="400" :height="150"/>
                </panel-content>
            </panel>
        </section>
    </layout>
</template>
