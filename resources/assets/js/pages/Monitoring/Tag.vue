<script type="text/ecmascript-6">
    import Tab from '../../components/Tabs/Tab.vue'
    import Tabs from '../../components/Tabs/Tabs.vue'
    import Layout from '../../layouts/MainLayout.vue'
    import Panel from '../../components/Panels/Panel.vue'
    import Spinner from '../../components/Loaders/Spinner.vue'
    import PanelHeading from '../../components/Panels/PanelHeading.vue'
    import PanelContent from '../../components/Panels/PanelContent.vue'

    export default {
        components: {Layout, Panel, PanelContent, PanelHeading, Tab, Tabs, Spinner}
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <panel>
                <panel-heading>
                    Recent Jobs for "{{ $route.params.tag }}"
                </panel-heading>

                <panel-content>
                    <tabs>
                        <tab>
                            <router-link :to="{ name: 'monitoring.detail.index', params: { tag: $route.params.tag }}" exact active-class="tab-link-active" class="tab-link">All</router-link>
                        </tab>
                        <tab>
                            <router-link :to="{ name: 'monitoring.detail.failed', params: { tag: $route.params.tag }}" active-class="tab-link-active" class="tab-link">Failed</router-link>
                        </tab>
                    </tabs>

                    <router-view/>
                </panel-content>
            </panel>
        </section>
    </layout>
</template>
