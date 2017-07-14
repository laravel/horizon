<script type="text/ecmascript-6">
    import Spinner from '../../components/Loaders/Spinner.vue'

    export default {
        props: ['loading'],


        components: {Spinner}
    }
</script>

<template>
    <div v-if="!loading" class="panel panel-default mb3">
        <slot/>
    </div>
    
    <div v-else class="panel panel-default mb3 pa8 df aic acc jcc w100%">
        <spinner/>
    </div>
</template>

