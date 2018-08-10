import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router);

export default new Router({
    mode: 'history',
    base: `/${horizon.uri}`,
    routes: [
        {
            path: '/',
            redirect: '/dashboard',
        },
        {
            path: '/dashboard',
            component: require('./pages/Dashboard.vue'),
        },
        {
            path: '/monitoring',
            component: require('./pages/Monitoring/Index.vue'),
        },
        {
            path: '/monitoring/:tag',
            component: require('./pages/Monitoring/Tag.vue'),
            children: [
                {
                    path: '/',
                    name: 'monitoring.detail.index',
                    component: require('./pages/Monitoring/Jobs.vue'),
                    props: {type: 'index'}
                },
                {
                    path: 'failed',
                    name: 'monitoring.detail.failed',
                    component: require('./pages/Monitoring/Jobs.vue'),
                    props: {type: 'failed'}
                },
            ],
        },
        {
            path: '/metrics',
            component: require('./pages/Metrics/Index.vue'),
            children: [
                {
                    path: '/',
                    redirect: 'jobs',
                },
                {
                    path: 'jobs',
                    component: require('./pages/Metrics/Jobs.vue')
                },
                {
                    path: 'queues',
                    component: require('./pages/Metrics/Queues.vue')
                },
            ],
        },
        {
            path: '/metrics/:type/:slug',
            name: 'metrics.detail',
            component: require('./pages/Metrics/Metric.vue'),
            props: true,
        },
        {
            path: '/recent-jobs',
            name: 'recent-jobs.detail',
            component: require('./pages/RecentJobs/Index.vue'),
        },
        {
            path: '/failed',
            component: require('./pages/Failed/Index.vue'),
        },
        {
            path: '/failed/:jobId',
            name: 'failed.detail',
            component: require('./pages/Failed/Job.vue'),
            props: true,
        },
    ],
})
