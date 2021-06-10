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
            :table="table.values"
            v-on:paginate="paginate">
            <template slot="content">
                <table-row
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:row-click="openDetail(tableData, tableDataIndex)">
                    <td class="datatable-cell">
                        <span v-text="tableData.name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.path"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.server"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.host"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.port"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.username"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.status"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <i class="fa fa-times-circle fa-lg row-delete" @click.stop="deleteRow(tableDataIndex, tableData)"></i>
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
                    <label>Remote Setup Name <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('name')">
                        {{errors.name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Remote Path <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.path"
                    :class="{ 'is-invalid': errors.hasOwnProperty('path') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('path')">
                        {{errors.path[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Remote Server <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.server"
                    :class="{ 'is-invalid': errors.hasOwnProperty('server') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('server')">
                        {{errors.server[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Remote Host <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.host"
                    :class="{ 'is-invalid': errors.hasOwnProperty('host') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('host')">
                        {{errors.host[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Remote Port <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.port"
                    :class="{ 'is-invalid': errors.hasOwnProperty('port') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('port')">
                        {{errors.port[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Remote User <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.username"
                    :class="{ 'is-invalid': errors.hasOwnProperty('username') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('username')">
                        {{errors.username[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Remote Password <span class="required">*</span></label>
                    <input type="password" class="form-control" v-model="form.values.password"
                    :class="{ 'is-invalid': errors.hasOwnProperty('password') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('password')">
                        {{errors.password[0]}}
                    </label>
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
        mounted() {
            this.paginate()
        },
        mixins: [ Util ],
        data() {
            return {
                errors: {},
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
                        id: '',
                        name: '',
                        path: '',
                        server: '',
                        host: '',
                        port: '',
                        username: '',
                        password: '',
                        status: 'Active',
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: "Remote Setup Name",
                            width: '200'
                        },
                        {
                            name: "path",
                            label: 'Remote Path',
                            width: '250'
                        },
                        {
                            name: "server",
                            label: 'Remote Server',
                            width: '150'
                        },
                        {
                            name: "host",
                            label: 'Remote Host',
                            width: '150'
                        },
                        {
                            name: "port",
                            label: 'Remote Port',
                            width: '150'
                        },
                        {
                            name: "username",
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
                                name: 'Transactions',
                                path: 'ftp://pathto POS Transactions',
                                server: 'Bonfire 1',
                                host: '192.168.2.92',
                                port: '92',
                                username: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
                                path: 'ftp://pathto POS Transactions',
                                server: 'Bonfire 1',
                                host: '192.168.2.92',
                                port: '92',
                                username: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
                                path: 'ftp://pathto POS Transactions',
                                server: 'Bonfire 1',
                                host: '192.168.2.92',
                                port: '92',
                                username: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
                                path: 'ftp://pathto POS Transactions',
                                server: 'Bonfire 1',
                                host: '192.168.2.92',
                                port: '92',
                                username: 'Admin Bonfire 1',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
                                path: 'ftp://pathto POS Transactions',
                                server: 'Bonfire 1',
                                host: '192.168.2.92',
                                port: '92',
                                username: 'Admin Bonfire 1',
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
            paginate(page = 1) {
                axios.get('remote-setup'+'?page='+page, {
                    params: {
                        itemsPerPage: this.table.settings.itemsPerPage,
                    }
                })
                .then(response => {
                   this.table.values.data = response.data.data.data
                   this.table.values.meta  = response.data.data.meta
                })
            },
            create() {
                this.clearForm();
                this.modal.visible = true;
            },

            clearForm() {
                this.errors = {}
                this.form.mode = 'create';

                this.form.values = {
                    name: '',
                    path: '',
                    server: '',
                    host: '',
                    port: '',
                    username: '',
                    password: '',
                    status: 'Active',
                }
            },

            deleteRow(index, data) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = 'Do you want to remove this data?';
                this.dialog.ok.function = () => {
                    axios.delete(`remote-setup/${data.id}`)
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
                    
                    axios.post('remote-setup', this.form.values)
                    .then(response => {
                        this.table.values.data.push({
                            name: this.form.values.name,
                            path: this.form.values.path,
                            server: this.form.values.server,
                            host: this.form.values.host,
                            port: this.form.values.port,
                            username: this.form.values.username,
                            status: this.form.values.status
                        });

                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = 'Successfully added a new Remote Setup!';
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            this.modal.visible = false;
                        };
                        this.errors = {}
                    }).catch(error => {
                        this.errors = error.response.data.errors
                    })

                } else {
                    let index = this.form.values.index;
                    
                    axios.put(`remote-setup/${this.form.values.id}`, this.form.values)
                    .then(response => {
                        this.table.values.data[index] = {
                            name: this.form.values.name,
                            path: this.form.values.path,
                            server: this.form.values.server,
                            host: this.form.values.host,
                            port: this.form.values.port,
                            username: this.form.values.username,
                            status: this.form.values.status
                        }

                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = 'Successfully updated the Remote Setup!';
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            this.modal.visible = false;
                        };
                        this.errors = {}
                    }).catch(error => {
                        this.errors = error.response.data.errors
                    })
                }
            },

            openDetail(data, index) {
                this.errors = {}
                this.form.mode = 'update';

                this.form.values = {
                    id: data.id,
                    index: index,
                    name: data.name,
                    path: data.path,
                    server: data.server,
                    host: data.host,
                    port: data.port,
                    username: data.username,
                    password: '',
                    status: data.status
                }

                this.modal.visible = true;
            }
        }
    }
</script>