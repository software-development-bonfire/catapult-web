<template>
    <div class="tab-pane fade show active" id="catapult-db-setup" role="tabpanel" aria-labelledby="catapult-db-setup-tab">
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
                        <span v-text="tableData.host"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.port"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.db_name"></span>
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
            @close="modal.visible = false">
            <template slot="header">
                {{ $t('label.catapult_db_setup_detail') }}
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>{{ $t('label.catapult_db_setup_name') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('name')">
                        {{errors.name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.host') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.host"
                    :class="{ 'is-invalid': errors.hasOwnProperty('host') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('host')">
                        {{errors.host[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.port') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.port"
                    :class="{ 'is-invalid': errors.hasOwnProperty('port') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('port')">
                        {{errors.port[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.db_name') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.db_name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('db_name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('db_name')">
                        {{errors.db_name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.db_username') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.username"
                    :class="{ 'is-invalid': errors.hasOwnProperty('username') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('username')">
                        {{errors.username[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.db_password') }} <span class="required">*</span></label>
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
                        host: '',
                        port: '',
                        db_name: '',
                        username: '',
                        password: '',
                        status: 1,
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: this.$t('label.catapult_db_setup_name'),
                            width: '250'
                        },
                        {
                            name: "host",
                            label: this.$t('label.host'),
                            width: '160'
                        },
                        {
                            name: "port",
                            label: this.$t('label.port'),
                            width: '90'
                        },
                        {
                            name: "db_name",
                            label: this.$t('label.db_name'),
                            width: '150'
                        },
                        {
                            name: "username",
                            label: this.$t('label.db_username'),
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
                                name: 'Catapult DB',
                                host: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                username: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                name: 'Catapult DB',
                                host: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                username: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                name: 'Catapult DB',
                                host: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                username: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                name: 'Catapult DB',
                                host: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                username: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                name: 'Catapult DB',
                                host: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                username: 'Catapult Admin',
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
                axios.get('catapult-db-setup'+'?page='+page, {
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
                    host: '',
                    port: '',
                    db_name: '',
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
                    axios.delete(`catapult-db-setup/${data.bid}`)
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
                    
                    axios.post('catapult-db-setup', this.form.values)
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

                    axios.put(`catapult-db-setup/${this.form.values.bid}`, this.form.values)
                    .then(response => {
                        this.table.values.data[index] = {
                            bid: this.form.values.bid,
                            name: this.form.values.name,
                            host: this.form.values.host,
                            port: this.form.values.port,
                            db_name: this.form.values.db_name,
                            username: this.form.values.username,
                            password: this.form.values.password,
                            status: this.form.values.status
                        }
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
                }
            },

            openDetail(data, index) {
                this.errors = {}
                this.form.mode = 'update';

                this.form.values = {
                    bid: data.bid,
                    index: index,
                    name: data.name,
                    host: data.host,
                    port: data.port,
                    db_name: data.db_name,
                    username: data.username,
                    password: data.password,
                    status: data.status
                }

                this.modal.visible = true;
            }
        }
    }
</script>