import dashboard from './screens/dashboard.vue';
import monitoring from './screens/monitoring/index.vue';
import monitoringTag from './screens/monitoring/tag.vue';
import monitoringTagJobs from './screens/monitoring/tag-jobs.vue';
import metrics from './screens/metrics/index.vue';
import metricsJobs from './screens/metrics/jobs.vue';
import metricsQueues from './screens/metrics/queues.vue';
import metricsPreview from './screens/metrics/preview.vue';
import recentJobs from './screens/recentJobs/index.vue';
import recentJobsJob from './screens/recentJobs/job.vue';
import failedJobs from './screens/failedJobs/index.vue';
import failedJobsJob from './screens/failedJobs/job.vue';
import batches from './screens/batches/index.vue';
import batchesPreview from './screens/batches/preview.vue';

export default [
    { path: '/', redirect: '/dashboard' },

    {
        path: '/dashboard',
        name: 'dashboard',
        component: dashboard,
    },

    {
        path: '/monitoring',
        name: 'monitoring',
        component: monitoring,
    },

    {
        path: '/monitoring/:tag',
        component: monitoringTag,
        children: [
            {
                path: 'jobs',
                name: 'monitoring-jobs',
                component: monitoringTagJobs,
                props: { type: 'jobs' },
            },
            {
                path: 'failed',
                name: 'monitoring-failed',
                component: monitoringTagJobs,
                props: { type: 'failed' },
            },
        ],
    },

    { path: '/metrics', redirect: '/metrics/jobs' },

    {
        path: '/metrics/',
        component: metrics,
        children: [
            {
                path: 'jobs',
                name: 'metrics-jobs',
                component: metricsJobs,
            },
            {
                path: 'queues',
                name: 'metrics-queues',
                component: metricsQueues,
            },
        ],
    },

    {
        path: '/metrics/:type/:slug',
        name: 'metrics-preview',
        component: metricsPreview,
    },

    {
        path: '/jobs/:type',
        name: 'jobs',
        component: recentJobs,
    },

    {
        path: '/jobs/pending/:jobId',
        name: 'pending-jobs-preview',
        component: recentJobsJob,
    },

    {
        path: '/jobs/completed/:jobId',
        name: 'completed-jobs-preview',
        component: recentJobsJob,
    },

    {
        path: '/jobs/silenced/:jobId',
        name: 'silenced-jobs-preview',
        component: recentJobsJob,
    },

    {
        path: '/failed',
        name: 'failed-jobs',
        component: failedJobs,
    },

    {
        path: '/failed/:jobId',
        name: 'failed-jobs-preview',
        component: failedJobsJob,
    },

    {
        path: '/batches',
        name: 'batches',
        component: batches,
    },

    {
        path: '/batches/:batchId',
        name: 'batches-preview',
        component: batchesPreview,
    },
];
