<script type="text/ecmascript-6">
    import Chart from 'chart.js';

    const SCHEME_CHANGED_EVENT = 'horizon-color-scheme-changed';

    export default {
        props: ['data'],

        data(){
            return {
                context: null,
                chart:null
            }
        },

        mounted(){
            this.context = this.$refs.canvas.getContext('2d');

            this.chart = new Chart(this.context, {
                type: 'line',
                options: {
                    tooltips: {
                        intersect: false,
                    },
                    legend: {
                        display: false,
                    },
                    scales: {
                        yAxes: [
                            {
                                ticks: {
                                    beginAtZero: true,
                                    padding: 8,
                                     callback: (value, index, values) => {
                                        return this.data.datasets[0].label === "Seconds"
                                            ? `${value} secs`
                                            : value;
                                    },
                                },
                                gridLines: {
                                    display: true
                                },
                                beforeBuildTicks: function (scale) {
                                    var max = scale.chart.data.datasets[0].data.reduce((max, value) => value > max ? value : max)

                                    scale.max = parseFloat(max) + parseFloat(max * 0.25);
                                },
                            }
                        ],
                        xAxes: [
                            {
                                ticks: {
                                    padding: 8,
                                },
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
                data: this.data
            });

            this.applyScaleColors();
            this.chart.update(0);

            this.onColorSchemeChanged = () => {
                this.applyScaleColors();
                this.chart.update(0);
            };

            window.addEventListener(SCHEME_CHANGED_EVENT, this.onColorSchemeChanged);
        },

        beforeUnmount() {
            window.removeEventListener(SCHEME_CHANGED_EVENT, this.onColorSchemeChanged);

            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },

        methods: {
            /**
             * Read Horizon's theme-aware muted text color and apply it to axes.
             */
            applyScaleColors() {
                if (!this.chart || !this.$refs.theme) {
                    return;
                }

                const muted = getComputedStyle(this.$refs.theme).color;
                const grid = Chart.helpers.color(muted).alpha(0.12).rgbString();
                const axis = Chart.helpers.color(muted).alpha(0.25).rgbString();

                this.chart.options.scales.xAxes.forEach(scale => {
                    scale.ticks = scale.ticks || {};
                    scale.ticks.fontColor = muted;
                    scale.gridLines = scale.gridLines || {};
                    scale.gridLines.color = grid;
                    scale.gridLines.zeroLineColor = axis;
                });

                this.chart.options.scales.yAxes.forEach(scale => {
                    scale.ticks = scale.ticks || {};
                    scale.ticks.fontColor = muted;
                    scale.gridLines = scale.gridLines || {};
                    scale.gridLines.color = grid;
                    scale.gridLines.zeroLineColor = axis;
                });
            },
        }
    }
</script>

<template>
    <div ref="theme" class="text-muted" style="position: relative;">
        <canvas ref="canvas" height="120"></canvas>
    </div>
</template>
