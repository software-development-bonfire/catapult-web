<template>
    <div class="tab-pane fade show active" id="remote-setup" role="tabpanel" aria-labelledby="remote-setup-tab">
        <div class="m-1">
            <button class="button button--dark" @click="create">{{ $t('label.add_new') }}</button>
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
                        <span v-if="tableData.status == 1">{{ $t('label.active') }}</span>
                        <span v-if="tableData.status == 0">{{ $t('label.inactive') }}</span>
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
            @close="closeDetail">
            <template slot="header">
                {{ $t('label.remote_setup_detail') }}
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>{{ $t('label.remote_setup_name') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('name')">
                        {{errors.name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.remote_path') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.path"
                    :class="{ 'is-invalid': errors.hasOwnProperty('path') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('path')">
                        {{errors.path[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.remote_server') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.server"
                    :class="{ 'is-invalid': errors.hasOwnProperty('server') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('server')">
                        {{errors.server[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.remote_host') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.host"
                    :class="{ 'is-invalid': errors.hasOwnProperty('host') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('host')">
                        {{errors.host[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.remote_port') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.port"
                    :class="{ 'is-invalid': errors.hasOwnProperty('port') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('port')">
                        {{errors.port[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.remote_username') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.username"
                    :class="{ 'is-invalid': errors.hasOwnProperty('username') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('username')">
                        {{errors.username[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.remote_password') }} <span class="required">*</span></label>
                    <input type="password" class="form-control" v-model="form.values.password"
                    :class="{ 'is-invalid': errors.hasOwnProperty('password') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('password')">
                        {{errors.password[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.status') }}</label>
                    <select class="form-control" v-model="form.values.status">
                        <option :value="1">{{ $t('label.active') }}</option>
                        <option :value="0">{{ $t('label.inactive') }}</option>
                    </select>
                </div>
            </template>
            <template slot="footer">
                <div align="center">
                    <button class="button button--light" @click="save">{{ $t('label.save') }}</button>
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
        mounted() {
            this.paginate();
        },
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
                        status: 1,
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: this.$t('label.remote_setup_name'),
                            width: '200'
                        },
                        {
                            name: "path",
                            label: this.$t('label.remote_path'),
                            width: '250'
                        },
                        {
                            name: "server",
                            label: this.$t('label.remote_server'),
                            width: '150'
                        },
                        {
                            name: "host",
                            label: this.$t('label.remote_host'),
                            width: '150'
                        },
                        {
                            name: "port",
                            label: this.$t('label.remote_port'),
                            width: '150'
                        },
                        {
                            name: "username",
                            label: this.$t('label.remote_username'),
                            width: '150'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
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
                    status: 1,
                }
            },

            deleteRow(index, data) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = 'Do you want to remove this data?';
                this.dialog.ok.function = () => {
                    axios.delete(`remote-setup/${data.bid}`)
                    .then(response => {
                        this.table.values.data.splice(index, 1);
                        this.dialog.status = 'success';
                        this.dialog.message = response.data.message;
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
                    })
                };
                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
            },

            save() {
                if (this.form.mode === 'create') {

                    axios.post('remote-setup', this.form.values)
                    .then(response => {
                        this.paginate();
                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = response.data.message;
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

                    axios.put(`remote-setup/${this.form.values.bid}`, this.form.values)
                    .then(response => {
                        this.table.values.data[index] = {
                            bid: this.form.values.bid,
                            name: this.form.values.name,
                            path: this.form.values.path,
                            server: this.form.values.server,
                            host: this.form.values.host,
                            port: this.form.values.port,
                            username: this.form.values.username,
                            password: this.form.values.password,
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
                    bid: data.bid,
                    index: index,
                    name: data.name,
                    path: data.path,
                    server: data.server,
                    host: data.host,
                    port: data.port,
                    username: data.username,
                    password: data.password,
                    status: data.status
                }

                this.modal.visible = true;
            },

            closeDetail() {
                this.modal.visible = false;

            }
        }
    }
</script>