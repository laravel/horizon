<script setup>
import phpunserialize from 'phpunserialize';

const props = defineProps({
    job: { type: Object, required: true }
});

const unserialized = computed(() => {
    try {
        return phpunserialize(props.job.payload.data.command);
    } catch (err) {
        //
    }
});

const delayed = computed(() => {
    if (unserialized.value && unserialized.value.delay && unserialized.value.delay.date) {
        return moment.tz(unserialized.value.delay.date, unserialized.value.delay.timezone)
            .fromNow(true);
    } else if (unserialized.value && unserialized.value.delay) {
        return formatDate(props.job.payload.pushedAt).add(unserialized.value.delay, 'seconds')
            .fromNow(true);
    }

    return null;
});
</script>

<template>
    <tr>
        <td>
            <router-link :title="job.name" :to="{ name: 'job-preview', params: { jobId: job.id, type: $route.params.type }}">
                {{ jobBaseName(job.name) }}
            </router-link>

            <small
v-if="delayed && (job.status == 'reserved' || job.status == 'pending')"
                class="ms-1 badge bg-secondary badge-sm"
                :title="`Delayed for ${delayed}`">
                Delayed
            </small>

            <br>

            <small class="text-muted">
                Queue: {{ job.queue }}

                <span v-if="job.payload.tags && job.payload.tags.length" class="text-break">
                    | Tags: {{ job.payload.tags && job.payload.tags.length ? job.payload.tags.slice(0,3).join(', ') : '' }}<span v-if="job.payload.tags.length > 3"> ({{ job.payload.tags.length - 3 }} more)</span>
                </span>
            </small>
        </td>

        <td class="table-fit text-muted">
            {{ readableTimestamp(job.payload.pushedAt) }}
        </td>

        <td v-if="$route.params.type=='completed' || $route.params.type=='silenced'" class="table-fit text-muted">
            {{ readableTimestamp(job.completed_at) }}
        </td>

        <td v-if="$route.params.type=='completed' || $route.params.type=='silenced'" class="table-fit text-end text-muted">
            <span>{{ job.completed_at ? (job.completed_at - job.reserved_at).toFixed(2)+'s' : '-' }}</span>
        </td>
    </tr>
</template>
