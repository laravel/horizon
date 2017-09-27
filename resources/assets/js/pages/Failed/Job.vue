<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'
    import Layout from '../../layouts/MainLayout.vue'
    import Icon from '../../components/Icons/Icon.vue'
    import Panel from '../../components/Panels/Panel.vue'
    import Status from '../../components/Status/Status.vue'
    import Message from '../../components/Messages/Message.vue'
    import PanelHeading from '../../components/Panels/PanelHeading.vue'
    import PanelContent from '../../components/Panels/PanelContent.vue'

    export default {
        props: ['jobId'],


        components: {Icon, Layout, Message, Panel, PanelContent, PanelHeading, Status},


        /**
         * The component's data.
         */
        data() {
            return {
                loadingJob: true,
                retryingJob: false,
                job: {}
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadFailedJob(this.jobId)

            this.interval = setInterval(() => {
                this.reloadRetries();
            }, 3000);
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed(){
            clearInterval(this.interval);
        },


        methods: {
            loadFailedJob(id) {
                this.loadingJob = true;

                this.$http.get('/horizon/api/jobs/failed/' + id)
                        .then(response => {
                            this.job = response.data;

                            this.loadingJob = false;
                        });
            },


            /**
             * Reload the job retries.
             */
            reloadRetries() {
                this.$http.get('/horizon/api/jobs/failed/' + this.jobId)
                        .then(response => {
                            this.job.retried_by = response.data.retried_by;

                        });
            },


            /**
             * Retry the given failed job.
             */
            retry(id) {
                if (this.retryingJob) {
                    return;
                }

                this.retryingJob = true;

                this.$http.post('/horizon/api/jobs/retry/' + id)
                        .then(() => {
                            setTimeout(() => {
                                this.reloadRetries();

                                this.retryingJob = false;
                            }, 3000);
                        });
            },


            /**
             * Convert exception to a more readable format.
             */
            prettyPrintException(exception){
                var lines = _.split(exception, "\n"),
                        output = '';

                lines.forEach(line => {
                    output += '<span>' + line + '</span>';
                });

                return output;
            },


            /**
             * Pretty print serialized job.
             *
             * @param data
             * @returns {string}
             */
            prettyPrintJob(data){
                return '<pre>' + JSON.stringify(phpunserialize(data), null, 2) + '</pre>';
            }
        }
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <panel :loading="loadingJob">
                <panel-heading>
                    {{job.name}}
                    <button @click="retry(job.id)" class="btn btn-primary btn-md">
                        <icon icon="refresh" size="sm" color="fw" class="ico-baseline mr0.5"
                              :spin="retryingJob"/>
                        Retry
                    </button>
                </panel-heading>

                <panel-content>
                    <div class="pa2" v-if="job.id">
                        <div class="frame mb2 pb2 brdr1--bottom bcg1">
                            <div class="blk2 ft15 lh2 basic-text tar">
                                ID<br>
                                On<br>
                                Tags<br>
                                Failed Time
                            </div>
                            <div class="blk9 ft15 lh2 basic-text">
                                {{job.id}}<br>
                                {{job.queue}}<br>
                                {{job.payload.tags.length ? job.payload.tags.join(', ') : ''}}<br>
                                {{this.formatDate(job.failed_at).format('YYYY-MM-DD HH:mm:ss')}}
                            </div>
                        </div>
                        <div class="frame mb2 pb2 brdr1--bottom bcg1">
                            <div class="blk2 ft15 lh2 basic-text tar">
                                Error
                            </div>
                            <div class="blk9">
                                <p class="basic-text ft14 lh1.5 jobDetailsText"
                                   v-html="prettyPrintException(job.exception)"></p>
                            </div>
                        </div>
                        <div class="frame">
                            <div class="blk2 ft15 lh2 basic-text tar">
                                Data
                            </div>
                            <div class="blk9">
                                <p class="basic-text ft14 lh1.5 jobDetailsText"
                                   v-html="prettyPrintJob(job.payload.data.command)"></p>
                            </div>
                        </div>
                    </div>
                </panel-content>
            </panel>

            <panel v-if="!loadingJob && job.retried_by.length">
                <panel-heading>Recent Retries</panel-heading>

                <panel-content>
                    <table class="table panel-table" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th class="pl2">Job</th>
                            <th>ID</th>
                            <th>Retry Time</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="retry in job.retried_by">
                            <td class="ph2">
                                <div class="df aic acc">
                                    <status :active="retry.status == 'completed'" :pending="retry.status == 'pending'" class="mr1"/>
                                    {{ retry.status.charAt(0).toUpperCase() + retry.status.slice(1) }}
                                </div>
                            </td>
                            <td>
                                <a v-if="retry.status == 'failed'" :href="'/horizon/failed/'+retry.id">
                                    {{ retry.id }}
                                </a>
                                <span v-else>{{ retry.id }}</span>
                            </td>
                            <td>{{formatDate(retry.retried_at).format('YYYY-MM-DD HH:mm:ss')}}</td>
                        </tr>
                        </tbody>
                    </table>

                    <message v-if="!job.retried_by" text="There aren't any recent retries for this job"/>
                </panel-content>
            </panel>
        </section>
    </layout>
</template>
