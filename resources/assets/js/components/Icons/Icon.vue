<script type="text/ecmascript-6">
    export default {
        props: {
            'icon': {},
            'color': {
                default: 'fg8'
            },
            'size': {
                default: 'default'
            },
            'spin': {
                default: false
            }
        },


        data() {
            return {
                sizes: {
                    sm: 'ico',
                    default: 'ico20',
                }
            }
        },


        computed: {
            iconSize() {
                return this.sizes[this.size]
            },
            iconName() {
                return `#zondicon-${this.icon}`
            },
            classNames() {
                return {}
            }
        },
    }
</script>

<template>
    <i :class="iconSize">
        <svg class="fcc" :class="[ color, {'spin':spin} ]">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" :xlink:href="iconName"></use>
        </svg>
    </i>
</template>
