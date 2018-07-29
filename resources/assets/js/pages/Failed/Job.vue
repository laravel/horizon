<script>
import _split from 'lodash/split'
import phpunserialize from 'phpunserialize'
import Layout from '../../layouts/MainLayout.vue'
import Status from '../../components/Status/Status.vue'

export default {
    components: {Layout, Status},
    props: ['jobId'],
    data() {
        return {
            loadingJob: true,
            retryingJob: false,
            job: {}
        }
    },
    mounted() {
        this.loadFailedJob(this.jobId)

        this.interval = setInterval(() => {
            this.reloadRetries()
        }, 3000)
    },
    destroyed() {
        clearInterval(this.interval)
    },
    methods: {
        loadFailedJob(id) {
            this.loadingJob = true

            axios.get('/horizon/api/jobs/failed/' + id)
                .then(({data}) => {
                    this.job = data

                    this.loadingJob = false
                })
        },

        /**
         * Reload the job retries.
         */
        reloadRetries() {
            axios.get('/horizon/api/jobs/failed/' + this.jobId)
                .then(({data}) => {
                    this.job.retried_by = data.retried_by

                })
        },

        /**
         * Retry the given failed job.
         */
        retry(id) {
            if (this.retryingJob) {
                return
            }

            this.retryingJob = true

            axios.post('/horizon/api/jobs/retry/' + id)
                .then(() => {
                    setTimeout(() => {
                        this.reloadRetries()

                        this.retryingJob = false
                    }, 3000)
                })
        },

        /**
         * Convert exception to a more readable format.
         */
        prettyPrintException(exception) {
            let lines = _split(exception, '\n'),
                output = ''

            lines.forEach((line) => {
                output += '<span>' + line + '</span>'
            })

            return output
        },

        /**
         * Pretty print serialized job.
         *
         * @param data
         * @returns {string}
         */
        prettyPrintJob(data) {
            return '<pre>' + JSON.stringify(data.command ? phpunserialize(data.command) : data, null, 2) + '</pre>'
        }
    }
}
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-7">{{ job.name }}</div>
                        <div class="col-md-5 text-right">
                            <button class="btn btn-primary btn-sm" @click="retry(job.id)">
                                <i class="icon-sm">
                                    <svg :class="{spin: retryingJob}" class="fill-white">
                                        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-refresh"/>
                                    </svg>
                                </i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div v-if="job.id">
                        <div class="row mb-2">
                            <div class="col-md-2"><strong>ID</strong></div>
                            <div class="col">{{ job.id }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-2"><strong>Queue</strong></div>
                            <div class="col">{{ job.queue }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-2"><strong>Tags</strong></div>
                            <div class="col">{{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}</div>
                        </div>
                        <div class="row">
                            <div class="col-md-2"><strong>Failed At</strong></div>
                            <div class="col">{{ readableTimestamp(job.failed_at) }}</div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-2"><strong>Error</strong></div>
                            <div class="col">
                                <div class="exceptionDisplay" v-html="prettyPrintException(job.exception)"/>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-2"><strong>Data</strong></div>
                            <div class="col">
                                <p class="jobDetailsText" v-html="prettyPrintJob(job.payload.data)"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!loadingJob && job.retried_by.length" class="card mt-4">
                <div class="card-header">Recent Retries</div>

                <div class="table-responsive">
                    <table class="table card-table table-hover">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>ID</th>
                                <th>Retry Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(retry, i) in job.retried_by" :key="i">
                                <td class="d-flex">
                                    <status :active="retry.status == 'completed'" :pending="retry.status == 'pending'" class="mr-2"/>
                                    {{ retry.status.charAt(0).toUpperCase() + retry.status.slice(1) }}
                                </td>
                                <td>
                                    <a v-if="retry.status == 'failed'" :href="'/horizon/failed/'+retry.id">
                                        {{ retry.id }}
                                    </a>
                                    <span v-else>{{ retry.id }}</span>
                                </td>
                                <td>{{ readableTimestamp(retry.retried_at) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <message v-if="!job.retried_by" text="There aren't any recent retries for this job"/>
                </div>
            </div>
        </section>
    </layout>
</template>
