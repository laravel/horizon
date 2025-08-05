<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Chart, ChartConfiguration, ChartData, registerables } from 'chart.js';

// Register all Chart.js components
Chart.register(...registerables);

interface Props {
    data: ChartData<'line'>;
}

const props = defineProps<Props>();

const canvas = ref<HTMLCanvasElement | null>(null);
const chart = ref<Chart<'line'> | null>(null);

onMounted(() => {
    if (!canvas.value) return;
    
    const context = canvas.value.getContext('2d');
    if (!context) return;

    const config: ChartConfiguration<'line'> = {
        type: 'line',
        data: props.data,
        options: {
            interaction: {
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    intersect: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return props.data.datasets[0].label === "Seconds"
                                ? `${value} secs`
                                : value;
                        },
                    },
                    grid: {
                        display: true
                    },
                    beforeBuildTicks: function(scale) {
                        const dataset = scale.chart.data.datasets[0];
                        if (dataset && dataset.data) {
                            const max = Math.max(...(dataset.data as number[]));
                            scale.max = max + (max * 0.25);
                        }
                    },
                },
                x: {
                    grid: {
                        display: true
                    },
                    afterTickToLabelConversion: function(axis) {
                        const ticks = axis.ticks;
                        
                        ticks.forEach((tick, i) => {
                            if (i % 6 !== 0 && (i + 1) !== ticks.length) {
                                tick.label = '';
                            }
                        });
                    }
                },
            }
        }
    };

    chart.value = new Chart(context, config);
});
</script>

<template>
    <div style="position: relative;">
        <canvas ref="canvas" height="120"></canvas>
    </div>
</template>
