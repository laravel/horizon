<script type="text/ecmascript-6">
    import _ from 'lodash';
    import moment from 'moment';
    import Layout from '../layouts/MainLayout.vue';
    import Panel from '../components/Panels/Panel.vue';
    import Status from '../components/Status/Status.vue';
    import Spinner from '../components/Loaders/Spinner.vue';
    import PanelHeading from '../components/Panels/PanelHeading.vue';
    import PanelContent from '../components/Panels/PanelContent.vue';

    export default {
        components: {Layout, Spinner, Status, Panel, PanelContent, PanelHeading},


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
        <section class="main-content">
            <panel :loading="loadingStats" class="mb3">
                <panel-heading>Overview</panel-heading>

                <panel-content>
                    <div class="stats">
                        <div class="stat">
                            <h2 class="stat-title">Jobs Per Minute</h2>
                            <h3 class="stat-meta">&nbsp;</h3>
                            <p class="stat-value">
                                {{ stats.jobsPerMinute }}
                            </p>
                        </div>
                        <div class="stat">
                            <h2 class="stat-title">Jobs past hour</h2>
                            <h3 class="stat-meta">&nbsp;</h3>
                            <p class="stat-value">
                                {{ stats.recentJobs }}
                            </p>
                        </div>
                        <div class="stat">
                            <h2 class="stat-title">Failed Jobs past hour</h2>
                            <h3 class="stat-meta">&nbsp;</h3>
                            <p class="stat-value">
                                {{ stats.recentlyFailed }}
                            </p>
                        </div>
                        <div class="stat stat-last">
                            <h2 class="stat-title">Status</h2>
                            <h3 class="stat-meta">&nbsp;</h3>

                            <div class="df aic acc">
                                <status :active="stats.status == 'running'" :pending="stats.status == 'paused'" class="mr1.5"/>
                                <span v-if="stats.status == 'running'" class="stat-value">
                                  Active
                                </span>
                                <span v-else-if="stats.status == 'paused'" class="stat-value">
                                  Paused
                                </span>
                                <span v-else class="stat-value">
                                  Inactive
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="stats">
                        <div class="stat">
                            <h2 class="stat-title">Total Processes</h2>
                            <h3 class="state-meta">&nbsp;</h3>
                            <p class="stat-value">
                                {{ stats.processes }}
                            </p>
                        </div>
                        <div class="stat">
                            <h2 class="stat-title">Max Wait Time</h2>
                            <h3 class="stat-meta" v-if="stats.max_wait_time">
                                {{ stats.max_wait_queue }}
                            </h3>
                            <p class="stat-value">
                                {{ stats.max_wait_time ? stats.max_wait_time + 's' : '-' }}
                            </p>
                        </div>
                        <div class="stat">
                            <h2 class="stat-title">Max Runtime</h2>
                            <h3 class="state-meta">&nbsp;</h3>
                            <p class="stat-value">
                                {{ stats.queueWithMaxRuntime ? stats.queueWithMaxRuntime : '-' }}
                            </p>
                        </div>
                        <div class="stat stat-last">
                            <h2 class="stat-title">Max Throughput</h2>
                            <h3 class="state-meta">&nbsp;</h3>
                            <p class="stat-value">
                                {{ stats.queueWithMaxThroughput ? stats.queueWithMaxThroughput : '-' }}
                            </p>
                        </div>
                    </div>
                </panel-content>
            </panel>

            <div v-if="loadingWorkload">
                <panel class="w100% df aic acc jcc pa8">
                    <spinner/>
                </panel>
            </div>
            <div v-if="workload.length">
                <panel>
                    <panel-heading>Current Workload</panel-heading>
                    <panel-content>
                        <table class="table panel-table" cellpadding="0" cellspacing="0">
                            <thead>
                            <tr>
                                <th class="ph2">Queue</th>
                                <th>Processes</th>
                                <th>Jobs</th>
                                <th>Wait</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="queue in workload">
                                <td class="ph2">
                                    <span class="fw7">{{ queue.name }}</span>
                                </td>
                                <td>{{ queue.processes }}</td>
                                <td>{{ queue.length }}</td>
                                <td>{{ humanTime(queue.wait) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </panel-content>
                </panel>
            </div>

            <div v-if="loadingWorkers">
                <panel class="w100% df aic acc jcc pa8">
                    <spinner/>
                </panel>
            </div>
            <div v-else>
                <panel v-for="worker in workers" :key="worker.name">
                    <panel-heading>{{ worker.name }}</panel-heading>
                    <panel-content>
                        <table class="table panel-table" cellpadding="0" cellspacing="0">
                            <thead>
                            <tr>
                                <th class="ph2">Supervisor</th>
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
                                <td>
                                    <status :active="supervisor.options.balance"/>
                                    <span v-if="supervisor.options.balance">
                                        ({{ supervisor.options.balance.charAt(0).toUpperCase() + supervisor.options.balance.slice(1) }})
                                    </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </panel-content>
                </panel>
            </div>
        </section>
    </layout>
</template>
