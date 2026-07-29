<template>
    <tr class="table-empty-row" :aria-label="title">
        <td :colspan="columns" class="p-0">
            <empty-state :title="title" :description="description" :icon="icon"></empty-state>
        </td>
    </tr>
</template>

<script type="text/ecmascript-6">
    import EmptyState from './EmptyState.vue';

    export default {
        components: {
            EmptyState,
        },

        props: {
            columns: {
                type: Number,
                required: true,
            },
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
