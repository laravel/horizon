<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Recent Jobs for "{{ $route.params.tag }}"</h5>
            </div>

            <ul class="nav nav-pills card-bg-secondary">
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'monitoring-jobs', params: { tag:$route.params.tag }}" href="#">
                        Recent Jobs
                    </router-link>
                </li>

                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'monitoring-failed', params: { tag:$route.params.tag }}" href="#">
                        Failed Jobs
                    </router-link>
                </li>
            </ul>

            <router-view/>
        </div>

    </div>
</template>
