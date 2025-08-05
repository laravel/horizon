<script setup lang="ts">
import { ref, watch, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import JobRow from './job-row.vue';
import Poll from '../../components/Poll.vue';

interface Job {
    id: string;
    name: string;
    queue: string;
    status: string;
    payload: {
        pushedAt: number;
        tags: string[];
        data: {
            command: string;
        };
    };
    completed_at?: number;
    reserved_at: number;
}

interface JobsResponse {
    jobs: Job[];
    total: number;
}

const route = useRoute();
const instance = getCurrentInstance();

const ready = ref(false);
const loadingNewEntries = ref(false);
const hasNewEntries = ref(false);
const page = ref(1);
const perPage = ref(50);
const totalPages = ref(1);
const jobs = ref<Job[]>([]);

onMounted(() => {
    updatePageTitle();
    loadJobs();
});

watch(() => route.path, () => {
    updatePageTitle();
    page.value = 1;
    loadJobs();
});

/**
 * Load the jobs of the given tag.
 */
const loadJobs = (starting = -1, refreshing = false) => {
    if (!refreshing) {
        ready.value = false;
    }

    const $http = instance?.appContext.config.globalProperties.$http;
    const $root = instance?.appContext.config.globalProperties.$root as any;
    
    if (!$http) return;

    $http.get<JobsResponse>(window.Horizon.basePath + '/api/jobs/' + route.params.type + '?starting_at=' + starting + '&limit=' + perPage.value)
        .then(response => {
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
    loadJobs(-1, false);
    hasNewEntries.value = false;
};

/**
 * Poll handler to refresh the jobs at regular intervals.
 */
const refreshJobsPeriodically = () => {
    if (page.value !== 1) {
        return;
    }

    loadJobs(-1, true);
};

/**
 * Load the jobs for the previous page.
 */
const previous = () => {
    loadJobs((page.value - 2) * perPage.value - 1);
    page.value -= 1;
    hasNewEntries.value = false;
};

/**
 * Load the jobs for the next page.
 */
const next = () => {
    loadJobs(page.value * perPage.value - 1);
    page.value += 1;
    hasNewEntries.value = false;
};

/**
 * Update the page title.
 */
const updatePageTitle = () => {
    document.title = route.params.type === 'pending'
        ? 'Horizon - Pending Jobs'
        : (
            route.params.type === 'silenced'
                ? 'Horizon - Silenced Jobs'
                : 'Horizon - Completed Jobs'
        );
};
</script>

<template>
    <div>
        <poll @poll="refreshJobsPeriodically" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0" v-if="route.params.type == 'pending'">Pending Jobs</h2>
                <h2 class="h6 m-0" v-if="route.params.type == 'completed'">Completed Jobs</h2>
                <h2 class="h6 m-0" v-if="route.params.type == 'silenced'">Silenced Jobs</h2>
            </div>

            <div v-if="!ready"
                 class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div v-if="ready && jobs.length == 0"
                 class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any jobs.</span>
            </div>

            <table v-if="ready && jobs.length > 0" class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th v-if="route.params.type=='pending'" class="text-end">Queued</th>
                        <th v-if="route.params.type=='completed' || route.params.type=='silenced'">Queued</th>
                        <th v-if="route.params.type=='completed' || route.params.type=='silenced'">Completed</th>
                        <th v-if="route.params.type=='completed' || route.params.type=='silenced'" class="text-end">Runtime</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                        <td colspan="100" class="text-center card-bg-secondary py-1">
                            <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                            <small v-if="loadingNewEntries">Loading...</small>
                        </td>
                    </tr>

                    <job-row v-for="job in jobs" :key="job.id" :job="job" />
                </tbody>
            </table>

            <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-sm" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-sm" :disabled="page>=totalPages">Next</button>
            </div>
        </div>
    </div>
</template>