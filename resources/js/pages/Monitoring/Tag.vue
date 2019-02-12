<script type="text/ecmascript-6">
    import Layout from '../../layouts/MainLayout.vue'

    export default {
        components: {Layout}
    }
</script>

<template>
    <layout>
        <section class="main-content">
            <div class="card">
                <div class="card-header">
                    Recent Jobs for "{{ $route.params.tag }}"
                </div>

                <ul class="nav nav-tabs card-tabs">
                    <li class="nav-item">
                        <router-link :to="{ name: 'monitoring.detail.index', params: { tag: $route.params.tag }}" exact active-class="active" class="nav-link">All</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link :to="{ name: 'monitoring.detail.failed', params: { tag: $route.params.tag }}" active-class="active" class="nav-link">Failed</router-link>
                    </li>
                </ul>

                <router-view/>
            </div>
        </section>
    </layout>
</template>
