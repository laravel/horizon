<script type="text/ecmascript-6">
    import moment from 'moment';
    import { Modal } from 'bootstrap';
    import Tooltip from '../components/Tooltip.vue';

    export default {
        components: {
            Tooltip,
        },

        /**
         * The component's data.
         */
        data() {
            return {
                workers: [],
                workersReady: false,
                workload: [],
                workloadReady: false,
                /**
                 * Bumped when a pause/resume mutation starts so in-flight
                 * /api/workload polls cannot overwrite fresher local state.
                 */
                workloadGeneration: 0,
                queueActions: [],
                batches: {
                    available: false,
                    active: null,
                    previews: [],
                },
                pauseModal: null,
                pauseModalQueue: null,
                pauseIndefinitely: true,
                pauseDurationMinutes: 60,
                pauseDurationError: null,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Dashboard";
        },


        /**
         * Tear down the Bootstrap pause modal so SPA navigations cannot leave
         * a backdrop or body.modal-open lock behind.
         */
        beforeUnmount() {
            if (this.pauseModal) {
                this.pauseModal.hide();
                this.pauseModal.dispose();
                this.pauseModal = null;
            }

            this.pauseModalQueue = null;
            this.pauseDurationError = null;
        },


        /**
         * Clear stale duration errors as the user corrects input.
         */
        watch: {
            pauseDurationMinutes() {
                if (this.pauseDurationError && this.pauseDurationIsValid()) {
                    this.pauseDurationError = null;
                }
            },

            pauseIndefinitely(pauseIndefinitely) {
                if (pauseIndefinitely) {
                    this.pauseDurationError = null;
                }
            },
        },


        computed: {
            stats() {
                return this.$root.stats;
            },


            maxWaitTime() {
                return Object.values(this.stats.wait || {})[0] ?? null;
            },


            maxWaitQueue() {
                const queue = Object.keys(this.stats.wait || {})[0];

                return queue ? (queue.split(':')[1] || queue) : null;
            },

            /**
             * Whether database batching is available for the dashboard card.
             * Total retained count from /api/stats navigation; null means hide.
             */
            batchesAvailable() {
                return Number.isInteger(this.stats.navigation?.batches);
            },

            queuePausingSupported() {
                return this.workload.some(queue => queue.queue_pausing_supported
                    || queue.split_queues?.some(splitQueue => splitQueue.queue_pausing_supported));
            },

            /**
             * Aggregate the live queue structures already returned by the
             * workload endpoint.
             */
            pendingState() {
                if (!this.workloadReady) {
                    return {
                        total: null,
                        reserved: null,
                        ready: null,
                        delayed: null,
                    };
                }

                const counts = {
                    reserved: this.workloadCount('reserved'),
                    ready: this.workloadCount('length'),
                    delayed: this.workloadCount('delayed'),
                };

                return {
                    ...counts,
                    total: Object.values(counts).every(Number.isInteger)
                        ? counts.reserved + counts.ready + counts.delayed
                        : null,
                };
            },


            /**
             * Determine the recently failed job period label.
             */
            failedJobsPeriod() {
                return !this.$root.statsReady || !this.stats.periods?.failedJobs
                    ? 'Past 7 Days'
                    : `Past ${this.determinePeriod(this.stats.periods.failedJobs)}`;
            },


            /**
             * Determine the completed job retention period.
             */
            completedRetentionPeriod() {
                if (!this.$root.statsReady || !Number.isInteger(this.stats.periods?.completedJobs)) {
                    return null;
                }

                return moment.duration(
                    moment().diff(moment().subtract(this.stats.periods.completedJobs, "minutes"))
                ).humanize();
            },
        },


        methods: {
            /**
             * Load the workers stats.
             */
            loadWorkers() {
                return this.$http.get(Horizon.basePath + '/api/masters')
                    .then(response => {
                        this.workers = response.data;
                        this.workersReady = true;
                    });
            },


            /**
             * Load the workload stats.
             *
             * Ignores responses that started before a pause/resume mutation or
             * that settle while a queue action is still in progress.
             */
            loadWorkload() {
                const generation = this.workloadGeneration;

                return this.$http.get(Horizon.basePath + '/api/workload')
                    .then(response => {
                        if (generation !== this.workloadGeneration || this.queueActions.length > 0) {
                            return;
                        }

                        this.workload = response.data;
                        this.workloadReady = true;
                    });
            },


            /**
             * Load the dashboard-only batch overview.
             */
            loadBatches() {
                if (!this.batchesAvailable) {
                    this.batches = {
                        available: false,
                        active: null,
                        previews: [],
                    };

                    return Promise.resolve();
                }

                return this.$http.get(Horizon.basePath + '/api/batches/overview')
                    .then(response => {
                        this.batches = response.data;
                    });
            },

            queueActionKey(queue) {
                return `${queue.connection}:${queue.name}`;
            },

            queueActionInProgress(queue) {
                return this.queueActions.includes(this.queueActionKey(queue));
            },

            updateQueuePauseState(state) {
                this.workload = this.workload.map(queue => {
                    if (queue.connection === state.connection && queue.name === state.queue) {
                        return { ...queue, paused: state.paused };
                    }

                    if (!queue.split_queues) {
                        return queue;
                    }

                    return {
                        ...queue,
                        split_queues: queue.split_queues.map(splitQueue => {
                            return splitQueue.connection === state.connection && splitQueue.name === state.queue
                                ? { ...splitQueue, paused: state.paused }
                                : splitQueue;
                        }),
                    };
                });
            },

            toggleQueue(queue) {
                if (!queue.queue_pausing_supported || this.queueActionInProgress(queue)) {
                    return;
                }

                if (queue.paused) {
                    this.sendQueuePauseRequest(queue);

                    return;
                }

                if (queue.timed_queue_pausing_supported) {
                    this.openPauseModal(queue);

                    return;
                }

                this.$root.alert.type = 'confirmation';
                this.$root.alert.message = 'Are you sure you want to pause this queue?';
                this.$root.alert.confirmationProceed = () => {
                    this.sendQueuePauseRequest(queue);
                };
            },

            openPauseModal(queue) {
                this.pauseModalQueue = queue;
                this.pauseIndefinitely = true;
                this.pauseDurationMinutes = 60;
                this.pauseDurationError = null;

                this.pauseModal = Modal.getOrCreateInstance(
                    document.getElementById('pauseQueueModal'),
                    { backdrop: 'static' },
                );
                this.pauseModal.show();
            },

            cancelPauseModal() {
                if (this.pauseModal) {
                    this.pauseModal.hide();
                }

                this.pauseModalQueue = null;
                this.pauseDurationError = null;
            },

            /**
             * Whether the timed pause duration is an integer in the allowed range.
             */
            pauseDurationIsValid() {
                const duration = Number(this.pauseDurationMinutes);

                return Number.isInteger(duration) && duration >= 1 && duration <= 525600;
            },

            confirmPauseModal() {
                const queue = this.pauseModalQueue;

                if (!queue) {
                    return;
                }

                if (!this.pauseIndefinitely) {
                    if (!this.pauseDurationIsValid()) {
                        this.pauseDurationError = 'Enter a pause duration between 1 and 525600 minutes.';

                        return;
                    }
                }

                const durationMinutes = this.pauseIndefinitely
                    ? null
                    : Number(this.pauseDurationMinutes);

                if (this.pauseModal) {
                    this.pauseModal.hide();
                }

                this.pauseDurationError = null;
                this.pauseModalQueue = null;
                this.sendQueuePauseRequest(queue, durationMinutes);
            },

            sendQueuePauseRequest(queue, durationMinutes = null) {
                if (this.queueActionInProgress(queue)) {
                    return;
                }

                const key = this.queueActionKey(queue);
                const endpoint = Horizon.basePath
                    + '/api/queues/'
                    + encodeURIComponent(queue.connection)
                    + '/'
                    + encodeURIComponent(queue.name)
                    + '/pause';

                this.queueActions.push(key);
                this.workloadGeneration++;

                const request = queue.paused
                    ? this.$http.delete(endpoint)
                    : this.$http.post(
                        endpoint,
                        durationMinutes === null ? {} : { duration_minutes: durationMinutes },
                    );

                request
                    .then(response => {
                        this.updateQueuePauseState(response.data);
                    })
                    .catch(() => {
                        this.$root.alert.type = 'error';
                        this.$root.alert.message = `Unable to ${queue.paused ? 'resume' : 'pause'} the ${queue.name} queue.`;
                    })
                    .finally(() => {
                        this.queueActions = this.queueActions.filter(action => action !== key);

                        if (this.queueActions.length === 0) {
                            // Invalidate any poll that started during the mutation window
                            // before the authoritative post-mutation refresh.
                            this.workloadGeneration++;
                            this.loadWorkload();
                        }
                    });
            },


            /**
             * Poll handler to refresh the stats at regular intervals.
             */
            refreshStatsPeriodically() {
                return Promise.all([
                    this.loadWorkers(),
                    this.loadWorkload(),
                    this.loadBatches(),
                ]);
            },

            /**
             * Format a live count while keeping unavailable values truthful.
             */
            statCount(value) {
                return Number.isInteger(value) ? value.toLocaleString() : '—';
            },


            /**
             * Sum an available workload field without turning missing data
             * into a misleading zero.
             */
            workloadCount(field) {
                return this.workload.every(queue => Number.isInteger(queue[field]))
                    ? this.workload.reduce((total, queue) => total + queue[field], 0)
                    : null;
            },


            /**
             *  Count processes for the given supervisor.
             */
            countProcesses(processes) {
                return Object.values(processes).reduce((total, value) => total + value, 0).toLocaleString();
            },


            /**
             *  Format the Supervisor display name.
             */
            superVisorDisplayName(supervisor, worker) {
                return supervisor.replace(worker + ':', '');
            },


            /**
             * Format a wait estimate from integer seconds into a compact label.
             */
            humanTime(time) {
                const seconds = Math.max(0, Math.floor(Number(time) || 0));

                if (seconds === 0) {
                    return 'Sub-second';
                }

                if (seconds < 60) {
                    return seconds + 's';
                }

                if (seconds < 3600) {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = seconds % 60;

                    return remainingSeconds
                        ? minutes + 'm ' + remainingSeconds + 's'
                        : minutes + 'm';
                }

                if (seconds < 86400) {
                    const hours = Math.floor(seconds / 3600);
                    const remainingMinutes = Math.floor((seconds % 3600) / 60);

                    return remainingMinutes
                        ? hours + 'h ' + remainingMinutes + 'm'
                        : hours + 'h';
                }

                const days = Math.floor(seconds / 86400);
                const remainingHours = Math.floor((seconds % 86400) / 3600);

                return remainingHours
                    ? days + 'd ' + remainingHours + 'h'
                    : days + 'd';
            },


            /**
             * Determine the unit for the given timeframe.
             */
            determinePeriod(minutes) {
                return moment.duration(moment().diff(moment().subtract(minutes, "minutes"))).humanize().replace(/^An?\s/i, '').replace(/^(.)|\s(.)/g, function ($1) {
                    return $1.toUpperCase();
                });
            },
        }
    }
</script>

<template>
    <div class="dashboard">
        <poll @poll="refreshStatsPeriodically" :interval="5" />

        <section class="card dashboard-section dashboard-overview">
            <div class="card-header d-flex align-items-center">
                <h2 class="h6 m-0">Overview</h2>
            </div>

            <div
                class="dashboard-overview-grid"
                :class="{ 'dashboard-overview-grid-without-batches': !batchesAvailable }"
            >
                <router-link
                    :to="{ name: 'jobs', params: { type: 'pending' } }"
                    class="dashboard-stat dashboard-stat-link"
                >
                    <small class="dashboard-stat-label">Pending Jobs</small>
                    <p class="dashboard-stat-value">
                        {{ statCount(pendingState.total ?? stats.navigation?.pending) }}
                    </p>
                    <div class="dashboard-stat-details">
                        <small class="dashboard-stat-detail-row">
                            <tooltip>
                                <template #default="{ tooltipId }">
                                    <span :aria-describedby="tooltipId" tabindex="0">Reserved</span>
                                </template>
                                <template #content>
                                    Jobs currently being worked on.
                                </template>
                            </tooltip>
                            <strong>{{ statCount(pendingState.reserved) }}</strong>
                        </small>
                        <small class="dashboard-stat-detail-row">
                            <tooltip>
                                <template #default="{ tooltipId }">
                                    <span :aria-describedby="tooltipId" tabindex="0">Ready</span>
                                </template>
                                <template #content>
                                    Jobs waiting for an available worker.
                                </template>
                            </tooltip>
                            <strong>{{ statCount(pendingState.ready) }}</strong>
                        </small>
                        <small class="dashboard-stat-detail-row">
                            <tooltip>
                                <template #default="{ tooltipId }">
                                    <span :aria-describedby="tooltipId" tabindex="0">Delayed</span>
                                </template>
                                <template #content>
                                    Jobs scheduled to run later.
                                </template>
                            </tooltip>
                            <strong>{{ statCount(pendingState.delayed) }}</strong>
                        </small>
                    </div>
                </router-link>

                <router-link
                    :to="{ name: 'failed-jobs' }"
                    class="dashboard-stat dashboard-stat-link"
                >
                    <small class="dashboard-stat-label">Failed Jobs</small>
                    <p class="dashboard-stat-value">
                        {{ statCount(stats.navigation?.failed) }}
                    </p>
                    <div class="dashboard-stat-details">
                        <small class="dashboard-stat-detail-row">
                            <span>Past hour</span>
                            <strong>{{ statCount(stats.failedJobsPastHour) }}</strong>
                        </small>
                        <small class="dashboard-stat-detail-row">
                            <span>Past 24 hours</span>
                            <strong>{{ statCount(stats.failedJobsPastDay) }}</strong>
                        </small>
                        <small class="dashboard-stat-detail-row">
                            <span>{{ failedJobsPeriod }}</span>
                            <strong>{{ statCount(stats.failedJobs) }}</strong>
                        </small>
                    </div>
                </router-link>

                <router-link
                    :to="{ name: 'jobs', params: { type: 'completed' } }"
                    class="dashboard-stat dashboard-stat-link"
                >
                    <small class="dashboard-stat-label">Completed Jobs</small>
                    <p class="dashboard-stat-value">
                        <tooltip v-if="completedRetentionPeriod">
                            <template #default="{ tooltipId }">
                                <span :aria-describedby="tooltipId" tabindex="0">
                                    {{ statCount(stats.navigation?.completed) }}
                                </span>
                            </template>
                            <template #content>
                                Completed jobs are retained for {{ completedRetentionPeriod }}.
                            </template>
                        </tooltip>
                        <template v-else>
                            {{ statCount(stats.navigation?.completed) }}
                        </template>
                    </p>
                    <div class="dashboard-stat-details">
                        <small class="dashboard-stat-detail-row">
                            <span>Jobs per minute</span>
                            <strong>{{ statCount(stats.jobsPerMinute) }}</strong>
                        </small>
                        <small class="dashboard-stat-detail-row">
                            <span>Throughput</span>
                            <strong>{{ statCount(stats.throughput) }}</strong>
                        </small>
                        <small class="dashboard-stat-detail-row">
                            <span>Silenced Jobs</span>
                            <strong>{{ statCount(stats.navigation?.silenced) }}</strong>
                        </small>
                    </div>
                </router-link>

                <div class="dashboard-stat dashboard-batches-stat" v-if="batchesAvailable">
                    <router-link :to="{ name: 'batches' }" class="dashboard-stat-link">
                        <small class="dashboard-stat-label">Batches in progress</small>
                        <p class="dashboard-stat-value">
                            {{ statCount(batches.active) }}
                        </p>
                    </router-link>

                    <div class="dashboard-batch-previews" v-if="batches?.previews?.length">
                        <router-link
                            v-for="batch in batches.previews.slice(0, 3)"
                            :key="batch.id"
                            :to="{ name: 'batches-preview', params: { batchId: batch.id } }"
                            class="dashboard-batch-preview"
                        >
                            <span class="dashboard-batch-name">{{ batch.name }}</span>
                            <span
                                class="dashboard-batch-progress"
                                role="progressbar"
                                aria-label="Batch progress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-valuenow="batch.progress"
                                :aria-valuetext="batch.progress + '%'"
                            >
                                <svg viewBox="0 0 16 16" aria-hidden="true">
                                    <circle
                                        class="dashboard-batch-progress-track"
                                        cx="8"
                                        cy="8"
                                        r="6.5"
                                        stroke-width="2.5"
                                    />
                                    <circle
                                        class="dashboard-batch-progress-value"
                                        cx="8"
                                        cy="8"
                                        r="6.5"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-dasharray="40.84"
                                        :stroke-dashoffset="40.84 * (1 - batch.progress / 100)"
                                    />
                                </svg>
                                <span>{{ batch.progress }}%</span>
                            </span>
                        </router-link>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="card dashboard-section dashboard-workload"
            v-if="$root.statsUnavailable || workloadReady"
        >
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h2 class="h6 m-0">Current Workload</h2>
            </div>

            <table class="table dashboard-empty-table mb-0" v-if="$root.statsUnavailable">
                <tbody>
                <table-empty
                    :columns="1"
                    title="Current workload unavailable"
                    description="Horizon could not load the current queue workload."
                    icon="queues"
                ></table-empty>
                </tbody>
            </table>

            <div class="dashboard-summary-grid" v-if="!$root.statsUnavailable && workload.length">
                <div class="dashboard-summary">
                    <small class="dashboard-stat-label">Total Processes</small>
                    <p class="dashboard-summary-value">
                        {{ statCount(stats.processes) }}
                    </p>
                </div>

                <div class="dashboard-summary">
                    <small class="dashboard-stat-label">Max Wait Time</small>
                    <p class="dashboard-summary-value">
                        <tooltip v-if="maxWaitQueue">
                            <template #default="{ tooltipId }">
                                <span :aria-describedby="tooltipId" tabindex="0">
                                    {{ maxWaitTime !== null ? humanTime(maxWaitTime) : '—' }}
                                </span>
                            </template>
                            <template #content>
                                Queue with the maximum wait time: {{ maxWaitQueue }}.
                            </template>
                        </tooltip>
                        <template v-else>
                            {{ maxWaitTime !== null ? humanTime(maxWaitTime) : '—' }}
                        </template>
                    </p>
                </div>

                <div class="dashboard-summary">
                    <small class="dashboard-stat-label">Max Runtime</small>
                    <p class="dashboard-summary-value">
                        <tooltip v-if="stats.queueWithMaxRuntime && typeof stats.maxRuntime === 'number'">
                            <template #default="{ tooltipId }">
                                <span :aria-describedby="tooltipId" tabindex="0">
                                    {{ stats.queueWithMaxRuntime }}
                                </span>
                            </template>
                            <template #content>
                                Average runtime for {{ stats.queueWithMaxRuntime }}: {{ stats.maxRuntime.toLocaleString() }}s.
                            </template>
                        </tooltip>
                        <template v-else>
                            {{ stats.queueWithMaxRuntime || '—' }}
                        </template>
                    </p>
                </div>

                <div class="dashboard-summary">
                    <small class="dashboard-stat-label">Max Throughput</small>
                    <p class="dashboard-summary-value">
                        <tooltip
                            v-if="stats.queueWithMaxThroughput && typeof stats.maxThroughput === 'number'"
                            class="horizon-tooltip-left"
                        >
                            <template #default="{ tooltipId }">
                                <span :aria-describedby="tooltipId" tabindex="0">
                                    {{ stats.queueWithMaxThroughput }}
                                </span>
                            </template>
                            <template #content>
                                Throughput for {{ stats.queueWithMaxThroughput }} since the last metrics snapshot: {{ statCount(stats.maxThroughput) }} jobs.
                            </template>
                        </tooltip>
                        <template v-else>
                            {{ stats.queueWithMaxThroughput || '—' }}
                        </template>
                    </p>
                </div>

                <div class="dashboard-summary">
                    <small class="dashboard-stat-label">Hourly Pressure</small>
                    <p class="dashboard-summary-value">
                        <tooltip class="horizon-tooltip-left">
                            <template #default="{ tooltipId }">
                                <span :aria-describedby="tooltipId" tabindex="0">
                                    {{ statCount(stats.recentJobsPastHour) }}
                                </span>
                            </template>
                            <template #content>
                                The number of jobs received by Horizon in the past hour.
                            </template>
                        </tooltip>
                    </p>
                </div>
            </div>

            <table class="table table-hover mb-0" v-if="!$root.statsUnavailable && workload.length">
                <thead>
                <tr>
                    <th>Queue</th>
                    <th class="text-end" style="width: 120px;">Ready Jobs</th>
                    <th class="text-end" style="width: 120px;">Processes</th>
                    <th class="text-end" style="width: 120px;">Throughput</th>
                    <th class="text-end" style="width: 180px;">Wait</th>
                    <th class="text-end dashboard-queue-actions-column" v-if="queuePausingSupported">Actions</th>
                </tr>
                </thead>

                <tbody>
                    <template v-for="queue in workload" :key="queue.connection + ':' + queue.name">
                        <tr>
                            <td :class="{ 'fw-bold': queue.split_queues }">
                                <span>{{ queue.name.replace(/,/g, ', ') }}</span>
                                <small
                                    class="badge badge-warning badge-sm rounded-pill ms-2"
                                    v-if="queue.paused"
                                >
                                    Paused
                                </small>
                            </td>
                            <td class="text-end text-muted" :class="{ 'fw-bold': queue.split_queues }">{{ queue.length ? queue.length.toLocaleString() : 0 }}</td>
                            <td class="text-end text-muted" :class="{ 'fw-bold': queue.split_queues }">{{ queue.processes ? queue.processes.toLocaleString() : 0 }}</td>
                            <td class="text-end text-muted" :class="{ 'fw-bold': queue.split_queues }">{{ statCount(queue.throughput) }}</td>
                            <td class="text-end text-muted" :class="{ 'fw-bold': queue.split_queues }">{{ humanTime(queue.wait) }}</td>
                            <td class="text-end dashboard-queue-actions-column" v-if="queuePausingSupported">
                                <button
                                    type="button"
                                    class="queue-control-action"
                                    :title="queue.paused ? `Resume ${queue.name} queue` : `Pause ${queue.name} queue`"
                                    :aria-label="queue.paused ? `Resume ${queue.name} queue` : `Pause ${queue.name} queue`"
                                    :aria-busy="queueActionInProgress(queue)"
                                    :disabled="queueActionInProgress(queue)"
                                    @click="toggleQueue(queue)"
                                    v-if="queue.queue_pausing_supported && !queue.split_queues"
                                >
                                    <svg v-if="queue.paused" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M6.5 4.61a1 1 0 0 1 1.54-.84l7 4.39a1 1 0 0 1 0 1.68l-7 4.39a1 1 0 0 1-1.54-.84V4.61Z" />
                                    </svg>
                                    <svg v-else viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M5.75 4.5A1.25 1.25 0 0 1 7 3.25h1A1.25 1.25 0 0 1 9.25 4.5v11A1.25 1.25 0 0 1 8 16.75H7a1.25 1.25 0 0 1-1.25-1.25v-11ZM10.75 4.5A1.25 1.25 0 0 1 12 3.25h1a1.25 1.25 0 0 1 1.25 1.25v11A1.25 1.25 0 0 1 13 16.75h-1a1.25 1.25 0 0 1-1.25-1.25v-11Z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <tr v-for="split_queue in queue.split_queues" :key="split_queue.name">
                            <td class="dashboard-split-queue">
                                <svg class="icon info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>

                                <span>{{ split_queue.name.replace(/,/g, ', ') }}</span>
                                <small
                                    class="badge badge-warning badge-sm rounded-pill ms-2"
                                    v-if="split_queue.paused"
                                >
                                    Paused
                                </small>
                            </td>
                            <td class="text-end text-muted">{{ split_queue.length ? split_queue.length.toLocaleString() : 0 }}</td>
                            <td class="text-end text-muted">—</td>
                            <td class="text-end text-muted">{{ statCount(split_queue.throughput) }}</td>
                            <td class="text-end text-muted">{{ humanTime(split_queue.wait) }}</td>
                            <td class="text-end dashboard-queue-actions-column" v-if="queuePausingSupported">
                                <button
                                    type="button"
                                    class="queue-control-action"
                                    :title="split_queue.paused ? `Resume ${split_queue.name} queue` : `Pause ${split_queue.name} queue`"
                                    :aria-label="split_queue.paused ? `Resume ${split_queue.name} queue` : `Pause ${split_queue.name} queue`"
                                    :aria-busy="queueActionInProgress(split_queue)"
                                    :disabled="queueActionInProgress(split_queue)"
                                    @click="toggleQueue(split_queue)"
                                    v-if="split_queue.queue_pausing_supported"
                                >
                                    <svg v-if="split_queue.paused" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M6.5 4.61a1 1 0 0 1 1.54-.84l7 4.39a1 1 0 0 1 0 1.68l-7 4.39a1 1 0 0 1-1.54-.84V4.61Z" />
                                    </svg>
                                    <svg v-else viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M5.75 4.5A1.25 1.25 0 0 1 7 3.25h1A1.25 1.25 0 0 1 9.25 4.5v11A1.25 1.25 0 0 1 8 16.75H7a1.25 1.25 0 0 1-1.25-1.25v-11ZM10.75 4.5A1.25 1.25 0 0 1 12 3.25h1a1.25 1.25 0 0 1 1.25 1.25v11A1.25 1.25 0 0 1 13 16.75h-1a1.25 1.25 0 0 1-1.25-1.25v-11Z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <table class="table dashboard-empty-table mb-0" v-if="!$root.statsUnavailable && workloadReady && !workload.length">
                <tbody>
                <table-empty
                    :columns="1"
                    title="All queues are clear"
                    description="Horizon has no queued workload right now."
                    icon="queues"
                ></table-empty>
                </tbody>
            </table>
        </section>

        <section
            class="card dashboard-section dashboard-instances"
            v-if="$root.statsUnavailable || (workersReady && !Object.keys(workers).length)"
        >
            <div class="card-header d-flex align-items-center">
                <h2 class="h6 m-0">Instances</h2>
            </div>

            <table class="table dashboard-empty-table mb-0">
                <tbody>
                <table-empty
                    v-if="$root.statsUnavailable"
                    :columns="1"
                    title="Horizon instances unavailable"
                    description="Horizon could not load the active masters and supervisors."
                    icon="instances"
                ></table-empty>

                <table-empty
                    v-else
                    :columns="1"
                    title="No Horizon instances"
                    description="Run php artisan horizon to start an instance and start processing queues."
                    icon="instances"
                ></table-empty>
                </tbody>
            </table>
        </section>

        <section
            class="dashboard-section dashboard-masters"
            v-else-if="workersReady && Object.keys(workers).length"
        >
            <article class="card dashboard-master" v-for="worker in workers" :key="worker.name">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="h6 m-0">{{ worker.name }}</h3>

                    <small
                        class="badge badge-success badge-sm rounded-pill"
                        v-if="worker.status === 'running'"
                    >
                        Running
                    </small>

                    <small
                        class="badge badge-warning badge-sm rounded-pill"
                        v-if="worker.status === 'paused'"
                    >
                        Paused
                    </small>
                </div>

                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Supervisor</th>
                        <th>Connection</th>
                        <th>Queues</th>
                        <th class="text-end" style="width: 120px;">Processes</th>
                        <th class="text-end" style="width: 150px;">Balancing</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr v-for="supervisor in worker.supervisors" :key="supervisor.name">
                        <td>{{ superVisorDisplayName(supervisor.name, worker.name) }}</td>
                        <td class="text-muted">{{ supervisor.options.connection }}</td>
                        <td class="text-muted">{{ supervisor.options.queue.replace(/,/g, ', ') }}</td>
                        <td class="text-end text-muted">{{ countProcesses(supervisor.processes) }}</td>
                        <td class="text-end text-muted" v-if="supervisor.options.balance">
                            {{ upperFirst(supervisor.options.balance) }}
                        </td>
                        <td class="text-end text-muted" v-else>
                            Fixed
                        </td>
                    </tr>
                    </tbody>
                </table>
            </article>
        </section>

        <div class="modal horizon-form-modal" id="pauseQueueModal" tabindex="-1" role="dialog" aria-labelledby="pauseQueueModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="pauseQueueModalLabel">
                            {{ pauseModalQueue ? 'Pause ' + pauseModalQueue.name + ' queue' : 'Pause queue' }}
                        </h2>
                    </div>

                    <div class="modal-body">
                        <div class="form-check mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="pauseIndefinitely"
                                v-model="pauseIndefinitely"
                            >
                            <label class="form-check-label" for="pauseIndefinitely">
                                Pause indefinitely
                            </label>
                        </div>

                        <div v-if="!pauseIndefinitely">
                            <label class="form-label" for="pauseDurationMinutes">Duration (minutes)</label>
                            <input
                                id="pauseDurationMinutes"
                                type="number"
                                class="form-control"
                                :class="{ 'is-invalid': pauseDurationError }"
                                min="1"
                                max="525600"
                                step="1"
                                v-model.number="pauseDurationMinutes"
                                :aria-invalid="pauseDurationError ? 'true' : 'false'"
                                :aria-describedby="pauseDurationError
                                    ? 'pauseDurationHelp pauseDurationError'
                                    : 'pauseDurationHelp'"
                            >
                            <small id="pauseDurationHelp" class="text-muted">Between 1 and 525600 minutes (one year).</small>
                            <div
                                v-if="pauseDurationError"
                                id="pauseDurationError"
                                class="invalid-feedback d-block"
                            >
                                {{ pauseDurationError }}
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-start flex-row-reverse">
                        <button class="btn btn-primary" @click="confirmPauseModal">
                            Pause queue
                        </button>

                        <button class="btn" @click="cancelPauseModal">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
