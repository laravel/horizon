<script type="text/ecmascript-6">
    export default {
        props: ['yes'],
    }
</script>

<template>
    <div v-if="yes" class="d-flex justify-content-center p-4">
        <i class="icon mr-2">
            <svg class="fill-primary spin">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#zondicon-refresh"></use>
            </svg>
        </i>
        <span>Loading...</span>
    </div>
</template>
