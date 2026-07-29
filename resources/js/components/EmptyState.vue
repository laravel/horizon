<template>
    <div class="empty-state">
        <div class="empty-state-content">
            <div class="empty-state-icon">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 20 20">
                    <template v-if="icon === 'pending'">
                        <path fill-rule="evenodd" d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm5-2.25A.75.75 0 017.75 7h.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-.5a.75.75 0 01-.75-.75v-4.5zm4 0a.75.75 0 01.75-.75h.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-.5a.75.75 0 01-.75-.75v-4.5z" clip-rule="evenodd"></path>
                    </template>

                    <template v-else-if="icon === 'completed'">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"></path>
                    </template>

                    <template v-else-if="icon === 'silenced'">
                        <path d="M4 8c0-.26.017-.517.049-.77l7.722 7.723a33.56 33.56 0 01-3.722-.01 2 2 0 003.862.15l1.134 1.134a3.5 3.5 0 01-6.53-1.409 32.91 32.91 0 01-3.257-.508.75.75 0 01-.515-1.076A11.448 11.448 0 004 8zM17.266 13.9a.756.756 0 01-.068.116L6.389 3.207A6 6 0 0116 8c.001 1.887.455 3.665 1.258 5.234a.75.75 0 01.01.666zM3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06L3.28 2.22z"></path>
                    </template>

                    <template v-else-if="icon === 'failed'">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                    </template>

                    <template v-else-if="icon === 'batches'">
                        <path fill-rule="evenodd" d="M2 3.75A.75.75 0 012.75 3h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 3.75zm0 4.167a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75zm0 4.166a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75zm0 4.167a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd"></path>
                    </template>

                    <template v-else-if="icon === 'monitoring'">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"></path>
                    </template>

                    <template v-else-if="icon === 'metrics'">
                        <path d="M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z"></path>
                    </template>

                    <template v-else-if="icon === 'queues'">
                        <path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v2.5A2.25 2.25 0 004.25 9h2.5A2.25 2.25 0 009 6.75v-2.5A2.25 2.25 0 006.75 2h-2.5zm0 9A2.25 2.25 0 002 13.25v2.5A2.25 2.25 0 004.25 18h2.5A2.25 2.25 0 009 15.75v-2.5A2.25 2.25 0 006.75 11h-2.5zm9-9A2.25 2.25 0 0011 4.25v2.5A2.25 2.25 0 0013.25 9h2.5A2.25 2.25 0 0018 6.75v-2.5A2.25 2.25 0 0015.75 2h-2.5zm0 9A2.25 2.25 0 0011 13.25v2.5A2.25 2.25 0 0013.25 18h2.5A2.25 2.25 0 0018 15.75v-2.5A2.25 2.25 0 0015.75 11h-2.5z" clip-rule="evenodd"></path>
                    </template>

                    <template v-else-if="icon === 'instances'">
                        <path fill-rule="evenodd" d="M6.5 3.25a3.25 3.25 0 00-3.25 3.25v7a3.25 3.25 0 003.25 3.25h7a3.25 3.25 0 003.25-3.25v-7a3.25 3.25 0 00-3.25-3.25h-7zM7 7a.75.75 0 01.75-.75h4.5A.75.75 0 0113 7v6a.75.75 0 01-.75.75h-4.5A.75.75 0 017 13V7z" clip-rule="evenodd"></path>
                        <path d="M6.25 1.75a.75.75 0 011.5 0v1.5h-1.5v-1.5zm3 0a.75.75 0 011.5 0v1.5h-1.5v-1.5zm3 0a.75.75 0 011.5 0v1.5h-1.5v-1.5zm-6 15h1.5v1.5a.75.75 0 01-1.5 0v-1.5zm3 0h1.5v1.5a.75.75 0 01-1.5 0v-1.5zm3 0h1.5v1.5a.75.75 0 01-1.5 0v-1.5zM1.75 6.25h1.5v1.5h-1.5a.75.75 0 010-1.5zm0 3h1.5v1.5h-1.5a.75.75 0 010-1.5zm0 3h1.5v1.5h-1.5a.75.75 0 010-1.5zm15-6h1.5a.75.75 0 010 1.5h-1.5v-1.5zm0 3h1.5a.75.75 0 010 1.5h-1.5v-1.5zm0 3h1.5a.75.75 0 010 1.5h-1.5v-1.5z"></path>
                    </template>

                    <template v-else>
                        <path fill-rule="evenodd" d="M5 4a3 3 0 013-3h4a3 3 0 013 3v1h1.5A1.5 1.5 0 0118 6.5v10a1.5 1.5 0 01-1.5 1.5h-13A1.5 1.5 0 012 16.5v-10A1.5 1.5 0 013.5 5H5V4zm3-1.5A1.5 1.5 0 006.5 4v1h7V4A1.5 1.5 0 0012 2.5H8z" clip-rule="evenodd"></path>
                    </template>
                </svg>
            </div>

            <div class="empty-state-title">{{ title }}</div>
            <div class="empty-state-description">{{ description }}</div>
        </div>
    </div>
</template>

<script type="text/ecmascript-6">
    export default {
        props: {
            title: {
                type: String,
                default: 'Nothing here yet',
            },
            description: {
                type: String,
                default: 'Entries will appear here when Horizon reports them.',
            },
            icon: {
                type: String,
                default: 'inbox',
            },
        },
    };
</script>
