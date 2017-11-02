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
                loadingStats: true,
                loadingWorkers: true,
                loadingWorkload: true,
                stats: {},
                workers: [],
                workload: [],
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Dashboard";

            this.loadStats();

            this.loadWorkers();

            this.loadWorkload();

            this.refreshStatsPeriodically();
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed(){
            clearInterval(this.interval);
        },


        methods: {
            /**
             * Load the general stats.
             */
            loadStats(reload = true) {
                if (reload) {
                    this.loadingStats = true;
                }

                this.$http.get('/horizon/api/stats')
                    .then(response => {
                        this.stats = response.data;

                        if (_.values(response.data.wait)[0]) {
                            this.stats.max_wait_time = _.values(response.data.wait)[0];
                            this.stats.max_wait_queue = _.keys(response.data.wait)[0].split(':')[1];
                        }

                        this.loadingStats = false;
                    });
            },


            /**
             * Load the workers stats.
             */
            loadWorkers(reload = true) {
                if (reload) {
                    this.loadingWorkers = true;
                }

                this.$http.get('/horizon/api/masters')
                    .then(response => {
                        this.workers = response.data;

                        this.loadingWorkers = false;
                    });
            },


            /**
             * Load the workload stats.
             */
            loadWorkload(reload = true) {
                if (reload) {
                    this.loadingWorkload = true;
                }

                this.$http.get('/horizon/api/workload')
                    .then(response => {
                        this.workload = response.data;

                        this.loadingWorkload = false;
                    });
            },


            /**
             * Refresh the stats every period of time.
             */
            refreshStatsPeriodically() {
                this.interval = setInterval(() => {
                    this.loadStats(false);

                    this.loadWorkers(false);

                    this.loadWorkload(false);
                }, 5000);
            },


            /**
             *  Count processes for the given supervisor.
             */
            countProcesses(processes){
                return _.chain(processes).values().sum().value()
            },


            /**
             *  Format the Supervisor display name.
             */
            superVisorDisplayName(supervisor, worker){
                return _.replace(supervisor, worker + ':', '');
            },


            /**
             *
             * @returns {string}
             */
            humanTime(time){
                return moment.duration(time, "seconds").humanize().replace(/^(.)|\s+(.)/g, function ($1) {
                    return $1.toUpperCase();
                });
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
                                    {{ stats.jobsPerMinute }}
                                </span>
                            </div>
                            <div class="stat col-3 p-4">
                                <h2 class="stat-title">Jobs past hour</h2>
                                <h3 class="stat-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.recentJobs }}
                                </span>
                            </div>
                            <div class="stat col-3 p-4">
                                <h2 class="stat-title">Failed Jobs past hour</h2>
                                <h3 class="stat-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.recentlyFailed }}
                                </span>
                            </div>
                            <div class="stat col-3 p-4 border-right-0">
                                <h2 class="stat-title">Status</h2>
                                <h3 class="stat-meta">&nbsp;</h3>

                                <div class="d-flex align-items-center">
                                    <status :active="stats.status == 'running'" :pending="stats.status == 'paused'" class="mr-2"/>
                                    <span class="stat-value">
                                      {{ {running: 'Active', paused: 'Paused', inactive:'Inactive'}[stats.status] }}
                                    </span>
                                </div>
                            </div>

                            <div class="stat col-3 p-4 border-bottom-0">
                                <h2 class="stat-title">Total Processes</h2>
                                <h3 class="state-meta">&nbsp;</h3>
                                <span class="stat-value">
                                    {{ stats.processes }}
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
                                <span>{{ queue.name }}</span>
                            </td>
                            <td>{{ queue.processes }}</td>
                            <td>{{ queue.length }}</td>
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
                            <td>{{ supervisor.options.queue }}</td>
                            <td class="d-flex align-items-center">
                                <status :active="supervisor.options.balance"  class="mr-2"/>
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
