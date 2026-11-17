<script type="text/ecmascript-6">
    import moment from 'moment';

    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                supported: true,
                locks: [],
                releasing: [],
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Unique Locks";

            this.loadLocks();
        },


        methods: {
            /**
             * Load the unique job locks.
             */
            loadLocks() {
                return this.$http.get(Horizon.basePath + '/api/unique-locks')
                    .then(response => {
                        this.supported = response.data.supported;
                        this.locks = response.data.locks;

                        this.ready = true;
                    });
            },


            /**
             * Release the given lock after confirmation.
             */
            release(lock) {
                this.$root.alert.type = 'confirmation';
                this.$root.alert.message = 'Are you sure you want to release this lock? Another job with the same unique ID may be dispatched while the original job is still pending.';
                this.$root.alert.confirmationProceed = () => {
                    this.releasing.push(lock.key);

                    this.$http.delete(Horizon.basePath + '/api/unique-locks', { data: { key: lock.key } })
                        .then(() => this.loadLocks())
                        .finally(() => this.releasing = this.releasing.filter(key => key !== lock.key));
                };
            },


            /**
             * Format the remaining lifetime of a lock.
             */
            expiresIn(seconds) {
                return seconds === null
                    ? 'Never'
                    : moment.duration(seconds, 'seconds').humanize(true);
            },
        },
    }
</script>

<template>
    <div>
        <poll @poll="loadLocks" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Unique Locks</h2>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>

            <div v-if="ready && !supported" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>Unique locks can only be listed when the default cache store uses the Redis or database driver.</span>
            </div>

            <div v-if="ready && supported && locks.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any unique job locks.</span>
            </div>

            <table v-if="ready && supported && locks.length > 0" class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Job</th>
                    <th>Unique ID</th>
                    <th>Pending Job</th>
                    <th>Expires</th>
                    <th class="text-end"></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="lock in locks" :key="lock.key">
                    <td class="text-break" :title="lock.key">{{ lock.name }}</td>
                    <td class="text-break">{{ lock.unique_id || '-' }}</td>
                    <td>
                        <router-link v-if="lock.job_id" :to="{ name: 'job-preview', params: { type: 'pending', jobId: lock.job_id }}">
                            {{ lock.job_id.split('-')[0] }}
                        </router-link>
                        <small v-else class="badge bg-warning badge-sm"
                               title="No pending job holds this lock, so it may be left over from a job that was lost or deleted.">
                            None found
                        </small>
                    </td>
                    <td class="table-fit text-muted">{{ expiresIn(lock.expires_in) }}</td>
                    <td class="text-end table-fit">
                        <button class="btn btn-outline-danger btn-sm" :disabled="releasing.includes(lock.key)" @click="release(lock)">
                            Release
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
