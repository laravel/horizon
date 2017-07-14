<script type="text/ecmascript-6">
    import Spinner from '../../components/Loaders/Spinner.vue'

    export default {
        props: ['loading'],


        components: {Spinner}
    }
</script>

<template>
    <div v-if="loading" class="panel-content">
        <div class="pa8 df aic acc jcc w100%">
            <spinner/>
        </div>
    </div>
    <div v-else class="panel-content">
        <slot/>
    </div>
</template>

