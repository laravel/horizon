<script type="text/ecmascript-6">
    import Tab from '../../components/Tabs/Tab.vue'
    import Tabs from '../../components/Tabs/Tabs.vue'
    import Panel from '../../components/Panels/Panel.vue'
    import Layout from '../../layouts/MainLayout.vue'
    import PanelContent from '../../components/Panels/PanelContent.vue'
    import PanelHeading from '../../components/Panels/PanelHeading.vue'

    export default {
        components: {Layout, Panel, PanelContent, PanelHeading, Tab, Tabs},


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
            <panel>
                <panel-heading>Metrics</panel-heading>

                <panel-content>
                    <tabs>
                        <tab>
                            <router-link to="/metrics/jobs" active-class="tab-link-active" class="tab-link">Jobs</router-link>
                        </tab>
                        <tab>
                            <router-link to="/metrics/queues" active-class="tab-link-active" class="tab-link">Queues</router-link>
                        </tab>
                    </tabs>

                    <router-view/>
                </panel-content>
            </panel>
        </section>
    </layout>
</template>
