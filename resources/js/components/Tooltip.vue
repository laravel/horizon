<script>
    let tooltipId = 0;

    export default {
        props: {
            disabled: {
                type: Boolean,
                default: false,
            },
        },

        data() {
            return {
                tooltipId: `horizon-tooltip-${tooltipId++}`,
            };
        },

        computed: {
            resolvedTooltipId() {
                return this.disabled ? undefined : this.tooltipId;
            },
        },
    };
</script>

<template>
    <span class="horizon-tooltip">
        <slot :tooltipId="resolvedTooltipId"></slot>

        <span
            v-if="!disabled"
            :id="tooltipId"
            class="horizon-tooltip-content"
            role="tooltip"
        >
            <slot name="content"></slot>
        </span>
    </span>
</template>
