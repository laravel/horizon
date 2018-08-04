<script>
export default {
    components: {},
    data() {
        return {
            name: '',
            saving: false,
            showModal: true
        }
    },
    mounted() {
        this.name = ''

        this.$nextTick(() => {
            this.$refs.tag.focus()
        })
    },

    methods: {
        /**
         * Save the tag and hide the modal.
         */
        saveTag() {
            if (!this.name) {
                this.$refs.tag.focus()
                return
            }

            this.saving = true

            axios.post('/horizon/api/monitoring', {'tag': this.name})
                .then(({data}) => {
                    this.showModal = false
                    this.saving = false

                    Bus.$emit('tagAdded', {tag: this.name})
                })
        },
        hideModal() {
            this.showModal = false
        }
    },
    watch: {
        showModal: {
            handler(val) {
                if (!val) Bus.$emit('addTagModalClosed')

                document.querySelector('body').classList.toggle('modal-open')
            },
            immediate: true
        }
    }
}
</script>

<template>
    <div id="addTagModal" :class="{'show': showModal}" class="modal" tabindex="-1" role="dialog">
        <div class="modal-backdrop show" @click="hideModal"/>

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Monitor Tag</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Tag Name</label>
                        <div class="col-sm-9">
                            <input ref="tag" v-model="name" type="text" class="form-control" @keyup.enter="saveTag">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-9 offset-md-3">
                            <button :disabled="saving" type="button" class="btn btn-primary" @click="saveTag">Monitor</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
