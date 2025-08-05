<script setup lang="ts">
import { computed, getCurrentInstance } from 'vue';
import { unserialize } from 'phpunserialize';
import moment from 'moment-timezone';

interface JobPayload {
    pushedAt: number;
    tags: string[];
    data: {
        command: string;
    };
}

interface Job {
    id: string;
    name: string;
    queue: string;
    status: string;
    payload: JobPayload;
    completed_at?: number;
    reserved_at: number;
    failed_at?: number;
}

interface Props {
    job: Job;
}

interface DelayData {
    delay?: {
        date: string;
        timezone: string;
    };
}

const props = defineProps<Props>();
const instance = getCurrentInstance();

const unserialized = computed<DelayData | null>(() => {
    try {
        return unserialize(props.job.payload.data.command) as DelayData;
    } catch (err) {
        return null;
    }
});

const delayed = computed<string | null>(() => {
    if (unserialized.value && unserialized.value.delay) {
        return moment.tz(unserialized.value.delay.date, unserialized.value.delay.timezone)
            .fromNow(true);
    }

    return null;
});

const jobBaseName = (name: string) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.jobBaseName(name);
};

const readableTimestamp = (timestamp: number) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.readableTimestamp(timestamp);
};

// Access parent component's type property
const parentType = computed(() => {
    return (instance?.parent as any)?.type || 'jobs';
});
</script>

<template>
    <tr>
        <td>
            <router-link :title="job.name" :to="{ name: 'job-preview', params: { jobId: job.id, type: parentType }}">
                {{ jobBaseName(job.name) }}
            </router-link>

            <small class="badge bg-secondary badge-sm" :title="`Delayed for ${delayed}`"
                   v-if="delayed && (job.status == 'reserved' || job.status == 'pending')">
                Delayed
            </small>

            <br>

            <small class="text-muted">
                Queue: {{job.queue}}

                <span v-if="job.payload.tags.length">
                    | Tags: {{ job.payload.tags && job.payload.tags.length ? job.payload.tags.slice(0,3).join(', ') : '' }}<span v-if="job.payload.tags.length > 3"> ({{ job.payload.tags.length - 3 }} more)</span>
                </span>
            </small>
        </td>

        <td class="table-fit text-muted">
            {{ readableTimestamp(job.payload.pushedAt) }}
        </td>

        <td v-if="parentType == 'jobs'" class="table-fit text-muted">
            {{ job.completed_at ? readableTimestamp(job.completed_at) : '-' }}
        </td>

        <td v-if="parentType == 'jobs'" class="table-fit text-muted">
            <span>{{ job.completed_at ? (job.completed_at - job.reserved_at).toFixed(2)+'s' : '-' }}</span>
        </td>

        <td v-if="parentType == 'failed'" class="table-fit text-muted">
            {{ job.failed_at ? readableTimestamp(job.failed_at) : '-' }}
        </td>
    </tr>
</template>