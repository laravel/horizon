<template>
    <tr>
        <td>
            <router-link :title="job.name" :to="{ name: 'job-preview', params: { jobId: job.id, type: $route.params.type }}">
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
                    | Tags: {{ job.payload.tags && job.payload.tags.length ? job.payload.tags.slice(0,3).join(', ') : '' }}<span class="text-secondary" v-if="job.payload.tags.length > 3"> +{{ job.payload.tags.length - 3 }} more</span>
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
                const delay = this.unserialized && this.unserialized.delay;

                if (delay && delay.date) {
                    return moment.tz(delay.date, delay.timezone).fromNow(true);
                }

                if (delay && typeof delay === 'object') {
                    // A DateInterval / CarbonInterval instance serializes with
                    // y/m/d/h/i/s keys, which don't line up with moment's own
                    // shorthand unit keys (e.g. its "m" means minutes, not
                    // months), so they must be mapped explicitly.
                    return this.formatDate(this.job.payload.pushedAt).add({
                        years: delay.y,
                        months: delay.m,
                        days: delay.d,
                        hours: delay.h,
                        minutes: delay.i,
                        seconds: delay.s,
                    }).fromNow(true);
                }

                if (delay) {
                    return this.formatDate(this.job.payload.pushedAt).add(delay, 'seconds')
                        .fromNow(true);
                }

                if (this.job.delay > 0) {
                    return moment.duration(this.job.delay, 'seconds').humanize();
                }

                return null;
            },
        },
    }
</script>
