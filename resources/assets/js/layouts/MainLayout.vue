<script type="text/ecmascript-6">
    import Status from '../components/Status/Status.vue'
    import MainSidebar from './MainSidebar.vue'

    export default {
        components: {Status, MainSidebar}
    }
</script>

<template>
    <div class="container">
        <div id="mainHeader" class="pt-4 pb-4">
            <div class="row">
                <div class="col">
                    <img src="/vendor/horizon/img/horizon.svg">
                </div>
            </div>
        </div>

        <main class="mt-4 mb-4">
            <div class="row">
                <div class="col-md-2">
                    <main-sidebar/>
                </div>

                <div class="col-md-10">
                    <slot/>
                </div>
            </div>
        </main>

        <footer id="mainFooter" class="pt-4 pb-4 text-center">
            Laravel is a trademark of Taylor Otwell. Copyright © Laravel LLC. All rights reserved.
        </footer>
    </div>
</template>

