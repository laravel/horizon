<script type="text/ecmascript-6">
    export default {
        /**
         * Prepare the component.
         */
        created() {
            document.title = "Horizon - Statistics";
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Statistics</h5>
            </div>

            <ul class="nav nav-pills card-bg-secondary">
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'statistics-pending' }" href="#">
                        Pending
                    </router-link>
                </li>

                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'statistics-completed' }" href="#">
                        Completed
                    </router-link>
                </li>

                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'statistics-failed' }" href="#">
                        Failed
                    </router-link>
                </li>
            </ul>

            <router-view/>
        </div>

    </div>
</template>
