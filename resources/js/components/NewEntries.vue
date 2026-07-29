<template>
    <tr class="new-entries-row">
        <td :colspan="columns" class="new-entries-cell">
            <span class="new-entries-layer" aria-hidden="true">
                <svg class="new-entries-pattern" shape-rendering="crispEdges">
                    <defs>
                        <pattern
                            id="table-notice-pinstripe"
                            width="4"
                            height="4"
                            patternUnits="userSpaceOnUse"
                            patternTransform="rotate(45)"
                        >
                            <rect width="1" height="4" fill="currentColor" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#table-notice-pinstripe)" />
                </svg>
            </span>

            <div class="new-entries-content">
                <span class="new-entries-message">
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                    </svg>

                    <span v-if="!loading">
                        Results have changed.
                        <a href="#" @click.prevent="$emit('load')">Reload</a>
                    </span>

                    <span v-else>Loading...</span>
                </span>
            </div>
        </td>
    </tr>
</template>

<script type="text/ecmascript-6">
    export default {
        emits: ['load'],

        props: {
            columns: {
                type: Number,
                required: true,
            },
            loading: {
                type: Boolean,
                default: false,
            },
        },
    };
</script>
