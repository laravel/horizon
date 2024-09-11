<script type="text/ecmascript-6">
    import moment from 'moment';

    export default {
        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                stats: {},
                workers: [],
                workload: [],
                ready: false,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Dashboard";

            this.refreshStatsPeriodically();
        },


        /**
         * Clean after the component is unmounted.
         */
        unmounted() {
            clearTimeout(this.timeout);
        },


        computed: {
            /**
             * Determine the recent job period label.
             */
            recentJobsPeriod() {
                return !this.ready
                    ? 'Jobs Past Hour'
                    : `Jobs Past ${this.determinePeriod(this.stats.periods.recentJobs)}`;
            },


            /**
             * Determine the recently failed job period label.
             */
            failedJobsPeriod() {
                return !this.ready
                    ? 'Failed Jobs Past 7 Days'
                    : `Failed Jobs Past ${this.determinePeriod(this.stats.periods.failedJobs)}`;
            },
        },


        methods: {
            /**
             * Load the general stats.
             */
            loadStats() {
                return this.$http.get(Horizon.basePath + '/api/stats')
                    .then(response => {
                        this.stats = response.data;

                        if (Object.values(response.data.wait)[0]) {
                            this.stats.max_wait_time = Object.values(response.data.wait)[0];
                            this.stats.max_wait_queue = Object.keys(response.data.wait)[0].split(':')[1];
                        }
                    });
            },


            /**
             * Load the workers stats.
             */
            loadWorkers() {
                return this.$http.get(Horizon.basePath + '/api/masters')
                    .then(response => {
                        this.workers = response.data;
                    });
            },


            /**
             * Load the workload stats.
             */
            loadWorkload() {
                return this.$http.get(Horizon.basePath + '/api/workload')
                    .then(response => {
                        this.workload = response.data;
                    });
            },


            /**
             * Refresh the stats every period of time.
             */
            refreshStatsPeriodically() {
                Promise.all([
                    this.loadStats(),
                    this.loadWorkers(),
                    this.loadWorkload(),
                ]).then(() => {
                    this.ready = true;

                    this.timeout = setTimeout(() => {
                        this.refreshStatsPeriodically();
                    }, 5000);
                });
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
             *
             * @returns {string}
             */
            humanTime(time) {
                return moment.duration(time, "seconds").humanize().replace(/^(.)/g, function ($1) {
                    return $1.toUpperCase();
                });
            },


            /**
             * Determine the unit for the given timeframe.
             */
            determinePeriod(minutes) {
                return moment.duration(moment().diff(moment().subtract(minutes, "minutes"))).humanize().replace(/^An?\s/i, '').replace(/^(.)|\s(.)/g, function ($1) {
                    return $1.toUpperCase();
                });
            }
        }
    }
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Overview</h2>
            </div>

            <div class="card-bg-secondary">
                <div class="d-flex">
                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold">Jobs Per Minute</small>

                            <p class="h4 mt-2 mb-0">
                                {{ stats.jobsPerMinute ? stats.jobsPerMinute.toLocaleString() : 0 }}
                            </p>
                        </div>
                    </div>

                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold" v-text="recentJobsPeriod"></small>

                            <p class="h4 mt-2 mb-0">
                                {{ stats.recentJobs ? stats.recentJobs.toLocaleString() : 0 }}
                            </p>
                        </div>
                    </div>

                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold" v-text="failedJobsPeriod"></small>

                            <p class="h4 mt-2 mb-0">
                                {{ stats.failedJobs ? stats.failedJobs.toLocaleString() : 0 }}
                            </p>
                        </div>
                    </div>

                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold">Status</small>

                            <div class="d-flex align-items-center mt-2">
                                <svg v-if="stats.status == 'running'" xmlns="http://www.w3.org/2000/svg" class="text-success" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <svg v-if="stats.status == 'paused'" xmlns="http://www.w3.org/2000/svg" class="text-warning" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <svg v-if="stats.status == 'inactive'" xmlns="http://www.w3.org/2000/svg" class="text-danger" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>

                                <p class="h4 mb-0 ms-2">{{ {running: 'Active', paused: 'Paused', inactive: 'Inactive'}[stats.status] }}</p>
                                <small v-if="stats.status == 'running' && stats.pausedMasters > 0" class="mb-0 ms-2">({{ stats.pausedMasters }} paused)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-muted fw-bold">Total Processes</small>

                            <p class="h4 mt-2">
                                {{ stats.processes ? stats.processes.toLocaleString() : 0 }}
                            </p>
                        </div>
                    </div>

                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-muted fw-bold">Max Wait Time</small>

                            <p class="mt-2 mb-0">
                                {{ stats.max_wait_time ? humanTime(stats.max_wait_time) : '-' }}
                            </p>

                            <small class="mt-1" v-if="stats.max_wait_queue">({{ stats.max_wait_queue }})</small>
                        </div>
                    </div>

                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-muted fw-bold">Max Runtime</small>

                            <p class="h4 mt-2">
                                {{ stats.queueWithMaxRuntime ? stats.queueWithMaxRuntime : '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-muted fw-bold">Max Throughput</small>

                            <p class="h4 mt-2">
                                {{ stats.queueWithMaxThroughput ? stats.queueWithMaxThroughput : '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden mt-4" v-if="workload.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Current Workload</h2>
            </div>

            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Queue</th>
                    <th class="text-end" style="width: 120px;">Jobs</th>
                    <th class="text-end" style="width: 120px;">Processes</th>
                    <th class="text-end" style="width: 180px;">Wait</th>
                </tr>
                </thead>

                <tbody>
                    <template v-for="queue in workload">
                        <tr>
                            <td :class="{ 'fw-bold': queue.split_queues }">
                                <span>{{ queue.name.replace(/,/g, ', ') }}</span>
                            </td>
                            <td class="text-end text-muted" :class="{ 'fw-bold': queue.split_queues }">{{ queue.length ? queue.length.toLocaleString() : 0 }}</td>
                            <td class="text-end text-muted" :class="{ 'fw-bold': queue.split_queues }">{{ queue.processes ? queue.processes.toLocaleString() : 0 }}</td>
                            <td class="text-end text-muted" :class="{ 'fw-bold': queue.split_queues }">{{ humanTime(queue.wait) }}</td>
                        </tr>

                        <tr v-for="split_queue in queue.split_queues">
                            <td>
                                <svg class="icon info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>

                                <span>{{ split_queue.name.replace(/,/g, ', ') }}</span>
                            </td>
                            <td class="text-end text-muted">{{ split_queue.length ? split_queue.length.toLocaleString() : 0 }}</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">{{ humanTime(split_queue.wait) }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>


        <div class="card overflow-hidden mt-4" v-for="worker in workers" :key="worker.name">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">{{ worker.name }}</h2>

                <svg v-if="worker.status == 'running'" xmlns="http://www.w3.org/2000/svg" class="text-success" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <svg v-if="worker.status == 'paused'" xmlns="http://www.w3.org/2000/svg" class="text-warning" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Supervisor</th>
                    <th>Queues</th>
                    <th class="text-end" style="width: 120px;">Processes</th>
                    <th class="text-end" style="width: 180px;">Balancing</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="supervisor in worker.supervisors">
                    <td>
                        <svg v-if="supervisor.status == 'paused'" class="fill-warning me-1" viewBox="0 0 20 20" style="width: 1rem; height: 1rem;">
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM7 6h2v8H7V6zm4 0h2v8h-2V6z" />
                        </svg>
                        <svg v-if="supervisor.status == 'inactive'" class="fill-danger me-1" viewBox="0 0 20 20" style="width: 1rem; height: 1rem;">
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z" />
                        </svg>
                        {{ superVisorDisplayName(supervisor.name, worker.name) }}
                    </td>
                    <td class="text-muted">{{ supervisor.options.queue.replace(/,/g, ', ') }}</td>
                    <td class="text-end text-muted">{{ countProcesses(supervisor.processes) }}</td>
                    <td class="text-end text-muted" v-if="supervisor.options.balance">
                        {{ supervisor.options.balance.charAt(0).toUpperCase() + supervisor.options.balance.slice(1) }}
                    </td>
                    <td class="text-end text-muted" v-else>
                        Disabled
                    </td>
                </tr>
                </tbody>
            </table>
        </div>


    </div>
</template>
