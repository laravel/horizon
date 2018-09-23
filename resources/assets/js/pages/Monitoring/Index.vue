<script>
import _reject from 'lodash/reject'
import Layout from '../../layouts/MainLayout.vue'
import AddTagModal from './AddTagModal.vue'

export default {
    components: {Layout, AddTagModal},
    data() {
        return {
            loadingTags: true,
            addTagModalOpened: false,
            tags: []
        }
    },
    mounted() {
        document.title = 'Horizon - Monitoring'

        this.loadTags()

        Bus.$on('tagAdded', (data) => {
            this.addTagModalOpened = false
            this.tags.push({tag: data.tag, count: 0})
        })

        Bus.$on('addTagModalClosed', (data) => {
            this.addTagModalOpened = false
        })
    },

    methods: {
        /**
         * Load the monitored tags.
         */
        loadTags() {
            this.loadingTags = true

            axios.get('/horizon/api/monitoring')
                .then(({data}) => {
                    this.tags = data

                    this.loadingTags = false
                })
        },

        /**
         * Open the modal for adding a new tag.
         */
        openTagModal() {
            this.addTagModalOpened = true
        },

        /**
         * Stop monitoring the given tag.
         */
        stopMonitoring(tag) {
            axios.delete('/horizon/api/monitoring/' + encodeURIComponent(tag))
                .then(() => {
                    this.tags = _reject(this.tags, (existing) => existing.tag == tag)
                })
        }
    }
}
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <span class="mr-auto">Monitoring</span>
                    <button class="btn btn-primary btn-md" @click="openTagModal">Monitor Tag</button>
                </div>

                <div class="table-responsive">
                    <loader :yes="loadingTags"/>

                    <p v-if="!loadingTags && !tags.length" class="text-center m-0 p-5">
                        You're not monitoring any tags.
                    </p>

                    <table v-if="!loadingTags && tags.length" class="table card-table table-hover">
                        <thead>
                            <tr>
                                <th>Tag Name</th>
                                <th>Jobs</th>
                                <th/>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="tag in tags" :key="tag.tag">
                                <td>
                                    <router-link :to="{ name: 'monitoring.detail.index', params: { tag:tag.tag }}"
                                                 href="#" class="fw7">{{ tag.tag }}
                                    </router-link>
                                </td>
                                <td>{{ tag.count }}</td>
                                <td class="text-right">
                                    <button class="btn btn-secondary" @click="stopMonitoring(tag.tag)">Stop Monitoring</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <add-tag-modal v-if="addTagModalOpened"/>
    </layout>
</template>
