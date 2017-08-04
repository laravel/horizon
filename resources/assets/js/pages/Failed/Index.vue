<script type="text/ecmascript-6">
    import moment from 'moment';
    import Layout from '../../layouts/MainLayout.vue'
    import Icon from '../../components/Icons/Icon.vue'
    import Panel from '../../components/Panels/Panel.vue'
    import Spinner from '../../components/Loaders/Spinner.vue'
    import Message from '../../components/Messages/Message.vue'
    import PanelContent from '../../components/Panels/PanelContent.vue'
    import PanelHeading from '../../components/Panels/PanelHeading.vue'

    export default {
        components: {Layout, Panel, PanelContent, PanelHeading, Message, Icon, Spinner},


        /**
         * The component's data.
         */
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
            };
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            tagSearchPhrase() {
                clearTimeout(this.searchTimeout);

                this.searchTimeout = setTimeout(() => {
                    this.loadJobs();
                }, 500);
            }
        },


        /**
         * Prepare the component.
         */
        created() {
            document.title = "Horizon - Failed Jobs";

            this.loadJobs();

            this.refreshJobsPeriodically();
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed(){
            clearInterval(this.interval);
        },


        methods: {
            /**
             * Load the failed jobs.
             */
            loadJobs(starting = -1, preload = true) {
                if (preload) {
                    this.loadingJobs = true;
                }

                var tagQuery = this.tagSearchPhrase ? 'tag=' + this.tagSearchPhrase + '&' : '';

                this.$http.get('/horizon/api/jobs/failed?' + tagQuery + 'starting_at=' + starting)
                        .then(response => {
                            this.jobs = response.data.jobs;

                            this.totalPages = Math.ceil(response.data.total / this.perPage);

                            this.loadingJobs = false;
                        });
            },


            /**
             * Retry the given failed job.
             */
            retry(id) {
                if (this.isRetrying(id)) {
                    return;
                }

                this.retryingJobs.push(id);

                this.$http.post('/horizon/api/jobs/retry/' + id)
                        .then(() => {
                            setTimeout(() => {
                                this.retryingJobs = _.reject(this.retryingJobs, job => job == id);
                            }, 3000);
                        });
            },


            /**
             * Determine if the given job is currently retrying.
             */
            isRetrying(id) {
                return _.includes(this.retryingJobs, id);
            },


            /**
             * Determine if the given job has completed.
             */
            hasCompleted(job){
                return _.find(job.retried_by, retry => retry.status == 'completed');
            },


            /**
             * Refresh the jobs every period of time.
             */
            refreshJobsPeriodically() {
                this.interval = setInterval(() => {
                    if (this.page != 1) {
                        return;
                    }

                    this.loadJobs(-1, false);
                }, 3000);
            },


            /**
             * Load the jobs for the previous page.
             */
            previous() {
                this.loadJobs(((this.page - 2) * this.perPage) - 1);

                this.page -= 1;
            },


            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs((this.page * this.perPage) - 1);

                this.page += 1;
            }
        }
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <panel>
                <panel-heading>
                    <div class="vab">
                        <span class="mr2">Failed Jobs</span>
                    </div>
                    <div class="search">
                        <input type="text" class="search-input" v-model="tagSearchPhrase" placeholder="Search Tags">
                    </div>
                </panel-heading>

                <panel-content>
                    <div v-if="loadingJobs" style="text-align: center; margin: 50px;">
                        <spinner/>
                    </div>

                    <message v-if="!loadingJobs && !jobs.length" text="There aren't any recent failed jobs."/>

                    <table v-if="! loadingJobs && jobs.length" class="table" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th class="pl2">Job</th>
                            <th>On</th>
                            <th>Tags</th>
                            <th>Runtime</th>
                            <th>Failed At</th>
                            <th>Retry</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="job in jobs">
                            <td class="ph2">
                                <router-link :to="{ name: 'failed.detail', params: { jobId: job.id }}" class="fw7">
                                    {{ job.name }}
                                </router-link>
                            </td>
                            <td>{{ job.queue }}</td>
                            <td>{{ job.payload.tags.length ? job.payload.tags.join(', ') : '' }}</td>
                            <td>{{ job.failed_at ? String(job.failed_at - job.reserved_at)+'s' : '-' }}</td>
                            <td>{{ readableTimestamp(job.failed_at) }}</td>
                            <td>
                                <span @click="retry(job.id)" v-if="!hasCompleted(job)">
                                    <icon :class="{'pointer': !isRetrying(job.id)}"
                                          :spin="isRetrying(job.id)"
                                          color="f1"
                                          icon="refresh"/>
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div v-if="! loadingJobs && jobs.length" class="simple-pagination">
                        <button @click="previous" class="btn btn-primary btn-md" :disabled="page==1">Previous</button>
                        <button @click="next" class="btn btn-primary btn-md" :disabled="page>=totalPages">Next</button>
                    </div>
                </panel-content>
            </panel>
        </section>
    </layout>
</template>
