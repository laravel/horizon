export default [
    {path: '/', redirect: '/dashboard'},

    {
        path: '/dashboard',
        name: 'dashboard',
        component: require('./screens/dashboard').default
    },

    {
        path: '/monitoring',
        name: 'monitoring',
        component: require('./screens/monitoring/index').default
    },

    {
        path: '/monitoring/:tag',
        name: 'monitoring-jobs',
        component: require('./screens/monitoring/jobs').default
    },

    //
    // {
    //     path: '/monitoring/:tag/failed',
    //     name: 'monitoring-failed',
    //     component: require('./screens/monitoring/failed').default
    // },
    //
    // {path: '/metrics', redirect: '/metrics/jobs'},
    //
    // {
    //     path: '/metrics/jobs',
    //     name: 'metrics-jobs',
    //     component: require('./screens/metrics/jobs').default
    // },
    //
    // {
    //     path: '/metrics/queues',
    //     name: 'metrics-queues',
    //     component: require('./screens/metrics/queues').default
    // },
    //
    // {
    //     path: '/metrics/:type/:slug',
    //     name: 'metrics-preview',
    //     component: require('./screens/metrics/preview').default
    // },
    //
    // {
    //     path: '/recent-jobs',
    //     name: 'recent-jobs',
    //     component: require('./screens/recentJobs/index').default
    // },
    //
    // {
    //     path: '/failed',
    //     name: 'failed-jobs',
    //     component: require('./screens/failedJobs/index').default
    // },
    //
    // {
    //     path: '/failed/:jobId',
    //     name: 'failed-jobs-preview',
    //     component: require('./screens/failedJobs/preview').default
    // },
];
