<script setup>
import { Head } from '@inertiajs/vue3';
</script>
<script type="text/ecmascript-6">
    export default {
        methods: {
            /**
             * Load the batches.
             */
            loadBatches(beforeId = '', refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                this.$http.get(Horizon.url(`/api/batches?before_id=${beforeId}`))
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

                this.loadBatches(0, false);

                this.hasNewEntries = false;
            },


            /**
             * Poll handler to refresh the batches at regular intervals.
             */
            refreshBatchesPeriodically() {
                if (this.page != 1) return;

                this.loadBatches('', true);
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
        <Head title="Horizon - Batches" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Batches</h2>
            </div>

            <div v-if="batches.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any batches.</span>
            </div>

            <table v-if="batches.length > 0" class="table table-hover mb-0">
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
                <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-2">
                        <small><a v-if="!loadingNewEntries" href="#" @click.prevent="loadNewEntries">Load New Entries</a></small>

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
                        <small v-if="!batch.cancelledAt && batch.failedJobs > 0 && batch.totalJobs - batch.pendingJobs < batch.totalJobs" class="badge badge-danger badge-sm">
                            Failures
                        </small>
                        <small v-if="!batch.cancelledAt && batch.totalJobs - batch.pendingJobs == batch.totalJobs" class="badge badge-success badge-sm">
                            Finished
                        </small>
                        <small v-if="!batch.cancelledAt && batch.pendingJobs > 0 && !batch.failedJobs" class="badge badge-secondary badge-sm">
                            Pending
                        </small>
                        <small v-if="batch.cancelledAt" class="badge badge-warning badge-sm">
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

            <div v-if="batches.length" class="p-3 d-flex justify-content-between border-top">
                <button class="btn btn-secondary btn-sm" :disabled="page==1" @click="previous">Previous</button>
                <button class="btn btn-secondary btn-sm" :disabled="batches.length < 50" @click="next">Next</button>
            </div>
        </div>

    </div>
</template>
