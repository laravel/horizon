<script type="text/ecmascript-6">
    import Status from '../components/Status/Status.vue'
    import MainHeader from '../partials/MainHeader.vue'
    import MainFooter from '../partials/MainFooter.vue'
    import MainSidebar from '../partials/MainSidebar.vue'

    export default {
        components: {Status, MainHeader, MainFooter, MainSidebar}
    }
</script>

<template>
    <div>
        <main-header>
            <div class="df aic acc">
                <p class="h34 df aic acc ft14 tcg7 uppercase"></p>
            </div>
        </main-header>

        <main>
            <div class="wrapper df">
                <main-sidebar/>

                <slot/>
            </div>
        </main>

        <main-footer/>
    </div>
</template>

