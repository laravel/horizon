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
                page: parseInt(this.$route.query.page) || 1,
                previousFirstId: this.$route.query.previous_first_id || null,
                batches: [],
                searchQuery: this.$route.query.query || '',
                searchTimeout: null,
            };
        },

        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Batches";

            this.loadBatches(this.$route.query.before_id || '');
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            searchQuery(newVal, oldVal) {
                if (!oldVal) return;

                clearTimeout(this.searchTimeout);

                this.searchTimeout = setTimeout(() => {
                    this.page = 1;
                    this.previousFirstId = null;

                    this.loadBatches();
                    this.updateQueryParams();
                }, 500);
            },

            '$root.autoLoadsNewEntries'(autoLoadsNewEntries) {
                if (autoLoadsNewEntries && this.hasNewEntries) {
                    this.hasNewEntries = false;
                }
            }
        },


        methods: {
            /**
             * Load the batches.
             */
            loadBatches(beforeId = '', refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                var searchQuery = this.searchQuery ? 'query=' + encodeURIComponent(this.searchQuery) + '&' : '';

                this.$http.get(Horizon.basePath + '/api/batches?' + searchQuery + 'before_id=' + beforeId)
                    .then(response => {
                        if (!this.$root.autoLoadsNewEntries && refreshing && !response.data.batches.length) {
                            this.ready = true;
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

                this.page = 1;
                this.previousFirstId = null;

                this.loadBatches('', false);

                this.hasNewEntries = false;

                this.updateQueryParams();
            },


            /**
             * Poll handler to refresh the batches at regular intervals.
             */
            refreshBatchesPeriodically() {
                if (this.page != 1) return;

                if (this.searchQuery) return;

                this.loadBatches('', true);
            },


            /**
             * Load the batches for the previous page.
             */
            previous() {
                var beforeId = this.page == 2 ? '' : this.previousFirstId;

                this.loadBatches(beforeId);

                this.page -= 1;

                this.hasNewEntries = false;

                this.updateQueryParams(beforeId);
            },


            /**
             * Load the batches for the next page.
             */
            next() {
                this.previousFirstId = this.batches[0]?.id + '0';

                var beforeId = this.batches.slice(-1)[0]?.id;

                this.loadBatches(beforeId);

                this.page += 1;

                this.hasNewEntries = false;

                this.updateQueryParams(beforeId);
            },


            /**
             * Clear the search query and reset the table state.
             */
            clearSearch() {
                this.searchQuery = '';
            },


            /**
             * Sync pagination and search state to URL query params.
             */
            updateQueryParams(beforeId) {
                var query = {};

                if (this.searchQuery) query.query = this.searchQuery;
                if (this.page > 1) query.page = this.page;
                if (beforeId) query.before_id = beforeId;
                if (this.previousFirstId && this.page > 1) query.previous_first_id = this.previousFirstId;

                this.$router.replace({ query }).catch(() => {});
            },
        }
    }
</script>

<template>
    <div>
        <poll @poll="refreshBatchesPeriodically" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Batches</h2>

                <div class="form-control-with-icon">
                    <div class="icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <input type="text" class="form-control w-100" :style="searchQuery ? 'padding-right: 2rem' : ''" v-model="searchQuery" placeholder="Search Batches">

                    <a v-if="searchQuery" href="#" @click.prevent="clearSearch" class="clear-search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
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
                    <th class="text-end">Size</th>
                    <th class="text-end">Completion</th>
                    <th class="text-end">Created</th>
                </tr>
                </thead>

                <tbody>
                <tr v-if="hasNewEntries && !this.$root.autoLoadsNewEntries" key="newEntries" class="dontanimate">
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
                    <td class="text-end text-muted">{{ batch.totalJobs }}</td>
                    <td class="text-end text-muted">{{ batch.progress }}%</td>

                    <td class="text-end table-fit">
                        {{ formatDateIso(batch.createdAt).format("YYYY-MM-DD HH:mm:ss") }}
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
