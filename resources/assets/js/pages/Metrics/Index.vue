<script type="text/ecmascript-6">
    import Layout from '../../layouts/MainLayout.vue'

    export default {
        components: {Layout},


        /**
         * Prepare the component.
         */
        created() {
            document.title = "Horizon - Metrics";
        }
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card">
                <div class="card-header">Metrics</div>

                <ul class="nav nav-tabs card-tabs">
                    <li class="nav-item">
                        <router-link to="/metrics/jobs" active-class="active" class="nav-link">Jobs</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/metrics/queues" active-class="active" class="nav-link">Queues</router-link>
                    </li>
                </ul>

                <router-view/>
            </div>
        </section>
    </layout>
</template>
