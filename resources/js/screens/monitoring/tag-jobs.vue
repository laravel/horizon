<script setup lang="ts">
import { ref, watch, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
import JobRow from './job-row.vue';
import Poll from '../../components/Poll.vue';

interface Props {
    type: 'jobs' | 'failed';
}

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
    failed_at?: number;
}

interface JobsResponse {
    jobs: Job[];
    total: number;
}

const props = defineProps<Props>();
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
    document.title = "Horizon - Monitoring";
    loadJobs(route.params.tag as string);
});

watch(() => route.path, () => {
    page.value = 1;
    loadJobs(route.params.tag as string);
});

/**
 * Load the jobs of the given tag.
 */
const loadJobs = (tag: string, starting = 0, refreshing = false) => {
    if (!refreshing) {
        ready.value = false;
    }

    const tagParam = props.type === 'failed' ? 'failed:' + tag : tag;

    const $http = instance?.appContext.config.globalProperties.$http;
    const $root = instance?.appContext.config.globalProperties.$root as any;
    
    if (!$http) return;

    $http.get<JobsResponse>(window.Horizon.basePath + '/api/monitoring/' + encodeURIComponent(tagParam) + '?starting_at=' + starting + '&limit=' + perPage.value + '&tag=' + encodeURIComponent(tagParam))
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

/**
 * Load new entries.
 */
const loadNewEntries = () => {
    jobs.value = [];
    loadJobs(route.params.tag as string, 0, false);
    hasNewEntries.value = false;
};

/**
 * Poll handler to refresh the jobs at regular intervals.
 */
const refreshJobsPeriodically = () => {
    if (page.value !== 1) {
        return;
    }

    loadJobs(route.params.tag as string, 0, true);
};

/**
 * Load the jobs for the previous page.
 */
const previous = () => {
    loadJobs(route.params.tag as string, (page.value - 2) * perPage.value);
    page.value -= 1;
    hasNewEntries.value = false;
};

/**
 * Load the jobs for the next page.
 */
const next = () => {
    loadJobs(route.params.tag as string, page.value * perPage.value);
    page.value += 1;
    hasNewEntries.value = false;
};
</script>

<template>
    <div>
        <poll @poll="refreshJobsPeriodically" />

        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>

            <span>Loading...</span>
        </div>


        <div v-if="ready && jobs.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <span>There aren't any jobs for this tag.</span>
        </div>

        <table v-if="ready && jobs.length > 0" class="table table-hover mb-0">
            <thead>
            <tr>
                <th>Job</th>
                <th>Queued</th>
                <th v-if="type == 'jobs'">Completed</th>
                <th class="text-end" v-if="type == 'jobs'">Runtime</th>
                <th class="text-end" v-if="type == 'failed'">Failed</th>
            </tr>
            </thead>

            <tbody>
            <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                <td colspan="100" class="text-center card-bg-secondary py-2">
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

</template>