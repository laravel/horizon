<script type="text/ecmascript-6">
    export default {
        components: {},

        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                jobs: [],
                sortBy: 'name',
                sortDir: 'asc'
            };
        },

        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Performance";
            this.loadJobs();
        },

        computed: {
            /**
             * Get the sorted jobs list.
             */
            sortedJobs() {
                return [...this.jobs].sort((a, b) => {
                    let comparison = 0;
                    
                    if (this.sortBy === 'name') {
                        comparison = a.name.localeCompare(b.name);
                    } else if (this.sortBy === 'memory') {
                        comparison = a.memory - b.memory;
                    } else if (this.sortBy === 'runtime') {
                        comparison = a.runtime - b.runtime;
                    } else if (this.sortBy === 'throughput') {
                        comparison = a.throughput - b.throughput;
                    }
                    
                    return this.sortDir === 'asc' ? comparison : -comparison;
                });
            }
        },

        methods: {
            /**
             * Load all jobs with their metrics.
             */
            loadJobs() {
                this.ready = false;

                // First get all measured jobs
                this.$http.get(Horizon.basePath + '/api/metrics/jobs')
                    .then(response => {
                        const jobNames = response.data;
                        const jobPromises = [];
                        
                        // For each job, get the latest metrics
                        jobNames.forEach(jobName => {
                            jobPromises.push(
                                this.$http.get(Horizon.basePath + '/api/metrics/jobs/' + encodeURIComponent(jobName))
                                    .then(response => {
                                        const metrics = response.data;
                                        // Get the most recent metrics
                                        const latestMetrics = metrics.length > 0 ? metrics[metrics.length - 1] : null;
                                        
                                        return {
                                            name: jobName,
                                            memory: latestMetrics ? latestMetrics.memory : 0,
                                            runtime: latestMetrics ? latestMetrics.runtime : 0,
                                            throughput: latestMetrics ? latestMetrics.throughput : 0
                                        };
                                    })
                            );
                        });
                        
                        // When all job metrics are loaded
                        Promise.all(jobPromises).then(jobsWithMetrics => {
                            this.jobs = jobsWithMetrics;
                            this.ready = true;
                        });
                    });
            },
            
            /**
             * Sort the jobs by the given column.
             */
            sortJobsBy(column) {
                if (this.sortBy === column) {
                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortBy = column;
                    this.sortDir = 'asc';
                }
            },
            
            /**
             * Get the sort icon for the given column.
             */
            sortIcon(column) {
                if (this.sortBy !== column) {
                    return 'fa-sort';
                }
                
                return this.sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
            }
        }
    }
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Job Performance Metrics</h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div v-if="ready && jobs.length === 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any jobs with performance metrics yet.</span>
            </div>

            <table v-if="ready && jobs.length > 0" class="table table-hover mb-0">
                <thead>
                <tr>
                    <th @click="sortJobsBy('name')">
                        Job Name
                        <i :class="['fas', sortIcon('name')]"></i>
                    </th>
                    <th @click="sortJobsBy('memory')" class="text-end">
                        Memory Usage
                        <i :class="['fas', sortIcon('memory')]"></i>
                    </th>
                    <th @click="sortJobsBy('runtime')" class="text-end">
                        Runtime
                        <i :class="['fas', sortIcon('runtime')]"></i>
                    </th>
                    <th @click="sortJobsBy('throughput')" class="text-end">
                        Throughput
                        <i :class="['fas', sortIcon('throughput')]"></i>
                    </th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="job in sortedJobs" :key="job.name">
                    <td>
                        <router-link class="text-decoration-none" :to="{ name: 'metrics-preview', params: { type: 'jobs', slug: job.name }}">
                            {{ job.name }}
                        </router-link>
                    </td>
                    <td class="text-end text-muted">
                        {{ job.memory > 0 ? job.memory.toFixed(2) + ' MB' : '-' }}
                    </td>
                    <td class="text-end text-muted">
                        {{ job.runtime > 0 ? job.runtime.toFixed(3) + ' s' : '-' }}
                    </td>
                    <td class="text-end text-muted">
                        {{ job.throughput }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
    th {
        cursor: pointer;
    }
    
    .fa-sort, .fa-sort-up, .fa-sort-down {
        margin-left: 5px;
    }
</style> 