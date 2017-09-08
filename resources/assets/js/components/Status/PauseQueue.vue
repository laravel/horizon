<script type="text/ecmascript-6">
    export default {

        data() {
            return {
                pausing: false,
            }
        },

        methods: {
            pause() {
                this.pausing = true;

                this.$http.post('/horizon/api/pause');
            },
        },
    }
</script>


<template>
    <a @click="pause" title="Pause the queue." v-if="!pausing">
        <slot></slot>
    </a>

    <a v-else>
        <i class="fa fa-circle-o-notch fa-spin fcwarning"/>
    </a>

</template>

<style scoped>
    a {
        cursor: pointer;
    }

    i {
        font-size:2rem;
    }
</style>
