import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router);

export default new Router({
    mode: 'history',
    base: '/horizon/',
    routes: [
        {
            path: '/',
            redirect: '/dashboard',
        },
        {
            path: '/dashboard',
            component: require('./pages/Dashboard.vue').default,
        },
        {
            path: '/monitoring',
            component: require('./pages/Monitoring/Index.vue').default,
        },
        {
            path: '/monitoring/:tag',
            component: require('./pages/Monitoring/Tag.vue').default,
            children: [
                {
                    path: '/',
                    name: 'monitoring.detail.index',
                    component: require('./pages/Monitoring/Jobs.vue').default,
                    props: {type: 'index'}
                },
                {
                    path: 'failed',
                    name: 'monitoring.detail.failed',
                    component: require('./pages/Monitoring/Jobs.vue').default,
                    props: {type: 'failed'}
                },
            ],
        },
        {
            path: '/metrics',
            component: require('./pages/Metrics/Index.vue').default,
            children: [
                {
                    path: '/',
                    redirect: 'jobs',
                },
                {
                    path: 'jobs',
                    component: require('./pages/Metrics/Jobs.vue').default
                },
                {
                    path: 'queues',
                    component: require('./pages/Metrics/Queues.vue').default
                },
            ],
        },
        {
            path: '/metrics/:type/:slug',
            name: 'metrics.detail',
            component: require('./pages/Metrics/Metric.vue').default,
            props: true,
        },
        {
            path: '/recent-jobs',
            name: 'recent-jobs.detail',
            component: require('./pages/RecentJobs/Index.vue').default,
        },
        {
            path: '/failed',
            component: require('./pages/Failed/Index.vue').default,
        },
        {
            path: '/failed/:jobId',
            name: 'failed.detail',
            component: require('./pages/Failed/Job.vue').default,
            props: true,
        },
    ],
})
