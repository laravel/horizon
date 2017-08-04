<script type="text/ecmascript-6">
    import Status from '../../components/Status/Status.vue'
    import Spinner from '../../components/Loaders/Spinner.vue'
    import Message from '../../components/Messages/Message.vue'

    export default {
        props: ['type'],

        components: {Status, Message, Spinner},

        /**
         * The component's data.
         */
        data() {
            return {
                page: 1,
                perPage: 50,
                totalPages: 1,
                loadState: {
                    index: true, failed: true
                },
                jobs: {
                    index: [], failed: []
                }
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.loadJobs(this.$route.params.tag);

            this.refreshJobsPeriodically();
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed(){
            clearInterval(this.interval);
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            '$route'() {
                this.page = 1;

                this.loadJobs(this.$route.params.tag);
            }
        },


        methods: {
            /**
             * Load the jobs of the given tag.
             */
            loadJobs(tag, starting = 0, preload = true) {
                if (preload) {
                    this.loadState[this.type] = true;
                }

                tag = this.type == 'failed' ? 'failed:' + tag : tag;

                return this.$http.get('/horizon/api/monitoring/' + encodeURIComponent(tag) + '?starting_at=' + starting + '&limit=' + this.perPage)
                        .then(response => {
                            this.jobs[this.type] = response.data.jobs;

                            this.totalPages = Math.ceil(response.data.total / this.perPage);

                            this.loadState[this.type] = false;

                            return response.data.jobs;
                        });
            },


            /**
             * Refresh the jobs every period of time.
             */
            refreshJobsPeriodically() {
                this.interval = setInterval(() => {
                    if (this.page != 1) {
                        return;
                    }

                    this.loadJobs(this.$route.params.tag, 0, false);
                }, 3000);
            },


            /**
             * Load the jobs for the previous page.
             */
            previous() {
                this.loadJobs(this.$route.params.tag,
                        (this.page - 2) * this.perPage
                );

                this.page -= 1;
            },


            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs(this.$route.params.tag,
                        this.page * this.perPage
                );

                this.page += 1;
            }
        }
    }
</script>
<template>
    <div>
        <div v-if="loadState[type]" style="text-align: center; margin: 50px;">
            <spinner/>
        </div>

        <message v-if="!loadState[type] && !jobs[type].length" text="There aren't any recent jobs for this tag."/>

        <table v-if="! loadState[type] && jobs[type].length" class="table panel-table" cellpadding="0" cellspacing="0">
            <thead>
            <tr>
                <th class="pl2">Job</th>
                <th>On</th>
                <th>Tags</th>
                <th v-if="type == 'index'">Queued At</th>
                <th>Runtime</th>
                <th v-if="type == 'index'">Status</th>
                <th v-if="type != 'index'">Failed At</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="job in jobs[type]">
                <td class="ph2">
                    <a v-if="job.status == 'failed'" :href="'/horizon/failed/'+job.id">{{ job.name }}</a>
                    <span v-else>{{ job.name }}</span>
                </td>
                <td>{{ job.queue }}</td>
                <td>{{ job.payload.tags && job.payload.tags.length ? job.payload.tags.join(', ') : '' }}</td>
                <td v-if="type == 'index'">
                    {{ readableTimestamp(job.payload.pushedAt) }}
                </td>
                <td>
                    <span v-if="job.status == 'failed'">{{ job.failed_at ? String(job.failed_at - job.reserved_at)+'s' : '-' }}</span>
                    <span v-else="">{{ job.completed_at ? String(job.completed_at - job.reserved_at)+'s' : '-' }}</span>
                </td>
                <td v-if="type == 'index'">
                    <status :active="job.status == 'completed'" :pending="job.status == 'reserved' || job.status == 'pending'" class="mr1"/>
                </td>
                <td v-if="type != 'index'">
                    {{ readableTimestamp(job.failed_at) }}
                </td>
            </tr>
            </tbody>
        </table>


        <div v-if="! loadState[type]" class="simple-pagination">
            <button @click="previous" class="btn btn-primary btn-md" :disabled="page==1">Previous</button>
            <button @click="next" class="btn btn-primary btn-md" :disabled="page>=totalPages">Next</button>
        </div>
    </div>
</template>
