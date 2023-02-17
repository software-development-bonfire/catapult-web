<template>
    <div class="tab-pane fade show active" id="licensing" role="tabpanel" aria-labelledby="licensing-tab">
        <div>
            <div class="pull-left">
                <table
                    border="1"
                    class="
                        table-design
                        table-design--default">
                    <thead>
                        <tr>
                            <td align="center" width="100px">{{ $t('label.field') }}</td>
                            <td align="center" width="300px">{{ $t('label.values') }}</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $t('label.host') }} <span class="required">*</span></td>
                            <td>
                                <input type="text" class="form-control" v-model="form.values.host">
                                <label class="text-danger error-message m-0">
                                    Host is required.
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>{{ $t('label.client_id') }} <span class="required">*</span></td>
                            <td>
                                <input type="text" class="form-control" v-model="form.values.client_id">
                                <label class="text-danger error-message m-0">
                                    Client ID is required.
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>{{ $t('label.branch_code') }} <span class="required">*</span></td>
                            <td>
                                <input type="text" class="form-control" v-model="form.values.branch_code">
                                <label class="text-danger error-message m-0">
                                    Branch Code is required.
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>{{ $t('label.product_key') }} <span class="required">*</span></td>
                            <td>
                                <input type="text" class="form-control" v-model="form.values.product_key">
                                <label class="text-danger error-message m-0">
                                    Product Key is required.
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="form-group mt-3" align="right">
                    <button class="button button--dark" @click="authenticate">{{ $t('label.authenticate') }}</button>
                </div>
            </div>
        </div>
        <dialog-box
            :status="dialog.status"
            :type="dialog.type"
            :visible.sync="dialog.visible"
            @ok="dialog.ok.function"
            @cancel="dialog.cancel.function">
            <template slot="message">
                <span v-text="dialog.message"></span>
            </template>
        </dialog-box>
    </div>
</template>

<script>
    import DialogBox from '../../../components/Message/DialogBox.vue';

    export default {
        components: {
            DialogBox
        },
        props: {
            activeTab: {
                type: Boolean
            }
        },
        data() {
            return {
                form: {
                    values: {
                        host: '',
                        client_id: '',
                        product_key: '',
                    }
                },
                dialog: {
                    visible: false,
                    type: '',
                    message: '',
                    ok: {
                        function: () => {},
                        function: () => {}
                    },
                    cancel: {
                        function: () => {
                            this.dialog.visible = false;
                        },
                        function: () => {}
                    },
                },
            }
        },
        methods: {
            authenticate() {
                this.dialog.visible = true;
                this.dialog.status = 'success';
                this.dialog.message = 'Connection Successfull!';
                this.dialog.ok.function = () => {
                    this.dialog.visible = true;
                    this.dialog.status = 'error';
                    this.dialog.message = 'Unable to connect!';
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                };
            }
        }
    }
</script>
