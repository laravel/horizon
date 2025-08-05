<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Modal } from 'bootstrap';
import { getCurrentInstance } from 'vue';

interface Props {
    type: 'error' | 'success' | 'confirmation' | null;
    message: string;
    autoClose: number;
    confirmationProceed?: () => void;
    confirmationCancel?: () => void;
}

const props = defineProps<Props>();

const timeout = ref<number | null>(null);
const alertModal = ref<Modal | null>(null);
const anotherModalOpened = ref(document.body.classList.contains('modal-open'));

const instance = getCurrentInstance();

onMounted(() => {
    const alertModalElement = document.getElementById('alertModal');
    
    if (!alertModalElement) return;

    alertModal.value = Modal.getOrCreateInstance(alertModalElement, {
        backdrop: 'static',
    });

    alertModal.value.show();

    alertModalElement.addEventListener('hidden.bs.modal', () => {
        if (instance?.appContext.config.globalProperties.$root) {
            const root = instance.appContext.config.globalProperties.$root as any;
            root.alert.type = null;
            root.alert.autoClose = 0;
            root.alert.message = '';
            root.alert.confirmationProceed = null;
            root.alert.confirmationCancel = null;
        }

        if (anotherModalOpened.value) {
            document.body.classList.add('modal-open');
        }
    });

    if (props.autoClose) {
        timeout.value = window.setTimeout(() => {
            close();
        }, props.autoClose);
    }
});

/**
 * Close the modal.
 */
const close = () => {
    if (timeout.value) {
        clearTimeout(timeout.value);
    }

    alertModal.value?.hide();
};

/**
 * Confirm and close the modal.
 */
const confirm = () => {
    props.confirmationProceed?.();
    close();
};

/**
 * Cancel and close the modal.
 */
const cancel = () => {
    props.confirmationCancel?.();
    close();
};
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
