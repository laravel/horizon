<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Chart from 'chart.js';

interface Props {
    data: any;
}

const props = defineProps<Props>();

const canvas = ref<HTMLCanvasElement | null>(null);
const chart = ref<Chart | null>(null);

onMounted(() => {
    if (!canvas.value) return;
    
    const context = canvas.value.getContext('2d');
    if (!context) return;

    chart.value = new Chart(context, {
        type: 'line',
        data: props.data,
        options: {
            legend: {
                display: false,
            },
            tooltips: {
                intersect: false,
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value: any) {
                            return props.data.datasets[0].label === "Seconds"
                                ? `${value} secs`
                                : value;
                        },
                    },
                    beforeBuildTicks: function(scale: any) {
                        const dataset = scale.chart.data.datasets[0];
                        if (dataset && dataset.data) {
                            const max = Math.max(...(dataset.data as number[]));
                            scale.max = max + (max * 0.25);
                        }
                    },
                }],
                xAxes: [{
                    display: true,
                    afterTickToLabelConversion: function(axis: any) {
                        const ticks = axis.ticks;
                        
                        ticks.forEach((_tick: any, i: any) => {
                            if (i % 6 !== 0 && (i + 1) !== ticks.length) {
                                ticks[i] = '';
                            }
                        });
                    }
                }],
            },
            maintainAspectRatio: false,
        },
    });
});
</script>

<template>
    <div style="position: relative;">
        <canvas ref="canvas" height="120"></canvas>
    </div>
</template>