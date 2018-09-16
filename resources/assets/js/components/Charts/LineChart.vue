<script>
import Chart from 'chart.js'
import _max from 'lodash/max'

export default {
    props: ['data'],
    data() {
        return {
            context: null,
            chart: null
        }
    },
    mounted() {
        this.context = this.$refs.canvas.getContext('2d')

        this.chart = new Chart(this.context, {
            type: 'line',
            options: {
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [
                        {
                            ticks: {
                                beginAtZero: true
                            },
                            gridLines: {
                                display: true
                            },
                            beforeBuildTicks(scale) {
                                let max = _max(scale.chart.data.datasets[0].data)

                                scale.max = parseFloat(max) + parseFloat(max * 0.25)
                            }
                        }
                    ],
                    xAxes: [
                        {
                            gridLines: {
                                display: true
                            },
                            afterTickToLabelConversion(data) {
                                let xLabels = data.ticks

                                xLabels.forEach((labels, i) => {
                                    if (i % 6 != 0 && i + 1 != xLabels.length) {
                                        xLabels[i] = ''
                                    }
                                })
                            }
                        }
                    ]
                }
            },
            data: this.data
        })
    }
}
</script>

<template>
    <div style="position: relative;">
        <canvas ref="canvas" height="70"/>
    </div>
</template>
