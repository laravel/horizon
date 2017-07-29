<script type="text/ecmascript-6">
    import $ from 'jQuery'
    import Modal from './Modal.vue'
    import Panel from '../Panels/Panel.vue'
    import Spinner from '../Loaders/Spinner.vue'
    import ModalBody from '../Modals/ModalBody.vue'
    import ModalHeading from '../Modals/ModalHeading.vue'

    export default {
        components: {Modal, ModalHeading, ModalBody, Spinner},


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
        watch: {
            '$root.showModal'() {
                this.name = '';

                this.$nextTick(_ => {
                    this.$refs.tag.focus();
                })
            }
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

                this.$http.post('/horizon/api/monitoring', {'tag': this.name})
                        .then(response => {
                            this.$root.showModal = false;

                            Bus.$emit('tagAdded', {
                                tag: this.name
                            });

                            this.saving = false;
                        })

            }
        }
    }
</script>

<template>
    <modal width="460">
        <modal-heading>
            <h3 class="ft22">Monitor Tag</h3>
        </modal-heading>
        <modal-body>
            <div v-if="saving" class="pa2 df aic acc jcc">
                <spinner/>
            </div>
            <div v-else class="pa2">
                <div class="frame mb2">
                    <p class="blk4 ft15 lh2 basic-text tar">
                        <label for="field[tag]">Tag Name</label>
                    </p>
                    <div class="blk8">
                        <input v-on:keyup.enter="saveTag" type="text" id="field[tag]" name="field[tag]"
                               class="form-control" v-model="name" ref="tag">
                    </div>
                </div>
                <div class="frame">
                    <div class="blk4">
                    </div>
                    <div class="blk8">
                        <button @click="saveTag" class="btn btn-primary btn-md">
                            <i class="ico ico-baseline mr0.5">
                                <svg class="fcc fw">
                                    <use xmlns:xlink="http://www.w3.org/1999/xlink"
                                         xlink:href="#zondicon-search"></use>
                                </svg>
                            </i>
                            Monitor
                        </button>
                    </div>
                </div>
            </div>
        </modal-body>
    </modal>
</template>
