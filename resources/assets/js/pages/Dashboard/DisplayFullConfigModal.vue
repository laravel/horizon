<script type="text/ecmascript-6">
    import $ from 'jquery'
    import _ from 'lodash';

    export default {
        components: {},

        props: {
            config: {
                type: Object,
            }
        },
        /**
         * The component's data.
         */
        data() {
            return {
                name: '',
                saving: false
            };
        },


        /**
         * Watch these properties for changes.
         */
        mounted() {
            $('#displayFullConfigModal').modal();

            $('#displayFullConfigModal').on('hidden.bs.modal', e => {
                Bus.$emit('displayFullConfigModalClosed');
            });
        },


        methods: {
            isIterable(arrayOrObject){
                return (_.isArray(arrayOrObject) || _.isObject(arrayOrObject));
            }
        }
    }
</script>

<template>
    <div class="modal" tabindex="-1" role="dialog" id="displayFullConfigModal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Full config</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group row" v-for="data,configKey in config">
                        <label class="col-sm-3 col-form-label">{{configKey}}</label>
                        <div class="col-sm-9">
                            <ul class="list-unstyled" v-if="isIterable(data)">
                                <li v-for="option, optionKey in data">
                                    <strong>{{optionKey}}</strong>: {{option}}
                                </li>
                            </ul>
                            <span v-else>{{data}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
