<script>
import _reject from 'lodash/reject'
import _includes from 'lodash/includes'
import _find from 'lodash/find'

import moment from 'moment'
import Layout from '../../layouts/MainLayout.vue'

export default {
    components: {Layout},
    data() {
        return {
            tagSearchPhrase: '',
            searchTimeout: null,
            page: 1,
            perPage: 50,
            totalPages: 1,
            loadingJobs: true,
            retryingJobs: [],
            jobs: []
        }
    },
    created() {
        document.title = 'Horizon - Failed Jobs'

        this.loadJobs()
        this.refreshJobsPeriodically()
    },
    destroyed() {
        clearInterval(this.interval)
    },
    methods: {
        /**
         * Load the failed jobs.
         */
        loadJobs(starting = -1, preload = true) {
            if (preload) {
                this.loadingJobs = true
            }

            let tagQuery = this.tagSearchPhrase ? 'tag=' + this.tagSearchPhrase + '&' : ''

            axios.get('/horizon/api/jobs/failed?' + tagQuery + 'starting_at=' + starting)
                .then(({data}) => {
                    this.jobs = data.jobs

                    this.totalPages = Math.ceil(data.total / this.perPage)

                    this.loadingJobs = false
                })
        },

        /**
         * Retry the given failed job.
         */
        retry(id) {
            if (this.isRetrying(id)) {
                return
            }

            this.retryingJobs.push(id)

            axios.post('/horizon/api/jobs/retry/' + id)
                .then(() => {
                    setTimeout(() => {
                        this.retryingJobs = _reject(this.retryingJobs, (job) => job == id)
                    }, 3000)
                })
        },

        /**
         * Determine if the given job is currently retrying.
         */
        isRetrying(id) {
            return _includes(this.retryingJobs, id)
        },

        /**
         * Determine if the given job has completed.
         */
        hasCompleted(job) {
            return _find(job.retried_by, (retry) => retry.status == 'completed')
        },

        /**
         * Refresh the jobs every period of time.
         */
        refreshJobsPeriodically() {
            this.interval = setInterval(() => {
                if (this.page != 1) {
                    return
                }

                this.loadJobs(-1, false)
            }, 3000)
        },

        /**
         * Load the jobs for the previous page.
         */
        previous() {
            this.loadJobs((this.page - 2) * this.perPage - 1)

            this.page -= 1
        },

        /**
         * Load the jobs for the next page.
         */
        next() {
            this.loadJobs(this.page * this.perPage - 1)

            this.page += 1
        }
    },
    watch: {
        tagSearchPhrase() {
            clearTimeout(this.searchTimeout)

            this.searchTimeout = setTimeout(() => {
                this.loadJobs()
            }, 500)
        }
    }
}
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <span class="mr-auto">Failed Jobs</span>
                    <div class="search">
                        <input v-model="tagSearchPhrase" type="text" class="form-control" placeholder="Search Tags">
                    </div>
                </div>

                <div class="table-responsive">
                    <loader :yes="loadingJobs"/>

                    <p v-if="!loadingJobs && !jobs.length" class="text-center m-0 p-5">
                        There aren't any recent failed jobs.
                    </p>

                    <table v-if="! loadingJobs && jobs.length" class="table card-table table-hover">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>On</th>
                                <th>Tags</th>
                                <th>Runtime</th>
                                <th>Failed At</th>
                                <th>Retry</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="job in jobs" :key="job.name">
                                <td>
                                    <router-link :to="{ name: 'failed.detail', params: { jobId: job.id }}" :title="job.name" vue-tippy>
                                        {{ jobBaseName(job.name) }}
                                    </router-link>
                                </td>
                                <td>{{ job.queue }}</td>
                                <td>{{ displayableTagsList(job.payload.tags) }}</td>
                                <td>{{ job.failed_at ? String((job.failed_at - job.reserved_at).toFixed(3))+'s' : '-' }}</td>
                                <td class="text-nowrap">{{ readableTimestamp(job.failed_at) }}</td>
                                <td>
                                    <span v-if="!hasCompleted(job)" @click="retry(job.id)">
                                        <i class="icon">
                                            <svg :class="{spin: isRetrying(job.id)}" class="fill-primary">
                                                <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-refresh"/>
                                            </svg>
                                        </i>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="! loadingJobs && jobs.length" class="p-3 mt-3 d-flex justify-content-between">
                        <button :disabled="page==1" class="btn btn-primary btn-md" @click="previous">Previous</button>
                        <button :disabled="page>=totalPages" class="btn btn-primary btn-md" @click="next">Next</button>
                    </div>
                </div>
            </div>
        </section>
    </layout>
</template>
