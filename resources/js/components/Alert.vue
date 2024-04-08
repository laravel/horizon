<script type="text/ecmascript-6">
    import { Modal } from 'bootstrap';

    export default {
        props: ['type', 'message', 'autoClose', 'confirmationProceed', 'confirmationCancel'],

        data(){
            return {
                timeout: null,
                alertModal: null,
                anotherModalOpened: document.body.classList.contains('modal-open')
            }
        },


        mounted() {
            const alertModalElement = document.getElementById('alertModal');

            this.alertModal = Modal.getOrCreateInstance(alertModalElement, {
                backdrop: 'static',
            })

            this.alertModal.show();

            alertModalElement.addEventListener('hidden.bs.modal', e => {
                this.$root.alert.type = null;
                this.$root.alert.autoClose = false;
                this.$root.alert.message = '';
                this.$root.alert.confirmationProceed = null;
                this.$root.alert.confirmationCancel = null;

                if (this.anotherModalOpened) {
                    document.body.classList.add('modal-open');
                }
            }, this);

            if (this.autoClose) {
                this.timeout = setTimeout(() => {
                    this.close();
                }, this.autoClose);
            }
        },


        methods: {
            /**
             * Close the modal.
             */
            close(){
                clearTimeout(this.timeout);

                this.alertModal.hide();
            },


            /**
             * Confirm and close the modal.
             */
            confirm(){
                this.confirmationProceed();

                this.close();
            },


            /**
             * Cancel and close the modal.
             */
            cancel(){
                if (this.confirmationCancel) {
                    this.confirmationCancel();
                }

                this.close();
            }
        }
    }
</script>

<template>
    <div class="modal" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <p class="m-0 py-4">{{message}}</p>
                </div>


                <div class="modal-footer justify-content-start flex-row-reverse">

                    <button v-if="type == 'error'" class="btn btn-primary" @click="close">
                        Close
                    </button>

                    <button v-if="type == 'success'" class="btn btn-primary" @click="close">
                        Okay
                    </button>


                    <button v-if="type == 'confirmation'" class="btn btn-danger" @click="confirm">
                        Yes
                    </button>
                    <button v-if="type == 'confirmation'" class="btn" @click="cancel">
                        Cancel
                    </button>

                </div>
            </div>
        </div>
    </div>
</template>

<style>
    #alertModal {
        z-index: 99999;
        background: rgba(0, 0, 0, 0.5);
    }

    #alertModal svg {
        display: block;
        margin: 0 auto;
        width: 4rem;
        height: 4rem;
    }
</style>
