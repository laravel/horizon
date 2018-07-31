<script>
import MainSidebar from './MainSidebar.vue'

export default {
    components: {MainSidebar},
    data() {
        return {
            logo: document.querySelector('meta[name="logo"]').getAttribute('content')
        }
    }
}
</script>

<template>
    <div class="container">
        <div id="mainHeader" class="pt-4 pb-4">
            <div class="row">
                <div class="col">
                    <img :src="logo">
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
    </div>
</template>

