<script setup lang="ts">
import { ref, watch, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import Poll from '../../components/Poll.vue';

interface Batch {
    id: string;
    name: string;
    totalJobs: number;
    pendingJobs: number;
    failedJobs: number;
    progress: number;
    createdAt: string;
    cancelledAt: string | null;
}

interface BatchesResponse {
    batches: Batch[];
}

const route = useRoute();
const instance = getCurrentInstance();

const ready = ref(false);
const loadingNewEntries = ref(false);
const hasNewEntries = ref(false);
const page = ref(1);
const previousFirstId = ref<string | null>(null);
const batches = ref<Batch[]>([]);

onMounted(() => {
    document.title = "Horizon - Batches";
});

watch(() => route.path, () => {
    page.value = 1;
    loadBatches();
});

/**
 * Load the batches.
 */
const loadBatches = (beforeId = '', refreshing = false) => {
    if (!refreshing) {
        ready.value = false;
    }

    const $http = instance?.appContext.config.globalProperties.$http;
    const $root = instance?.appContext.config.globalProperties.$root as any;
    
    if (!$http) return;

    $http.get<BatchesResponse>(window.Horizon.basePath + '/api/batches?before_id=' + beforeId)
        .then(response => {
            if (!$root.autoLoadsNewEntries && refreshing && !response.data.batches.length) {
                return;
            }

            if (!$root.autoLoadsNewEntries && refreshing && batches.value.length && response.data.batches[0]?.id !== batches.value[0]?.id) {
                hasNewEntries.value = true;
            } else {
                batches.value = response.data.batches;
            }

            ready.value = true;
        });
};

const loadNewEntries = () => {
    batches.value = [];
    loadBatches('0', false);
    hasNewEntries.value = false;
};

/**
 * Poll handler to refresh the batches at regular intervals.
 */
const refreshBatchesPeriodically = () => {
    if (page.value !== 1) return;
    loadBatches('', true);
};

/**
 * Load the batches for the previous page.
 */
const previous = () => {
    loadBatches(
        page.value === 2 ? '' : previousFirstId.value || ''
    );

    page.value -= 1;
    hasNewEntries.value = false;
};

/**
 * Load the batches for the next page.
 */
const next = () => {
    previousFirstId.value = batches.value[0]?.id + '0';

    loadBatches(
        batches.value.slice(-1)[0]?.id
    );

    page.value += 1;
    hasNewEntries.value = false;
};

const formatDateIso = (date: string) => {
    const instance = getCurrentInstance();
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.formatDateIso(date);
};
</script>

<template>
    <div>
        <poll @poll="refreshBatchesPeriodically" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Batches</h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && batches.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any batches.</span>
            </div>

            <table v-if="ready && batches.length > 0" class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Batch</th>
                    <th>Status</th>
                    <th class="text-end">Size</th>
                    <th class="text-end">Completion</th>
                    <th class="text-end">Created</th>
                </tr>
                </thead>

                <tbody>
                <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-2">
                        <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                        <small v-if="loadingNewEntries">Loading...</small>
                    </td>
                </tr>

                <tr v-for="batch in batches" :key="batch.id">
                    <td>
                        <router-link :title="batch.id" :to="{ name: 'batches-preview', params: { batchId: batch.id }}">
                            {{ batch.name || batch.id }}
                        </router-link>
                    </td>
                    <td>
                        <small class="badge badge-danger badge-sm" v-if="!batch.cancelledAt && batch.failedJobs > 0 && batch.totalJobs - batch.pendingJobs < batch.totalJobs">
                            Failures
                        </small>
                        <small class="badge badge-success badge-sm" v-if="!batch.cancelledAt && batch.totalJobs - batch.pendingJobs == batch.totalJobs">
                            Finished
                        </small>
                        <small class="badge badge-secondary badge-sm" v-if="!batch.cancelledAt && batch.pendingJobs > 0 && !batch.failedJobs">
                            Pending
                        </small>
                        <small class="badge badge-warning badge-sm" v-if="batch.cancelledAt">
                            Cancelled
                        </small>
                    </td>
                    <td class="text-end text-muted">{{ batch.totalJobs }}</td>
                    <td class="text-end text-muted">{{ batch.progress }}%</td>

                    <td class="text-end table-fit">
                        {{ formatDateIso(batch.createdAt).format("YYYY-MM-DD HH:mm:ss") }}
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="ready && batches.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-sm" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-sm" :disabled="batches.length < 50">Next</button>
            </div>
        </div>

    </div>
</template>