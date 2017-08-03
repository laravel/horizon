<script type="text/ecmascript-6">
    import Layout from '../../layouts/MainLayout.vue'
    import Panel from '../../components/Panels/Panel.vue'
    import Message from '../../components/Messages/Message.vue'
    import AddTagModal from '../../components/Modals/AddTagModal.vue'
    import PanelHeading from '../../components/Panels/PanelHeading.vue'
    import PanelContent from '../../components/Panels/PanelContent.vue'

    export default {
        components: {Message, Layout, Panel, PanelContent, PanelHeading, AddTagModal},


        /**
         * The component's data.
         */
        data() {
            return {
                loadingTags: true,
                tags: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Horizon - Monitoring";

            this.loadTags();

            Bus.$on('tagAdded', data => this.startMonitoringTag(data.tag));
        },


        methods: {
            /**
             * Load the monitored tags.
             */
            loadTags() {
                this.loadingTags = true;

                this.$http.get('/horizon/api/monitoring')
                        .then(response => {
                            this.tags = response.data;

                            this.loadingTags = false;
                        });
            },


            /**
             * Open the modal for adding a new tag.
             */
            openTagModal() {
                this.$root.showModal = true;
            },


            /**
             * Start monitoring a new tag.
             */
            startMonitoringTag(tag){
                this.tags.push({tag: tag, count: 0});
            },


            /**
             * Stop monitoring the given tag.
             */
            stopMonitoring(tag) {
                this.$http.delete('/horizon/api/monitoring/' + encodeURIComponent(tag))
                        .then(() => {
                            this.tags = _.reject(this.tags, existing => existing.tag == tag)
                        })
            }
        }
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <panel>
                <panel-heading>
                    Monitoring
                    <button @click="openTagModal" class="btn btn-primary btn-md">Monitor Tag</button>
                </panel-heading>

                <panel-content :loading="loadingTags">
                    <message v-if="!tags.length" text="You're not monitoring any tags."/>

                    <table v-else class="table panel-table" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th class="ph2" width="400">Tag Name</th>
                            <th>Jobs</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr v-for="tag in tags">
                            <td class="ph2">
                                <router-link :to="{ name: 'monitoring.detail.index', params: { tag:tag.tag }}"
                                             href="#" class="fw7">{{ tag.tag }}
                                </router-link>
                            </td>
                            <td>{{ tag.count }}</td>
                            <td>
                                <button @click="stopMonitoring(tag.tag)" class="btn btn-primary btn-md">Stop Monitoring</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </panel-content>
            </panel>
        </section>

        <add-tag-modal/>
    </layout>
</template>
