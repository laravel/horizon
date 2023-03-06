<script type="text/ecmascript-6">
    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                loadingNewEntries: false,
                hasNewEntries: false,
                page: 1,
                previousFirstId: null,
                batches: [],
            };
        },

        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Batches";

            this.loadBatches();

            this.refreshBatchesPeriodically();
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearInterval(this.interval);
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            '$route'() {
                this.page = 1;

                this.loadBatches();
            },
        },


        methods: {
            /**
             * Load the batches.
             */
            loadBatches(beforeId = '', refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                this.$http.get(Horizon.basePath + '/api/batches?before_id=' + beforeId)
                    .then(response => {
                        if (!this.$root.autoLoadsNewEntries && refreshing && !response.data.batches.length) {
                            return;
                        }

                        if (!this.$root.autoLoadsNewEntries && refreshing && this.batches.length && response.data.batches[0]?.id !== this.batches[0]?.id) {
                            this.hasNewEntries = true;
                        } else {
                            this.batches = response.data.batches;
                        }

                        this.ready = true;
                    });
            },


            loadNewEntries() {
                this.batches = [];

                this.loadBatches(0, false);

                this.hasNewEntries = false;
            },


            /**
             * Refresh the batches every period of time.
             */
            refreshBatchesPeriodically() {
                this.interval = setInterval(() => {
                    if (this.page != 1) return;

                    this.loadBatches('', true);
                }, 3000);
            },


            /**
             * Load the batches for the previous page.
             */
            previous() {
                this.loadBatches(
                    this.page == 2 ? '' : this.previousFirstId
                );

                this.page -= 1;

                this.hasNewEntries = false;
            },


            /**
             * Load the batches for the next page.
             */
            next() {
                this.previousFirstId = this.batches[0]?.id + '0';

                this.loadBatches(
                    this.batches.slice(-1)[0]?.id
                );

                this.page += 1;

                this.hasNewEntries = false;
            }
        }
    }
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Batches</h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && batches.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any batches.</span>
            </div>

            <table v-if="ready && batches.length > 0" class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Batch</th>
                    <th>Status</th>
                    <th class="text-right">Size</th>
                    <th class="text-right">Completion</th>
                    <th class="text-right">Created</th>
                </tr>
                </thead>

                <tbody>
                <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-2">
                        <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                        <small v-if="loadingNewEntries">Loading...</small>
                    </td>
                </tr>

                <tr v-for="batch in batches" :key="batch.id">
                    <td>
                        <router-link :title="batch.id" :to="{ name: 'batches-preview', params: { batchId: batch.id }}">
                            {{ batch.name || batch.id }}
                        </router-link>
                    </td>
                    <td>
                        <small class="badge badge-danger badge-sm" v-if="!batch.cancelledAt && batch.failedJobs > 0 && batch.totalJobs - batch.pendingJobs < batch.totalJobs">
                            Failures
                        </small>
                        <small class="badge badge-success badge-sm" v-if="!batch.cancelledAt && batch.totalJobs - batch.pendingJobs == batch.totalJobs">
                            Finished
                        </small>
                        <small class="badge badge-secondary badge-sm" v-if="!batch.cancelledAt && batch.pendingJobs > 0 && !batch.failedJobs">
                            Pending
                        </small>
                        <small class="badge badge-warning badge-sm" v-if="batch.cancelledAt">
                            Cancelled
                        </small>
                    </td>
                    <td class="text-right text-muted">{{batch.totalJobs}}</td>
                    <td class="text-right text-muted">{{batch.progress}}%</td>

                    <td class="text-right text-muted table-fit">
                        {{ formatDateIso(batch.createdAt).format('YYYY-MM-DD HH:mm:ss') }}
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="ready && batches.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-sm" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-sm" :disabled="batches.length < 50">Next</button>
            </div>
        </div>

    </div>
</template>
