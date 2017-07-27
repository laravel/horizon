import Vue from 'vue';
import _ from 'lodash';
import Chart from 'chart.js';
import {mergeOptions} from '../../../helpers/options';

export default Vue.extend({
    props: {
        chartId: {
            default: 'line-chart',
            type: String
        },
        width: {
            default: 400,
            type: Number
        },
        height: {
            default: 400,
            type: Number
        },
    },


    data() {
        return {
            defaultOptions: {
                scales: {
                    yAxes: [
                        {
                            ticks: {
                                beginAtZero: true
                            },
                            gridLines: {
                                display: true
                            },
                            beforeBuildTicks: function (scale) {
                                var max = _.max(scale.chart.data.datasets[0].data);
                                scale.max = parseInt(max) + parseInt(max * 0.25);
                            },
                        }
                    ],
                    xAxes: [
                        {
                            gridLines: {
                                display: true
                            },
                            afterTickToLabelConversion: function (data) {
                                var xLabels = data.ticks;

                                xLabels.forEach(function (labels, i) {
                                    if (i % 6 != 0 && (i + 1) != xLabels.length) {
                                        xLabels[i] = '';
                                    }
                                });
                            }
                        },
                    ]
                }
            },
        };
    },


    render(h) {
        return h('div', [
            h('canvas', {
                attrs: {
                    id: this.chartId,
                    width: this.width,
                    height: this.height,
                },
                ref: 'canvas',
            }),
        ]);
    },


    methods: {
        renderChart(data, options) {
            Chart.defaults.global.layout = {};
            Chart.defaults.global.layout.padding = 40;
            Chart.defaults.global.legend.display = false;

            const chartOptions = mergeOptions(this.defaultOptions, options);

            this._chart = new Chart(this.$refs.canvas.getContext('2d'), {
                type: 'line',
                data: data,
                options: chartOptions,
            });
        },
    },


    beforeDestroy() {
        this._chart.destroy();
    },
});
