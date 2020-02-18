<script type="text/ecmascript-6">
    import _take from "lodash/take"

    export default {
        props: ['trace'],

        /**
         * The component's data.
         */
        data() {
            return {
                minimumLines: 5,
                showAll: false,
            };
        },

        computed: {
            lines(){
                return this.showAll ? _take(this.trace, 1000) : _take(this.trace, this.minimumLines);
            }
        }
    }
</script>

<template>
    <table class="table mb-0">
        <tbody>
        <tr v-for="line in lines">
            <td class="card-bg-secondary">{{line}}</td>
        </tr>

        <tr v-if="! showAll">
            <td class="card-bg-secondary"><a href="*" v-on:click.prevent="showAll = true">Show All</a></td>
        </tr>
        </tbody>
    </table>
</template>

<style scoped>

</style>
