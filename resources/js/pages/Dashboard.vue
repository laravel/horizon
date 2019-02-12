<script type="text/ecmascript-6">
    import _ from 'lodash';
    import moment from 'moment';
    import Layout from '../layouts/MainLayout.vue';
    import Status from '../components/Status/Status.vue';

    export default {
        components: {Layout, Status},


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
                return ! this.ready
                    ? 'Jobs past hour'
                    : `Jobs past ${this.determinePeriod(this.stats.periods.recentJobs)}`;
            },


            /**
             * Determine the recently failed job period label.
             */
            recentlyFailedPeriod() {
                return ! this.ready
                    ? 'Failed jobs past 7 days'
                    : `Failed jobs past ${this.determinePeriod(this.stats.periods.recentlyFailed)}`;
            },
        },


        methods: {
            /**
             * Load the general stats.
             */
            loadStats() {
                return this.$http.get('/horizon/api/stats')
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
                return this.$http.get('/horizon/api/masters')
                    .then(response => {
                        this.workers = response.data;
                    });
            },


            /**
             * Load the workload stats.
             */
            loadWorkload() {
                this.loadingWorkload = !this.ready;

                return this.$http.get('/horizon/api/workload')
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
    <layout>
        <section class="mainContent">
            <div class="card">
                <div class="card-header">Overview</div>

                <div class="card-body p-0">
                    <div class="container-fluid">
                        <div class="stats row">
                            <div class="stat col-3 p-4">
                                <h2 class="stat-title">Jobs Per Minute</h2>
                                <h3 class="stat-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.jobsPerMinute ? stats.jobsPerMinute.toLocaleString() : 0 }}
                                </span>
                            </div>
                            <div class="stat col-3 p-4">
                                <h2 class="stat-title" v-text="recentJobsPeriod"></h2>
                                <h3 class="stat-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.recentJobs ? stats.recentJobs.toLocaleString() : 0 }}
                                </span>
                            </div>
                            <div class="stat col-3 p-4">
                                <h2 class="stat-title" v-text="recentlyFailedPeriod"></h2>
                                <h3 class="stat-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.recentlyFailed ? stats.recentlyFailed.toLocaleString() : 0 }}
                                </span>
                            </div>
                            <div class="stat col-3 p-4 border-right-0">
                                <h2 class="stat-title">Status</h2>
                                <h3 class="stat-meta">&nbsp;</h3>

                                <div class="d-flex align-items-center">
                                    <status v-if="stats.status" :active="stats.status == 'running'" :pending="stats.status == 'paused'" class="mr-2"/>
                                    <span class="stat-value">
                                        {{ {running: 'Active', paused: 'Paused', inactive:'Inactive'}[stats.status] }}
                                    </span>
                                </div>
                            </div>

                            <div class="stat col-3 p-4 border-bottom-0">
                                <h2 class="stat-title">Total Processes</h2>
                                <h3 class="state-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.processes ? stats.processes.toLocaleString() : 0 }}
                                </span>
                            </div>

                            <div class="stat col-3 p-4 border-bottom-0">
                                <h2 class="stat-title">Max Wait Time</h2>
                                <h3 class="stat-meta">
                                    {{ stats.max_wait_queue || '&nbsp;' }}
                                </h3>
                                <span class="stat-value">
                                    {{ stats.max_wait_time ? humanTime(stats.max_wait_time) : '-' }}
                                </span>
                            </div>

                            <div class="stat col-3 p-4 border-bottom-0">
                                <h2 class="stat-title">Max Runtime</h2>
                                <h3 class="state-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.queueWithMaxRuntime ? stats.queueWithMaxRuntime : '-' }}
                                </span>
                            </div>

                            <div class="stat col-3 p-4 border-0">
                                <h2 class="stat-title">Max Throughput</h2>
                                <h3 class="state-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.queueWithMaxThroughput ? stats.queueWithMaxThroughput : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4" v-if="workload.length">
                <div class="card-header">Current Workload</div>
                <div class="table-responsive">
                    <table class="table card-table table-hover">
                        <thead>
                            <tr>
                                <th>Queue</th>
                                <th>Processes</th>
                                <th>Jobs</th>
                                <th>Wait</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="queue in workload">
                                <td>
                                    <span>{{ queue.name.replace(/,/g, ', ') }}</span>
                                </td>
                                <td>{{ queue.processes ? queue.processes.toLocaleString() : 0 }}</td>
                                <td>{{ queue.length ? queue.length.toLocaleString() : 0 }}</td>
                                <td>{{ humanTime(queue.wait) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-4" v-for="worker in workers" :key="worker.name">
                <div class="card-header">{{ worker.name }}</div>
                <div class="table-responsive">
                    <table class="table card-table table-hover">
                        <thead>
                            <tr>
                                <th>Supervisor</th>
                                <th>Processes</th>
                                <th>Queues</th>
                                <th>Balancing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="supervisor in worker.supervisors">
                                <td class="ph2">
                                    <span class="fw7">{{ superVisorDisplayName(supervisor.name, worker.name) }}</span>
                                </td>
                                <td>{{ countProcesses(supervisor.processes) }}</td>
                                <td>{{ supervisor.options.queue.replace(/,/g, ', ') }}</td>
                                <td class="d-flex align-items-center">
                                    <status :active="supervisor.options.balance" class="mr-2"/>
                                    <span v-if="supervisor.options.balance">
                                        ({{ supervisor.options.balance.charAt(0).toUpperCase() + supervisor.options.balance.slice(1) }})
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </layout>
</template>
