<template>
    <tr>
        <td>
            <router-link :title="job.name" :to="{ name: $route.params.type+'-jobs-preview', params: { jobId: job.id }}">
                {{ jobBaseName(job.name) }}
            </router-link>

            <small class="badge badge-secondary badge-sm"
                    v-tooltip:top="`Delayed for ${delayed}`"
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

        <td class="table-fit">
            {{ readableTimestamp(job.payload.pushedAt) }}
        </td>

        <td v-if="$route.params.type=='completed'" class="table-fit">
            {{ readableTimestamp(job.completed_at) }}
        </td>

        <td v-if="$route.params.type=='completed'" class="table-fit">
            <span>{{ job.completed_at ? (job.completed_at - job.reserved_at).toFixed(2)+'s' : '-' }}</span>
        </td>
    </tr>
</template>

<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'
    import moment from 'moment-timezone';

    export default {
        props: {
            job: {
                type: Object,
                required: true
            }
        },

        computed: {
            unserialized() {
                try {
                    return phpunserialize(this.job.payload.data.command);
                }catch(err){
                    //
                }
            },

            delayed() {
                if (this.unserialized && this.unserialized.delay && this.unserialized.delay.date) {
                    return moment.tz(this.unserialized.delay.date, this.unserialized.delay.timezone)
                        .fromNow(true);
                } else if (this.unserialized && this.unserialized.delay) {
                    return this.formatDate(this.job.payload.pushedAt).add(this.unserialized.delay, 'seconds')
                        .fromNow(true);
                }

                return null;
            },
        },
    }
</script>
