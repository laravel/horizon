<script type="text/ecmascript-6">
    import ModalContent from '../Modals/ModalContent.vue'

    export default {
        props: ['width'],


        components: {
            ModalContent,
        },


        /**
         * The component's data.
         */
        data() {
            return {
                modalWidth: `${this.width}px`
            }
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.addEventListener("keydown", (e) => {
                if (e.keyCode == 27) {
                    this.$root.showModal = false;
                }
            })
        },


        methods: {
            /**
             * Close the modal
             */
            close() {
                this.$root.showModal = false;
            }
        },
    }
</script>

<template>
    <transition name="fade">
        <div @click="close" v-show="$root.showModal" class="modal">
            <modal-content :style="{width: modalWidth}">
                <slot/>
            </modal-content>
        </div>
    </transition>
</template>
