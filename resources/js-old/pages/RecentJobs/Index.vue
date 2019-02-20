<script type="text/ecmascript-6">
    import Layout from '../../layouts/MainLayout.vue'
    import JobTable from './Jobs.vue'

    export default {
        components: {JobTable, Layout},


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Recent Jobs";
        }
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card">
                <div class="card-header">Recent Jobs</div>

                <job-table/>
            </div>
        </section>
    </layout>
</template>

