<script type="text/ecmascript-6">
    import $ from 'jquery'

    export default {
        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                name: '',
                saving: false
            };
        },


        /**
         * Watch these properties for changes.
         */
        mounted() {
            $('#addTagModal').modal();

            $('#addTagModal').on('hidden.bs.modal', e => {
                Bus.$emit('addTagModalClosed');
            });

            this.name = '';

            this.$nextTick(_ => {
                this.$refs.tag.focus();
            })
        },


        methods: {
            /**
             * Save the tag and hide the modal.
             */
            saveTag() {
                if (!this.name) {
                    this.$refs.tag.focus();
                    return;
                }

                this.saving = true;

                this.$http.post('/api/monitoring', {'tag': this.name})
                    .then(response => {
                        $('#addTagModal').modal('hide');

                        Bus.$emit('tagAdded', {tag: this.name});

                        this.saving = false;
                    })
            }
        }
    }
</script>

<template>
    <div class="modal" tabindex="-1" role="dialog" id="addTagModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Monitor Tag</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Tag Name</label>
                        <div class="col-sm-9">
                            <input v-on:keyup.enter="saveTag" type="text" class="form-control" v-model="name" ref="tag">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-9 offset-md-3">
                            <button @click="saveTag" type="button" class="btn btn-primary" :disabled="saving">Monitor</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
