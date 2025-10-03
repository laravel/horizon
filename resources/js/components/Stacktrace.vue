<script setup>
import { computed } from 'vue';

const props = defineProps(['trace']);

const minimumLines = ref(5);
const showAll = ref(false);

const lines = computed(() => {
    return props.trace.slice(0, showAll.value ? 1000 : minimumLines.value)
});
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
