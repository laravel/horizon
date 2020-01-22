<script type="text/ecmascript-6">
    import _ from 'lodash';
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
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearTimeout(this.timeout);
        },


        computed: {
            /**
             * Determine the recent job period label.
             */
            recentJobsPeriod() {
                return !this.ready
                    ? 'Jobs past hour'
                    : `Jobs past ${this.determinePeriod(this.stats.periods.recentJobs)}`;
            },


            /**
             * Determine the recently failed job period label.
             */
            failedJobsPeriod() {
                return !this.ready
                    ? 'Failed jobs past 7 days'
                    : `Failed jobs past ${this.determinePeriod(this.stats.periods.failedJobs)}`;
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

                        if (_.values(response.data.wait)[0]) {
                            this.stats.max_wait_time = _.values(response.data.wait)[0];
                            this.stats.max_wait_queue = _.keys(response.data.wait)[0].split(':')[1];
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
                        this.refreshStatsPeriodically(false);
                    }, 5000);
                });
            },


            /**
             *  Count processes for the given supervisor.
             */
            countProcesses(processes) {
                return _.chain(processes).values().sum().value().toLocaleString()
            },


            /**
             *  Format the Supervisor display name.
             */
            superVisorDisplayName(supervisor, worker) {
                return _.replace(supervisor, worker + ':', '');
            },


            /**
             *
             * @returns {string}
             */
            humanTime(time) {
                return moment.duration(time, "seconds").humanize().replace(/^(.)|\s+(.)/g, function ($1) {
                    return $1.toUpperCase();
                });
            },


            /**
             * Determine the unit for the given timeframe.
             */
            determinePeriod(minutes) {
                return moment.duration(moment().diff(moment().subtract(minutes, "minutes"))).humanize().replace(/^An?/i, '');
            }
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Overview</h5>
            </div>

            <div class="card-bg-secondary">
                <div class="d-flex">
                    <div class="w-25 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Jobs Per Minute</small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.jobsPerMinute ? stats.jobsPerMinute.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-25 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase" v-text="recentJobsPeriod"></small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.recentJobs ? stats.recentJobs.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-25 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase" v-text="failedJobsPeriod"></small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.failedJobs ? stats.failedJobs.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-25 border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Status</small>

                            <div class="d-flex align-items-center mt-4">
                                <svg v-if="stats.status == 'running'" class="fill-success" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"></path>
                                </svg>

                                <svg v-if="stats.status == 'paused'" class="fill-warning" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM7 6h2v8H7V6zm4 0h2v8h-2V6z"/>
                                </svg>

                                <svg v-if="stats.status == 'inactive'" class="fill-danger" viewBox="0 0 20 20" style=" width: 1.5rem; height: 1.5rem;">
                                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"/>
                                </svg>

                                <h4 class="mb-0 ml-2">{{ {running: 'Active', paused: 'Paused', inactive:'Inactive'}[stats.status] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="w-25 border-right">
                        <div class="p-4 mb-0">
                            <small class="text-uppercase">TOTAL PROCESSES</small>

                            <h4 class="mt-4">
                                {{ stats.processes ? stats.processes.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-25 border-right">
                        <div class="p-4 mb-0">
                            <small class="text-uppercase">MAX WAIT TIME</small>
                            <small v-if="stats.max_wait_queue">({{ stats.max_wait_queue }})</small>

                            <h4 class="mt-4">
                                {{ stats.max_wait_time ? humanTime(stats.max_wait_time) : '-' }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-25 border-right">
                        <div class="p-4 mb-0">
                            <small class="text-uppercase">MAX RUNTIME</small>

                            <h4 class="mt-4">
                                {{ stats.queueWithMaxRuntime ? stats.queueWithMaxRuntime : '-' }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-uppercase">MAX THROUGHPUT</small>

                            <h4 class="mt-4">
                                {{ stats.queueWithMaxThroughput ? stats.queueWithMaxThroughput : '-' }}
                            </h4>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card mt-4" v-if="workload.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Current Workload</h5>
            </div>

            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Queue</th>
                    <th>Processes</th>
                    <th>Jobs</th>
                    <th class="text-right">Wait</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="queue in workload">
                    <td>
                        <span>{{ queue.name.replace(/,/g, ', ') }}</span>
                    </td>
                    <td>{{ queue.processes ? queue.processes.toLocaleString() : 0 }}</td>
                    <td>{{ queue.length ? queue.length.toLocaleString() : 0 }}</td>
                    <td class="text-right">{{ humanTime(queue.wait) }}</td>
                </tr>
                </tbody>
            </table>
        </div>


        <div class="card mt-4" v-for="worker in workers" :key="worker.name">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>{{ worker.name }}</h5>
            </div>

            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Supervisor</th>
                    <th>Processes</th>
                    <th>Queues</th>
                    <th class="text-right">Balancing</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="supervisor in worker.supervisors">
                    <td>{{ superVisorDisplayName(supervisor.name, worker.name) }}</td>
                    <td>{{ countProcesses(supervisor.processes) }}</td>
                    <td>{{ supervisor.options.queue.replace(/,/g, ', ') }}</td>
                    <td class="text-right">
                        ({{ supervisor.options.balance.charAt(0).toUpperCase() + supervisor.options.balance.slice(1) }})
                    </td>
                </tr>
                </tbody>
            </table>
        </div>


    </div>
</template>
