<script setup lang="ts">
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import LineChart from '../../components/LineChart.vue';

interface MetricData {
    runtime: number;
    throughput: number;
    time: number;
}

interface PreparedData {
    runtime: number;
    throughput: number;
    time: string;
}

interface MetricCharts {
    throughPutChart?: any;
    runTimeChart?: any;
}

const route = useRoute();
const instance = getCurrentInstance();

const ready = ref(false);
const rawData = ref<MetricData[]>([]);
const metric = ref<MetricCharts>({});

onMounted(() => {
    document.title = "Horizon - Metrics";
    loadMetric();
});

/**
 * Load the metric.
 */
const loadMetric = () => {
    ready.value = false;

    const $http = instance?.appContext.config.globalProperties.$http;
    if (!$http) return;

    $http.get<MetricData[]>(window.Horizon.basePath + '/api/metrics/' + route.params.type + '/' + encodeURIComponent(route.params.slug as string))
        .then(response => {
            const data = prepareData(response.data);

            rawData.value = response.data;

            metric.value.throughPutChart = buildChartData(data, 'throughput', 'Times');
            metric.value.runTimeChart = buildChartData(data, 'runtime', 'Seconds');

            ready.value = true;
        });
};

/**
 * Prepare the response data for charts.
 */
const prepareData = (data: MetricData[]): PreparedData[] => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    
    const grouped = baseMixin.groupBy(data.map((value: MetricData) => ({
        ...value,
        time: baseMixin.formatDate(value.time).format("MMM-D hh:mmA"),
    })), 'time');
    
    return Object.values(grouped).map((group: any) => 
        group.reduce((sum: any, value: any) => ({
            runtime: parseFloat(sum.runtime) + parseFloat(value.runtime),
            throughput: parseInt(sum.throughput) + parseInt(value.throughput),
            time: value.time
        }))
    );
};

/**
 * Build the given chart data.
 */
const buildChartData = (data: PreparedData[], attribute: keyof PreparedData, label: string): any => {
    return {
        labels: data.map(entry => entry.time),
        datasets: [
            {
                label: label,
                data: data.map(entry => entry[attribute] as number),
                tension: 0,
                backgroundColor: 'transparent',
                pointBackgroundColor: '#fff',
                pointBorderColor: '#7746ec',
                borderColor: '#7746ec',
                borderWidth: 2,
            },
        ],
    };
};
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Throughput - {{route.params.slug}}</h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary" v-if="ready">
                <p class="text-center m-0 p-5" v-if="ready && !rawData.length">
                    Not Enough Data
                </p>

                <line-chart v-if="ready && rawData.length && metric.throughPutChart" :data="metric.throughPutChart"/>
            </div>
        </div>

        <div class="card overflow-hidden mt-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Runtime - {{route.params.slug}}</h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary" v-if="ready">
                <p class="text-center m-0 p-5" v-if="ready && !rawData.length">
                    Not Enough Data
                </p>

                <line-chart v-if="ready && rawData.length && metric.runTimeChart" :data="metric.runTimeChart"/>
            </div>
        </div>
    </div>
</template>