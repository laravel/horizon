<script type="text/ecmascript-6">
    export default {

        data() {
            return {
                resuming: false,
            }
        },

        methods: {
            resume() {
                this.resuming = true;

                this.$http.delete('/horizon/api/pause');
            },
        },
    }
</script>


<template>

    <a @click="resume" title="Resume the queue." v-if="!resuming">
        <slot></slot>
    </a>

    <a v-else title="Please wait...">
        <i class="fa fa-circle-o-notch fa-spin fcsuccess"/>
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
