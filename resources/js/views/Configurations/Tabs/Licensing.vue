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
                            <td align="center" width="100px">Field</td>
                            <td align="center" width="300px">Values</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Host</td>
                            <td>
                                <input type="text" class="form-control">
                            </td>
                        </tr>
                        <tr>
                            <td>Client ID</td>
                            <td>
                                <input type="text" class="form-control">
                            </td>
                        </tr>
                        <tr>
                            <td>Product Key</td>
                            <td>
                                <input type="text" class="form-control">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="form-group mt-3" align="right">
                    <button class="button button--dark" @click="authenticate">Authenticate</button>
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
        data() {
            return {
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