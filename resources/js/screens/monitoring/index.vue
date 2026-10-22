<script type="text/ecmascript-6">
    import { Modal } from 'bootstrap';

    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                newTag: '',
                reason: '',
                editing: false,
                saving: false,
                error: '',
                actionError: '',
                stoppingTag: null,
                addTagModal: null,
                tags: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Monitoring";
        },


        beforeUnmount() {
            if (this.addTagModal) {
                this.addTagModal.hide();
                this.addTagModal.dispose();
            }
        },


        methods: {
            /**
             * Load the monitored tags.
             */
            loadTags() {
                return this.$http.get(Horizon.basePath + '/api/monitoring')
                    .then(response => {
                        this.tags = response.data;

                        this.ready = true;
                    });
            },


            /**
             * Poll handler to refresh the tags at regular intervals.
             */
            refreshTagsPeriodically() {
                this.loadTags();
            },


            /**
             * Open the monitor form.
             */
            openNewTagModal(tag = null) {
                this.editing = tag !== null;
                this.newTag = tag ? tag.tag : '';
                this.reason = tag ? tag.reason || '' : '';
                this.error = '';
                const modal = document.getElementById('addTagModel');
                this.addTagModal = Modal.getOrCreateInstance(modal, {backdrop: 'static', keyboard: false});
                modal.addEventListener('shown.bs.modal', () => {
                    document.getElementById(this.editing ? 'monitorReason' : 'newTagInput').focus();
                }, {once: true});
                this.addTagModal.show();
            },


            /**
             * Save the monitored tag and its reason.
             */
            monitorNewTag() {
                if (this.saving) return;

                if (!this.newTag.trim() || !this.reason.trim()) {
                    this.error = 'A tag and reason are required.';
                    return;
                }

                this.saving = true;
                this.error = '';
                const request = this.editing
                    ? this.$http.patch(Horizon.basePath + '/api/monitoring/' + encodeURIComponent(this.newTag), {reason: this.reason})
                    : this.$http.post(Horizon.basePath + '/api/monitoring', {tag: this.newTag, reason: this.reason});

                request.then(() => {
                    this.addTagModal.hide();
                    return this.loadTags();
                }).catch(error => {
                    if (error.response && error.response.status === 422) {
                        this.error = Object.values(error.response.data.errors).flat().join(' ');
                    } else {
                        this.error = 'Unable to save the monitor. Refresh and try again.';
                    }
                }).finally(() => {
                    this.saving = false;
                });
            },


            /**
             * Cancel editing the monitor.
             */
            cancelNewTag() {
                if (!this.saving && this.addTagModal) this.addTagModal.hide();
            },


            /**
             * Stop monitoring the given tag.
             */
            stopMonitoring(tag) {
                if (this.stoppingTag !== null) return;

                this.stoppingTag = tag;
                this.actionError = '';
                this.$http.delete(Horizon.basePath + '/api/monitoring/' + encodeURIComponent(tag))
                    .then(() => {
                        this.tags = this.tags.filter(existing => existing.tag !== tag);
                    }).catch(() => {
                        this.actionError = 'Unable to stop monitoring. Refresh and try again.';
                    }).finally(() => {
                        this.stoppingTag = null;
                    });
            }
        }
    }
</script>

<template>
    <div>
        <poll @poll="refreshTagsPeriodically" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Monitoring</h2>

                <button v-if="Horizon.canManageMonitoring" @click="openNewTagModal()" class="btn btn-primary btn-sm">Monitor Tag</button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin me-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && tags.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>You're not monitoring any tags.</span>
            </div>


            <div v-if="!Horizon.canManageMonitoring" class="px-3 py-2 text-muted">Sign in to add, edit, or stop monitors.</div>
            <div v-if="actionError" class="alert alert-danger m-3" role="alert">{{ actionError }}</div>

            <div class="table-responsive">
                <table v-if="ready && tags.length > 0" class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Reason</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th class="text-end">Jobs</th>
                        <th class="text-end"></th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr v-for="tag in tags" :key="tag.tag">
                        <td>
                            <router-link :to="{ name: 'monitoring-jobs', params: { tag:tag.tag }}" href="#">
                                {{ tag.tag }}
                            </router-link>
                        </td>
                        <td>
                            <div class="d-flex align-items-start">
                                <button v-if="Horizon.canManageMonitoring" type="button" @click="openNewTagModal(tag)" class="btn btn-sm btn-link control-action p-0 me-2 flex-shrink-0" title="Edit Reason" aria-label="Edit Reason">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M17.414 2.586a2 2 0 010 2.828l-1 1-2.828-2.828 1-1a2 2 0 012.828 0zM12.172 5L3 14.172V17h2.828L15 7.828 12.172 5z" />
                                    </svg>
                                </button>

                                <span class="text-break" style="white-space: pre-wrap">{{ tag.reason || 'Not provided' }}</span>
                            </div>
                        </td>
                        <td>{{ tag.created_by ? tag.created_by.name : 'Unknown' }}</td>
                        <td class="text-nowrap" :title="tag.created_at ? formatDateIso(tag.created_at).format('YYYY-MM-DD HH:mm:ss') : null">{{ tag.created_at_human || 'Unknown' }}</td>
                        <td class="text-end text-muted">{{ tag.count }}</td>
                        <td class="text-end">
                            <button v-if="Horizon.canManageMonitoring" @click="stopMonitoring(tag.tag)" :disabled="stoppingTag !== null" class="btn btn-sm btn-link control-action" title="Stop Monitoring" aria-label="Stop Monitoring">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal" id="addTagModel" tabindex="-1" role="dialog" aria-labelledby="monitorModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header" id="monitorModalLabel">{{ editing ? 'Edit Monitor Reason' : 'Monitor New Tag' }}</div>

                    <div class="modal-body">
                        <label for="newTagInput" class="form-label">Tag</label>
                        <input :disabled="editing || saving" type="text" class="form-control" placeholder="App\Models\User:6352"
                               v-on:keyup.enter="monitorNewTag"
                               v-model="newTag"
                               id="newTagInput">
                        <label for="monitorReason" class="form-label mt-3">Reason</label>
                        <textarea v-model="reason" :disabled="saving" id="monitorReason" class="form-control" rows="3" maxlength="500" required></textarea>
                        <div v-if="error" class="alert alert-danger mt-3 mb-0" role="alert">{{ error }}</div>
                    </div>


                    <div class="modal-footer justify-content-start flex-row-reverse">
                        <button class="btn btn-primary" :disabled="saving" @click="monitorNewTag">
                            {{ saving ? 'Saving...' : editing ? 'Save' : 'Monitor' }}
                        </button>

                        <button class="btn" :disabled="saving" @click="cancelNewTag">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
