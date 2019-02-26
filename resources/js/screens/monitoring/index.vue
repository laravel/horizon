<script type="text/ecmascript-6">
    import $ from 'jquery';

    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                newTag: '',
                addTagModalOpened: false,
                tags: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Monitoring";

            this.loadTags();

            this.$on('addTagModalClosed', data => {
                this.addTagModalOpened = false;
            });
        },


        methods: {
            /**
             * Load the monitored tags.
             */
            loadTags() {
                this.ready = false;

                this.$http.get('/' + Horizon.path + '/api/monitoring')
                    .then(response => {
                        this.tags = response.data;

                        this.ready = true;
                    });
            },


            /**
             * Open the modal for adding a new tag.
             */
            openNewTagModal() {
                $('#addTagModel').modal({
                    backdrop: 'static',
                });

                $('#newTagInput').focus();
            },


            /**
             * Monitor the given tag.
             */
            monitorNewTag() {
                if (!this.newTag) {
                    $('#newTagInput').focus();
                    return;
                }

                this.$http.post('/' + Horizon.path + '/api/monitoring', {'tag': this.newTag})
                    .then(response => {
                        $('#addTagModal').modal('hide');

                        this.tags.push({tag: this.newTag, count: 0});

                        $('#addTagModel').modal('hide');

                        this.newTag = '';
                    })
            },


            /**
             * Cancel adding a new tag.
             */
            cancelNewTag() {
                $('#addTagModel').modal('hide');

                this.newTag = '';
            },

            /**
             * Stop monitoring the given tag.
             */
            stopMonitoring(tag) {
                this.$http.delete('/' + Horizon.path + '/api/monitoring/' + encodeURIComponent(tag))
                    .then(() => {
                        this.tags = _.reject(this.tags, existing => existing.tag == tag)
                    })
            }
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Monitoring</h5>

                <button @click="openNewTagModal" class="btn btn-primary btn-sm">Monitor Tag</button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && tags.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>You're not monitoring any tags.</span>
            </div>


            <table v-if="ready && tags.length > 0" class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Tag Name</th>
                    <th>Jobs</th>
                    <th class="text-right"></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="tag in tags">
                    <td>
                        <router-link :to="{ name: 'monitoring-jobs', params: { tag:tag.tag }}" href="#">
                            {{ tag.tag }}
                        </router-link>
                    </td>
                    <td>{{ tag.count }}</td>
                    <td class="text-right">
                        <a href="#" @click="stopMonitoring(tag.tag)" class="control-action" title="Stop Monitoring">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z"></path>
                            </svg>
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="modal" id="addTagModel" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">Monitor New Tag</div>

                    <div class="modal-body">
                        <input type="text" class="form-control" placeholder="App\User:6352"
                               v-on:keyup.enter="monitorNewTag"
                               v-model="newTag"
                               id="newTagInput">
                    </div>


                    <div class="modal-footer justify-content-center">
                        <button class="btn btn-primary" @click="monitorNewTag">
                            Monitor
                        </button>

                        <button class="btn" @click="cancelNewTag">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
