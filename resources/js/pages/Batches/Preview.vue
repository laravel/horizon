<script setup>
import { Head, router, usePoll } from '@inertiajs/vue3';
import { ref } from 'vue';

const polling = ref(false);

usePoll(3000, {
    onStart: () => polling.value = true,
    onFinish: () => polling.value = false,
}, {
    keepAlive: false,
});

function retryFailedJob(id) {
    router.post(Horizon.url(`batches/retry/${id}`), {
        onSuccess: page => {
            router.reload();
        }
    });
}
</script>

<template>
    <div>
        <Head title="Horizon - Batches" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 v-if="polling" class="h6 m-0">Batch Preview</h2>
                <h2 v-if="!polling" class="h6 m-0">{{ batch.name || batch.id }}</h2>

                <button v-if="failedJobs.length > 0" class="btn btn-primary" @click.prevent="retryFailedJob(batch.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon" fill="currentColor" :class="{spin: retrying}">
                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                    </svg>

                    Retry Failed Jobs
                </button>
            </div>

            <div v-if="polling" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div v-if="!polling" class="card-body card-bg-secondary">
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">ID</div>
                    <div class="col">
                        {{ batch.id }}

                        <small v-if="batch.failedJobs > 0 && batch.totalJobs - batch.pendingJobs < batch.totalJobs" class="ms-1 badge badge-danger badge-sm">
                            Failures
                        </small>
                        <small v-if="batch.totalJobs - batch.pendingJobs == batch.totalJobs" class="ms-1 badge badge-success badge-sm">
                            Finished
                        </small>
                        <small v-if="batch.pendingJobs > 0 && !batch.failedJobs" class="ms-1 badge badge-secondary badge-sm">
                            Pending
                        </small>
                    </div>
                </div>
                <div v-if="batch.name" class="row mb-2">
                    <div class="col-md-2 text-muted">Name</div>
                    <div class="col">{{ batch.name }}</div>
                </div>
                <div v-if="batch.options.queue" class="row mb-2">
                    <div class="col-md-2 text-muted">Queue</div>
                    <div class="col">{{ batch.options.queue }}</div>
                </div>
                <div v-if="batch.options.connection" class="row mb-2">
                    <div class="col-md-2 text-muted">Connection</div>
                    <div class="col">{{ batch.options.connection }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Created</div>
                    <div class="col">{{ formatDateIso(batch.createdAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div v-if="batch.finishedAt" class="row mb-2">
                    <div class="col-md-2 text-muted">Finished</div>
                    <div class="col">{{ formatDateIso(batch.finishedAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div v-if="batch.cancelledAt" class="row mb-2">
                    <div class="col-md-2 text-muted">Cancelled</div>
                    <div class="col">{{ formatDateIso(batch.cancelledAt).format('YYYY-MM-DD HH:mm:ss') }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Total Jobs</div>
                    <div class="col">{{batch.totalJobs}}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Pending Jobs</div>
                    <div class="col">{{ batch.pendingJobs }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2 text-muted">Failed Jobs</div>
                    <div class="col">{{ batch.failedJobs }}</div>
                </div>
                <div class="row">
                    <div class="col-md-2 text-muted">Processed Jobs<br><small>(Including Failed)</small></div>
                    <div class="col">{{ batch.processedJobs }} ({{ batch.progress }}%)</div>
                </div>
            </div>
        </div>

        <div v-if="!polling && failedJobs.length" class="card overflow-hidden mt-4">
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

                <tr v-for="failedJob in failedJobs">
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
