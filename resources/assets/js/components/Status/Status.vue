<script>
export default {
    props: {
        active: {},
        pending: {},
        activeClass: {
            default: 'fsuccess'
        }
    },
    data() {
        return {
            theActiveClass: this.activeClass
        }
    }
}
</script>

<template>
    <div class="statusIcon">
        <svg v-if="active" class="fill-success">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-checkmark-outline"/>
        </svg>
        <svg v-else-if="!pending" class="fill-danger">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-close-outline"/>
        </svg>
        <svg v-if="pending" class="fill-warning">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-pause-outline"/>
        </svg>
    </div>
</template>

