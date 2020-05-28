export default [
    { path: '/', redirect: '/dashboard' },

    {
        path: '/dashboard',
        name: 'dashboard',
        component: require('./screens/dashboard').default,
    },

    {
        path: '/monitoring',
        name: 'monitoring',
        component: require('./screens/monitoring/index').default,
    },

    {
        path: '/monitoring/:tag',
        component: require('./screens/monitoring/tag').default,
        children: [
            {
                path: 'jobs',
                name: 'monitoring-jobs',
                component: require('./screens/monitoring/tag-jobs').default,
                props: { type: 'jobs' },
            },
            {
                path: 'failed',
                name: 'monitoring-failed',
                component: require('./screens/monitoring/tag-jobs').default,
                props: { type: 'failed' },
            },
        ],
    },

    { path: '/metrics', redirect: '/metrics/jobs' },

    {
        path: '/metrics/',
        component: require('./screens/metrics/index').default,
        children: [
            {
                path: 'jobs',
                name: 'metrics-jobs',
                component: require('./screens/metrics/jobs').default,
            },
            {
                path: 'queues',
                name: 'metrics-queues',
                component: require('./screens/metrics/queues').default,
            },
        ],
    },

    {
        path: '/metrics/:type/:slug',
        name: 'metrics-preview',
        component: require('./screens/metrics/preview').default,
    },

    {
        path: '/jobs/:type',
        name: 'jobs',
        component: require('./screens/recentJobs/index').default,
    },

    {
        path: '/jobs/pending/:jobId',
        name: 'pending-jobs-preview',
        component: require('./screens/recentJobs/job').default,
    },

    {
        path: '/jobs/completed/:jobId',
        name: 'completed-jobs-preview',
        component: require('./screens/recentJobs/job').default,
    },

    {
        path: '/failed',
        name: 'failed-jobs',
        component: require('./screens/failedJobs/index').default,
    },

    {
        path: '/failed/:jobId',
        name: 'failed-jobs-preview',
        component: require('./screens/failedJobs/job').default,
    },

    {
        path: '/batches',
        name: 'batches',
        component: require('./screens/batches/index').default,
    },

    {
        path: '/batches/:batchId',
        name: 'batches-preview',
        component: require('./screens/batches/preview').default,
    },
];
