<template>
    <tr>
        <td>
            <router-link :title="job.name" :to="{ name: 'job-preview', params: { jobId: job.id, type: route.params.type }}">
                {{ jobBaseName(job.name) }}
            </router-link>

            <small class="ms-1 badge bg-secondary badge-sm"
                :title="`Delayed for ${delayed}`"
                v-if="delayed && (job.status == 'reserved' || job.status == 'pending')">
                Delayed
            </small>

            <br>

            <small class="text-muted">
                Queue: {{job.queue}}

                <span v-if="job.payload.tags && job.payload.tags.length" class="text-break">
                    | Tags: {{ job.payload.tags && job.payload.tags.length ? job.payload.tags.slice(0,3).join(', ') : '' }}<span v-if="job.payload.tags.length > 3"> ({{ job.payload.tags.length - 3 }} more)</span>
                </span>
            </small>
        </td>

        <td class="table-fit text-muted">
            {{ readableTimestamp(job.payload.pushedAt) }}
        </td>

        <td v-if="route.params.type=='completed' || route.params.type=='silenced'" class="table-fit text-muted">
            {{ readableTimestamp(job.completed_at) }}
        </td>

        <td v-if="route.params.type=='completed' || route.params.type=='silenced'" class="table-fit text-end text-muted">
            <span>{{ job.completed_at ? (job.completed_at - job.reserved_at).toFixed(2)+'s' : '-' }}</span>
        </td>
    </tr>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router';
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
}

interface Props {
    job: Job;
}

interface DelayData {
    delay?: {
        date?: string;
        timezone?: string;
    } | number;
}

const props = defineProps<Props>();
const route = useRoute();
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
        if (typeof unserialized.value.delay === 'object' && unserialized.value.delay.date) {
            return moment.tz(unserialized.value.delay.date, unserialized.value.delay.timezone || 'UTC')
                .fromNow(true);
        } else if (typeof unserialized.value.delay === 'number') {
            const baseMixin = instance?.appContext.config.globalProperties as any;
            return baseMixin.formatDate(props.job.payload.pushedAt).add(unserialized.value.delay, 'seconds')
                .fromNow(true);
        }
    }

    return null;
});

const jobBaseName = (name: string) => {
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.jobBaseName(name);
};

const readableTimestamp = (timestamp: number | undefined) => {
    if (!timestamp) return '-';
    const baseMixin = instance?.appContext.config.globalProperties as any;
    return baseMixin.readableTimestamp(timestamp);
};
</script>
