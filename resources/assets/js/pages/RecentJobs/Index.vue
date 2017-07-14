<script type="text/ecmascript-6">
    import Layout from '../../layouts/MainLayout.vue'
    import Panel from '../../components/Panels/Panel.vue'
    import JobTable from './Jobs.vue'
    import PanelContent from '../../components/Panels/PanelContent.vue'
    import PanelHeading from '../../components/Panels/PanelHeading.vue'

    export default {
        components: {JobTable, Layout, Panel, PanelContent, PanelHeading},


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
            <panel>
                <panel-heading>Recent Jobs</panel-heading>

                <panel-content>
                    <job-table/>
                </panel-content>
            </panel>
        </section>
    </layout>
</template>

