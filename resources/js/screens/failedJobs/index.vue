<script setup lang="ts">
import { ref, watch, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import Poll from '../../components/Poll.vue';

interface RetryInfo {
    id: string;
    status: string;
}

interface JobPayload {
    attempts: number;
    retry_of?: string;
    tags?: string[];
}

interface FailedJob {
    id: string;
    name: string;
    queue: string;
    failed_at: number;
    reserved_at: number;
    retried_by: RetryInfo[];
    payload: JobPayload;
}

interface FailedJobsResponse {
    jobs: FailedJob[];
    total: number;
}

const route = useRoute();
const instance = getCurrentInstance();

const tagSearchPhrase = ref('');
const searchTimeout = ref<number | null>(null);
const ready = ref(false);
const loadingNewEntries = ref(false);
const hasNewEntries = ref(false);
const page = ref(1);
const perPage = ref(50);
const totalPages = ref(1);
const jobs = ref<FailedJob[]>([]);
const retryingJobs = ref<string[]>([]);

onMounted(() => {
    document.title = "Horizon - Failed Jobs";
});

watch(() => route.path, () => {
    page.value = 1;
    loadJobs();
});

watch(tagSearchPhrase, () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }

    searchTimeout.value = window.setTimeout(() => {
        loadJobs();
        refreshJobsPeriodically();
    }, 500);
});

/**
 * Load the jobs of the given tag.
 */
const loadJobs = (starting = 0, refreshing = false) => {
    if (!refreshing) {
        ready.value = false;
    }

    const tagQuery = tagSearchPhrase.value ? 'tag=' + tagSearchPhrase.value + '&' : '';
    const $http = instance?.appContext.config.globalProperties.$http;
    const $root = instance?.appContext.config.globalProperties.$root as any;
    
    if (!$http) return;

    $http.get<FailedJobsResponse>(window.Horizon.basePath + '/api/jobs/failed?' + tagQuery + 'starting_at=' + starting)
        .then(response => {
            if (!$root.autoLoadsNewEntries && refreshing && !response.data.jobs.length) {
                return;
            }

            if (!$root.autoLoadsNewEntries && refreshing && jobs.value.length && response.data.jobs[0]?.id !== jobs.value[0]?.id) {
                hasNewEntries.value = true;
            } else {
                jobs.value = response.data.jobs;
                totalPages.value = Math.ceil(response.data.total / perPage.value);
            }

            ready.value = true;
        });
};

const loadNewEntries = () => {
    jobs.value = [];
    loadJobs(0, false);
    hasNewEntries.value = false;
};

/**
 * Retry the given failed job.
 */
const retry = (id: string) => {
    if (isRetrying(id)) {
        return;
    }

    retryingJobs.value.push(id);

    const $http = instance?.appContext.config.globalProperties.$http;
    if (!$http) return;

    $http.post(window.Horizon.basePath + '/api/jobs/retry/' + id)
        .then(() => {
            setTimeout(() => {
                retryingJobs.value = retryingJobs.value.filter(job => job !== id);
            }, 5000);
        }).catch(() => {
            retryingJobs.value = retryingJobs.value.filter(job => job !== id);
        });
};

/**
 * Determine if the given job is currently retrying.
 */
const isRetrying = (id: string): boolean => {
    return retryingJobs.value.includes(id);
};

/**
 * Determine if the given job has completed.
 */
const hasCompleted = (job: FailedJob): boolean => {
    return !!job.retried_by.find(retry => retry.status === 'completed');
};

/**
 * Determine if the given job was retried.
 */
const wasRetried = (job: FailedJob): boolean => {
    return !!(job.retried_by && job.retried_by.length);
};

/**
 * Determine if the given job is a retry.
 */
const isRetry = (job: FailedJob): boolean => {
    return !!job.payload.retry_of;
};

/**
 * Construct the tooltip label for a retried job.
 */
const retriedJobTooltip = (job: FailedJob): string => {
    const lastRetry = job.retried_by[job.retried_by.length - 1];
    return `Total retries: ${job.retried_by.length}, Last retry status: ${upperFirst(lastRetry.status)}`;
};

/**
 * Poll handler to refresh the jobs at regular intervals.
 */
const refreshJobsPeriodically = () => {
    loadJobs((page.value - 1) * perPage.value, true);
};

/**
 * Load the jobs for the previous page.
 */
const previous = () => {
    loadJobs((page.value - 2) * perPage.value);
    page.value -= 1;
    hasNewEntries.value = false;
};

/**
 * Load the jobs for the next page.
 */
const next = () => {
    loadJobs(page.value * perPage.value);
    page.value += 1;
    hasNewEntries.value = false;
};

const jobBaseName = (name: string) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.jobBaseName(name);
};

const readableTimestamp = (timestamp: number) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.readableTimestamp(timestamp);
};

const upperFirst = (string: string) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.upperFirst(string);
};
</script>

<template>
    <div>
        <poll @poll="refreshJobsPeriodically" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Failed Jobs</h2>

                <div class="form-control-with-icon">
                    <div class="icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <input type="text" class="form-control w-100" v-model="tagSearchPhrase" placeholder="Search Tags">
                </div>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && jobs.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any failed jobs.</span>
            </div>

            <table v-if="ready && jobs.length > 0" class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Job</th>
                    <th class="text-end">Runtime</th>
                    <th>Failed</th>
                    <th class="text-end">Retry</th>
                </tr>
                </thead>

                <tbody>
                <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-2">
                        <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                        <small v-if="loadingNewEntries">Loading...</small>
                    </td>
                </tr>

                <tr v-for="job in jobs" :key="job.id">
                    <td>
                        <router-link :title="job.name" :to="{ name: 'failed-jobs-preview', params: { jobId: job.id }}">{{ jobBaseName(job.name) }}</router-link>

                        <small class="ms-1 badge bg-secondary badge-sm"
                               :title="retriedJobTooltip(job)"
                               v-if="wasRetried(job)">
                            Retried
                        </small>

                        <br>

                        <small class="text-muted">
                            Queue: {{job.queue}}
                            | Attempts: {{ job.payload.attempts }}
                            <span v-if="isRetry(job)">
                            | Retry of
                            <router-link :title="job.name" :to="{ name: 'failed-jobs-preview', params: { jobId: job.payload.retry_of }}">
                                {{ job.payload.retry_of?.split('-')[0] }}
                            </router-link>
                            </span>
                            <span v-if="job.payload.tags && job.payload.tags.length" class="text-break">
                            | Tags: {{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}
                            </span>
                        </small>
                    </td>

                    <td class="table-fit text-muted text-end">
                        <span>{{ job.failed_at ? String((job.failed_at - job.reserved_at).toFixed(2))+'s' : '-' }}</span>
                    </td>

                    <td class="table-fit text-muted">
                        {{ readableTimestamp(job.failed_at) }}
                    </td>

                    <td class="text-end table-fit">
                        <a href="#" title="Retry Job" @click.prevent="retry(job.id)" v-if="!hasCompleted(job)">
                            <svg class="fill-primary" viewBox="0 0 20 20" style="width: 1.25rem; height: 1.25rem;" :class="{spin: isRetrying(job.id)}">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-sm" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-sm" :disabled="page>=totalPages">Next</button>
            </div>
        </div>

    </div>
</template>