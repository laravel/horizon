<script setup lang="ts">
import { ref, onBeforeMount, onMounted, onBeforeUnmount } from 'vue';

interface Props {
    interval?: number;
    keepAlive?: boolean;
    immediate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    interval: 3,
    keepAlive: false,
    immediate: true
});

const emit = defineEmits<{
    poll: [];
}>();

const loading = ref(0);
const lastExecutionTime = ref(0);
const pollingInterval = ref(0);
const poll = ref<number | null>(null);
const visibilitychangeListener = ref<(() => void) | null>(null);

const emitPoll = () => {
    if (loading.value) {
        return;
    }

    loading.value++;
    emit('poll');
    loading.value--;
    lastExecutionTime.value = Date.now();
};

const removeListener = () => {
    if (poll.value) {
        clearInterval(poll.value);
        poll.value = null;
    }
};

const createListener = () => {
    poll.value = window.setInterval(() => {
        emitPoll();
    }, pollingInterval.value);
};

const updatePollingInterval = () => {
    if (props.keepAlive) {
        pollingInterval.value = props.interval * 1000;
        return;
    }

    if (document.visibilityState === 'visible') {
        pollingInterval.value = 1000 * props.interval;
    } else if (document.visibilityState === 'hidden') {
        // One hour...
        pollingInterval.value = 1000 * 60 * 60;
    }
};

const changedVisibility = () => {
    updatePollingInterval();
    removeListener();
    createListener();

    // throttling
    if ((Date.now() - lastExecutionTime.value) >= pollingInterval.value) {
        emitPoll();
    }
};

onBeforeMount(() => {
    updatePollingInterval();

    if (props.immediate) {
        emitPoll();
    }
});

onMounted(() => {
    createListener();

    if (!props.keepAlive) {
        visibilitychangeListener.value = changedVisibility;
        document.addEventListener('visibilitychange', visibilitychangeListener.value);
    }
});

onBeforeUnmount(() => {
    removeListener();

    if (visibilitychangeListener.value) {
        document.removeEventListener('visibilitychange', visibilitychangeListener.value);
    }
});
</script>

<template>
    <!-- This component doesn't render anything -->
</template>
