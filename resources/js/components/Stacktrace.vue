<script type="text/ecmascript-6">
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
            lines() {
                return this.trace.slice(0, this.showAll ? 1000 : this.minimumLines);
            }
        }
    }
</script>

<template>
    <div class="table-responsive">
        <table class="table mb-0">
            <tbody>
            <tr v-for="line in lines">
                <td class="card-bg-secondary"><code>{{line}}</code></td>
            </tr>

            <tr v-if="! showAll">
                <td class="card-bg-secondary"><a href="*" class="text-decoration-none" v-on:click.prevent="showAll = true">Show All</a></td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>

</style>
