<script type="text/ecmascript-6">
    export default {
        props: ['type', 'text'],


        computed: {
            messageStyles() {
                return {
                    'basic-text': this.type == '',
                    'bgdanger tcw': this.type == 'error'
                }
            }
        }
    }
</script>

<template>
    <div class="df aic acc jcc pa4" :class="messageStyles">
        <p class="ft15">
            {{ text }}
        </p>
    </div>
</template>

