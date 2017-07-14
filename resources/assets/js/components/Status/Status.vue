<script type="text/ecmascript-6">
    export default {
        props: {
            active: {},
            pending: {},
            activeClass: {
                default: 'fsuccess'
            },
        },


        /**
         * The component's data.
         */
        data() {
            return {
                theActiveClass: this.activeClass
            }
        },
    }
</script>

<template>
    <i class="ico20">
        <svg v-if="active" class="fcc" :class="[theActiveClass]">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-checkmark-outline"></use>
        </svg>
        <svg v-else-if="!pending" class="fcc fdanger">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-close-outline"></use>
        </svg>

        <svg v-if="pending" class="fcc fwarning">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-pause-outline"></use>
        </svg>
    </i>
</template>

