<script type="text/ecmascript-6">
export default {
    /**
     * The component's data.
     */
    data() {
        return {
            succeededCount: 0,
            failedCount: 0,
        };
    },


    /**
     * Prepare the component.
     */
    mounted() {
        this.loadTagCounts();
    },


    /**
     * Watch these properties for changes.
     */
    watch: {
        '$route.params.tag'() {
            this.loadTagCounts();
        },
    },


    methods: {
        /**
         * Load the succeeded and failed counts for the current tag.
         */
        loadTagCounts() {
            this.$http.get(Horizon.basePath + '/api/monitoring')
                .then(response => {
                    const tagData = response.data.find(tag => tag.tag === this.$route.params.tag);

                    this.succeededCount = tagData ? tagData.succeeded_count : 0;
                    this.failedCount = tagData ? tagData.failed_count : 0;
                });
        },


        /**
         * Poll handler to refresh tab counters at regular intervals.
         */
        refreshTagCountsPeriodically() {
            this.loadTagCounts();
        }
    }
}
</script>

<template>
    <div>
        <poll @poll="refreshTagCountsPeriodically" />

        <div class="card overflow-hidden">
            <div
                class="card-header d-flex align-items-center justify-content-between"
            >
                <h2 class="h6 m-0">
                    Recent Jobs for "{{ $route.params.tag }}"
                </h2>
            </div>

            <ul class="nav nav-pills card-bg-secondary">
                <li class="nav-item">
                    <router-link
                        class="nav-link text-decoration-none"
                        active-class="active"
                        :to="{
                            name: 'monitoring-jobs',
                            params: { tag: $route.params.tag },
                        }"
                        href="#"
                    >
                        Recent Jobs ({{ succeededCount }})
                    </router-link>
                </li>

                <li class="nav-item">
                    <router-link
                        class="nav-link text-decoration-none"
                        active-class="active"
                        :to="{
                            name: 'monitoring-failed',
                            params: { tag: $route.params.tag },
                        }"
                        href="#"
                    >
                        Failed Jobs ({{ failedCount }})
                    </router-link>
                </li>
            </ul>

            <router-view />
        </div>
    </div>
</template>
