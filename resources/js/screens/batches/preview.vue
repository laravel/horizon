<script setup lang="ts">
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import Poll from '../../components/Poll.vue';

interface BatchOptions {
    queue?: string;
    connection?: string;
}

interface Batch {
    id: string;
    name: string;
    totalJobs: number;
    pendingJobs: number;
    failedJobs: number;
    processedJobs: number;
    progress: number;
    createdAt: string;
    finishedAt?: string;
    cancelledAt?: string;
    options: BatchOptions;
}

interface FailedJob {
    id: string;
    name: string;
    failed_at: number;
    reserved_at: number;
}

interface BatchResponse {
    batch: Batch;
    failedJobs: FailedJob[];
}

const route = useRoute();
const instance = getCurrentInstance();

const ready = ref(false);
const retrying = ref(false);
const batch = ref<Batch>({} as Batch);
const failedJobs = ref<FailedJob[]>([]);

onMounted(() => {
    document.title = "Horizon - Batches";
});

const loadBatch = (reload = true) => {
    if (reload) {
        ready.value = false;
    }

    const $http = instance?.appContext.config.globalProperties.$http;
    if (!$http) return;

    $http.get<BatchResponse>(window.Horizon.basePath + '/api/batches/' + route.params.batchId)
        .then(response => {
            batch.value = response.data.batch;
            failedJobs.value = response.data.failedJobs;
            ready.value = true;
        });
};

/**
 * Retry the given failed job.
 */
const retry = (id: string) => {
    if (retrying.value) {
        return;
    }

    retrying.value = true;

    const $http = instance?.appContext.config.globalProperties.$http;
    if (!$http) return;

    $http.post(window.Horizon.basePath + '/api/batches/retry/' + id)
        .then(() => {
            setTimeout(() => {
                loadBatch(false);
                retrying.value = false;
            }, 3000);
        });
};

const formatDateIso = (date: string) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.formatDateIso(date);
};

const readableTimestamp = (timestamp: number) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.readableTimestamp(timestamp);
};

const jobBaseName = (name: string) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.jobBaseName(name);
};
</script>

<template>
    <div>
        <poll @poll="loadBatch(false)" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0" v-if="!ready">Batch Preview</h2>
                <h2 class="h6 m-0" v-if="ready">{{batch.name || batch.id}}</h2>

                <button class="btn btn-primary" v-if="failedJobs.length > 0" v-on:click.prevent="retry(batch.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon" fill="currentColor" :class="{spin: retrying}">
                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                    </svg>

                    Retry Failed Jobs
                </button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div class="card-body card-bg-secondary" v-if="ready">
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">ID</div>
                    <div class="col">
                        {{batch.id}}

                        <small class="ms-1 badge badge-danger badge-sm" v-if="batch.failedJobs > 0 && batch.totalJobs - batch.pendingJobs < batch.totalJobs">
                            Failures
                        </small>
                        <small class="ms-1 badge badge-success badge-sm" v-if="batch.totalJobs - batch.pendingJobs == batch.totalJobs">
                            Finished
                        </small>
                        <small class="ms-1 badge badge-secondary badge-sm" v-if="batch.pendingJobs > 0 && !batch.failedJobs">
                            Pending
                        </small>
                    </div>
                </div>
                <div class="row mb-2" v-if="batch.name">
                    <div class="col-md-2 text-muted">Name</div>
                    <div class="col">{{batch.name}}</div>
                </div>
                <div class="row mb-2" v-if="batch.options.queue">
                    <div class="col-md-2 text-muted">Queue</div>
                    <div class="col">{{batch.options.queue}}</div>
                </div>
                <div class="row mb-2" v-if="batch.options.connection">
                    <div class="col-md-2 text-muted">Connection</div>
                    <div class="col">{{batch.options.connection}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Created</div>
                    <div class="col">{{ formatDateIso(batch.createdAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div class="row mb-2" v-if="batch.finishedAt">
                    <div class="col-md-2 text-muted">Finished</div>
                    <div class="col">{{ formatDateIso(batch.finishedAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div class="row mb-2" v-if="batch.cancelledAt">
                    <div class="col-md-2 text-muted">Cancelled</div>
                    <div class="col">{{ formatDateIso(batch.cancelledAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Total Jobs</div>
                    <div class="col">{{batch.totalJobs}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Pending Jobs</div>
                    <div class="col">{{batch.pendingJobs}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Failed Jobs</div>
                    <div class="col">{{batch.failedJobs}}</div>
                </div>
                <div class="row">
                    <div class="col-md-2 text-muted">Processed Jobs<br><small>(Including Failed)</small></div>
                    <div class="col">{{ (batch.processedJobs) }} ({{batch.progress}}%)</div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden mt-4" v-if="ready && failedJobs.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Failed Jobs</h2>
            </div>

            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Job</th>
                    <th class="text-end">Runtime</th>
                    <th class="text-end">Failed</th>
                </tr>
                </thead>

                <tbody>

                <tr v-for="failedJob in failedJobs" :key="failedJob.id">
                    <td>
                        <router-link :to="{ name: 'failed-jobs-preview', params: { jobId: failedJob.id }}">
                            {{ jobBaseName(failedJob.name) }}
                        </router-link>
                    </td>

                    <td class="text-end text-muted table-fit">
                        <span>{{ failedJob.failed_at && failedJob.reserved_at ? String(( failedJob.failed_at - failedJob.reserved_at ).toFixed(2))+'s' : '-' }}</span>
                    </td>

                    <td class="text-end text-muted table-fit">
                        {{ readableTimestamp(failedJob.failed_at) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>