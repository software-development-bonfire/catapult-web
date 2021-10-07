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
                        <span v-text="tableData.storage_type_label"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.local_path"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.remote_path"></span>
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
                    <label>{{ $t('label.storage_type') }}</label>
                    <select
                        class="form-control"
                        v-model="form.values.storage_type"
                        @change="
                            form.values.storage_type_label =
                                $event.target.value == 0
                                    ? $t('label.local_network')
                                    : $t('label.ftp')
                        ">
                        <option :value="0">{{ $t('label.local_network') }}</option>
                        <option :value="1">{{ $t('label.ftp') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.file_storage_setup_name') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('name')">
                        {{errors.name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.remote_path') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.remote_path"
                    :class="{ 'is-invalid': errors.hasOwnProperty('remote_path') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('remote_path')">
                        {{errors.remote_path[0]}}
                    </label>
                </div>
                <div class="form-group" v-if="form.values.storage_type == 1">
                    <label>{{ $t('label.remote_server') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.server"
                    :class="{ 'is-invalid': errors.hasOwnProperty('server') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('server')">
                        {{errors.server[0]}}
                    </label>
                </div>
                <div class="form-group" v-if="form.values.storage_type == 1">
                    <label>{{ $t('label.remote_host') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.host"
                    :class="{ 'is-invalid': errors.hasOwnProperty('host') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('host')">
                        {{errors.host[0]}}
                    </label>
                </div>
                <div class="form-group" v-if="form.values.storage_type == 1">
                    <label>{{ $t('label.remote_port') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.port"
                    :class="{ 'is-invalid': errors.hasOwnProperty('port') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('port')">
                        {{errors.port[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ form.values.storage_type == 0 ? $t('label.username') : $t('label.remote_username') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.username"
                    :class="{ 'is-invalid': errors.hasOwnProperty('username') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('username')">
                        {{errors.username[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ form.values.storage_type == 0 ? $t('label.password') : $t('label.remote_password') }} <span class="required">*</span></label>
                    <input type="password" class="form-control" v-model="form.values.password"
                    :class="{ 'is-invalid': errors.hasOwnProperty('password') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('password')">
                        {{errors.password[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.local_path') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.local_path"
                           :class="{ 'is-invalid': errors.hasOwnProperty('local_path') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('local_path')">
                        {{errors.local_path[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.setup_status') }}</label>
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
                    index: null,
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
                            label: this.$t('label.file_storage_setup_name'),
                            width: '200'
                        },
                        {
                            name: "storage_type_label",
                            label: this.$t('label.storage_type'),
                            width: '150'
                        },
                        {
                            name: "local_path",
                            label: this.$t('label.local_path'),
                            width: '250'
                        },
                        {
                            name: "remote_path",
                            label: this.$t('label.remote_path'),
                            width: '250'
                        },
                        {
                            name: "server",
                            label: this.$t('label.server'),
                            width: '150'
                        },
                        {
                            name: "host",
                            label: this.$t('label.host'),
                            width: '150'
                        },
                        {
                            name: "port",
                            label: this.$t('label.port'),
                            width: '150'
                        },
                        {
                            name: "username",
                            label: this.$t('label.username'),
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
                let that = this;

                if (this.form.mode === 'create') {
                    axios.post('remote-setup', this.form.values)
                        .then(response => {
                            that.paginate();
                            that.dialog.visible = true;
                            that.dialog.status = 'success';
                            that.dialog.message = response.data.message;
                            that.dialog.ok.function = () => {
                                that.dialog.visible = false;
                                that.modal.visible = false;
                            };
                            that.errors = {}
                        }).catch(error => {
                            that.errors = error.response.data.errors
                        });
                } else {
                    let index = this.form.index;

                    axios.patch(`remote-setup/${this.form.values.bid}`, this.form.values)
                        .then(response => {
                            that.table.values.data[index] = {...that.form.values};

                            that.dialog.visible = true;
                            that.dialog.status = 'success';
                            that.dialog.message = response.data.message;
                            that.dialog.ok.function = () => {
                                that.dialog.visible = false;
                                that.modal.visible = false;
                            };
                            that.errors = {}
                        }).catch(error => {
                            that.errors = error.response.data.errors
                        });
                }
            },

            openDetail(data, index) {
                this.errors = {}
                this.form.mode = 'update';

                this.form.values = {...data};
                this.form.index = index;

                this.modal.visible = true;
            },

            closeDetail() {
                this.modal.visible = false;

            }
        }
    }
</script>
