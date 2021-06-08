<template>
    <div class="tab-pane fade" id="remote-setup" role="tabpanel" aria-labelledby="remote-setup-tab">
        <div class="m-1">
            <button class="button button--dark" @click="create">Add New</button>
        </div>
        <datatable
            class="
                datatable--full-width
                datatable--hoverable"
            :header-fields="table.header"
            :settings="table.settings"
            :table="table.values">
            <template slot="content">
                <table-row
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:row-click="openDetail(tableData, tableDataIndex)">
                    <td class="datatable-cell">
                        <span v-text="tableData.remote_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.remote_path"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.remote_server"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.remote_ip"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.remote_port"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.remote_user"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.status"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <i class="fa fa-times-circle fa-lg row-delete" @click.stop="deleteRow(tableDataIndex)"></i>
                    </td>
                </table-row>
            </template>
        </datatable>
        <modal
            :height="600"
            centered-display
            v-if="modal.visible"
            @close="modal.visible = false">
            <template slot="header">
                Remote Setup Detail
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>Remote Setup Name</label>
                    <input type="text" class="form-control" v-model="form.values.remote_setup_name">
                </div>
                <div class="form-group">
                    <label>Remote Path</label>
                    <input type="text" class="form-control" v-model="form.values.remote_path">
                </div>
                <div class="form-group">
                    <label>Remote Server</label>
                    <input type="text" class="form-control" v-model="form.values.remote_server">
                </div>
                <div class="form-group">
                    <label>Remote IP</label>
                    <input type="text" class="form-control" v-model="form.values.remote_ip">
                </div>
                <div class="form-group">
                    <label>Remote Port</label>
                    <input type="text" class="form-control" v-model="form.values.remote_port">
                </div>
                <div class="form-group">
                    <label>Remote User</label>
                    <input type="text" class="form-control" v-model="form.values.remote_user">
                </div>
                <div class="form-group">
                    <label>Remote Password</label>
                    <input type="password" class="form-control" v-model="form.values.remote_password">
                </div>
                <div class="form-group">
                    <label>Setup Status</label>
                    <select class="form-control" v-model="form.values.status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </template>
            <template slot="footer">
                <div align="center">
                    <button class="button button--light" @click="save">Save</button>
                </div>
            </template>
        </modal>
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
    import Datatable from '../../../components/Datatable2/Datatable.vue';
    import TableRow from '../../../components/Datatable2/TableRow.vue';
    import Modal from '../../../components/Modal/Modal.vue';
    import DialogBox from '../../../components/Message/DialogBox.vue';
    import Util from '../../../mixins/Util.vue';

    export default {
        components: {
            Datatable,
            TableRow,
            Modal,
            DialogBox
        },
        mixins: [ Util ],
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
                modal: {
                    visible: false
                },
                form: {
                    mode: 'create',
                    values: {
                        remote_setup_name: '',
                        remote_path: '',
                        remote_server: '',
                        remote_ip: '',
                        remote_port: '',
                        remote_user: '',
                        remote_password: '',
                        status: 'Active',
                    }
                },
                table: {
                    header: [
                        {
                            name: "remote_setup_name",
                            label: "Remote Setup Name",
                            width: '200'
                        },
                        {
                            name: "remote_path",
                            label: 'Remote Path',
                            width: '250'
                        },
                        {
                            name: "remote_server",
                            label: 'Remote Server',
                            width: '150'
                        },
                        {
                            name: "remote_ip",
                            label: 'Remote IP',
                            width: '150'
                        },
                        {
                            name: "remote_port",
                            label: 'Remote Port',
                            width: '150'
                        },
                        {
                            name: "remote_user",
                            label: 'Remote User',
                            width: '150'
                        },
                        {
                            name: "status",
                            label: 'Status',
                            width: '90'
                        },
                        {
                            name: "actions",
                            label: '',
                            width: '50'
                        }
                    ],
                    values: {
                        data: [
                            {
                                remote_setup_name: 'Transactions',
                                remote_path: 'ftp://pathto POS Transactions',
                                remote_server: 'Bonfire 1',
                                remote_ip: '192.168.2.92',
                                remote_port: '92',
                                remote_user: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                remote_setup_name: 'Transactions',
                                remote_path: 'ftp://pathto POS Transactions',
                                remote_server: 'Bonfire 1',
                                remote_ip: '192.168.2.92',
                                remote_port: '92',
                                remote_user: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                remote_setup_name: 'Transactions',
                                remote_path: 'ftp://pathto POS Transactions',
                                remote_server: 'Bonfire 1',
                                remote_ip: '192.168.2.92',
                                remote_port: '92',
                                remote_user: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                remote_setup_name: 'Transactions',
                                remote_path: 'ftp://pathto POS Transactions',
                                remote_server: 'Bonfire 1',
                                remote_ip: '192.168.2.92',
                                remote_port: '92',
                                remote_user: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                remote_setup_name: 'Transactions',
                                remote_path: 'ftp://pathto POS Transactions',
                                remote_server: 'Bonfire 1',
                                remote_ip: '192.168.2.92',
                                remote_port: '92',
                                remote_user: 'Admin Bonfire 1',
                                status: 'Active',
                            }
                        ],
                        meta: {
                            pagination: {
                                count: 1,
                                current_page: 1,
                                links: {},
                                per_page: 10,
                                total: 1,
                                total_pages: 1
                            }
                        }
                    },
                    settings: {
                        itemsPerPage: 10,
                        withRowNumbers: true
                    }
                }
            }
        },
        methods: {
            create() {
                this.clearForm();
                this.modal.visible = true;
            },

            clearForm() {
                this.form.mode = 'create';

                this.form.values = {
                    remote_setup_name: '',
                    remote_path: '',
                    remote_server: '',
                    remote_ip: '',
                    remote_port: '',
                    remote_user: '',
                    remote_password: '',
                    status: 'Active',
                }
            },

            deleteRow(index) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = 'Do you want to remove this data?';
                this.dialog.ok.function = () => {
                    this.table.values.data.splice(index, 1);
                    this.dialog.status = 'success';
                    this.dialog.message = 'Successfully removed the data!';
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                };
                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
            },

            save() {
                if (this.form.mode === 'create') {
                    this.table.values.data.push({
                        remote_setup_name: this.form.values.remote_setup_name,
                        remote_path: this.form.values.remote_path,
                        remote_server: this.form.values.remote_server,
                        remote_ip: this.form.values.remote_ip,
                        remote_port: this.form.values.remote_port,
                        remote_user: this.form.values.remote_user,
                        status: this.form.values.status
                    });

                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = 'Successfully added a new Remote Setup!';
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                        this.modal.visible = false;
                    };
                } else {
                    let index = this.form.values.index;

                    this.table.values.data[index] = {
                        remote_setup_name: this.form.values.remote_setup_name,
                        remote_path: this.form.values.remote_path,
                        remote_server: this.form.values.remote_server,
                        remote_ip: this.form.values.remote_ip,
                        remote_port: this.form.values.remote_port,
                        remote_user: this.form.values.remote_user,
                        status: this.form.values.status
                    }

                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = 'Successfully updated the Remote Setup!';
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                        this.modal.visible = false;
                    };
                }
            },

            openDetail(data, index) {
                this.form.mode = 'update';

                this.form.values = {
                    index: index,
                    remote_setup_name: data.remote_setup_name,
                    remote_path: data.remote_path,
                    remote_server: data.remote_server,
                    remote_ip: data.remote_ip,
                    remote_port: data.remote_port,
                    remote_user: data.remote_user,
                    remote_password: '',
                    status: data.status
                }

                this.modal.visible = true;
            }
        }
    }
</script>