<script type="text/ecmascript-6">
    export default {
        /**
         * Prepare the component.
         */
        created() {
            document.title = "Horizon - Metrics";
        }
    }
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Metrics</h2>
            </div>

            <ul class="nav nav-pills card-bg-secondary">
                <li class="nav-item">
                    <router-link class="nav-link text-decoration-none" active-class="active" :to="{ name: 'metrics-jobs'}" href="#">
                        Jobs
                    </router-link>
                </li>

                <li class="nav-item">
                    <router-link class="nav-link text-decoration-none" active-class="active" :to="{ name: 'metrics-queues'}" href="#">
                        Queues
                    </router-link>
                </li>
            </ul>

            <router-view/>
        </div>

    </div>
</template>
