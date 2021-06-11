<template>
    <div class="tab-pane fade" id="catapult-db-setup" role="tabpanel" aria-labelledby="catapult-db-setup-tab">
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
                Catapult DB Setup Detail
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>Catapult DB Setup Name <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('name')">
                        {{errors.name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Host <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.host"
                    :class="{ 'is-invalid': errors.hasOwnProperty('host') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('host')">
                        {{errors.host[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>Port <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.port"
                    :class="{ 'is-invalid': errors.hasOwnProperty('port') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('port')">
                        {{errors.port[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>DB Name <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.db_name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('db_name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('db_name')">
                        {{errors.db_name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>DB User <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.username"
                    :class="{ 'is-invalid': errors.hasOwnProperty('username') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('username')">
                        {{errors.username[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>DB Password <span class="required">*</span></label>
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
                        host: '',
                        port: '',
                        db_name: '',
                        username: '',
                        password: '',
                        status: 'Active',
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: "Catapult DB Setup Name",
                            width: '250'
                        },
                        {
                            name: "host",
                            label: 'Host',
                            width: '160'
                        },
                        {
                            name: "port",
                            label: 'Port',
                            width: '90'
                        },
                        {
                            name: "db_name",
                            label: 'DB Name',
                            width: '150'
                        },
                        {
                            name: "username",
                            label: 'DB User',
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
                    status: 'Active',
                }
            },

            deleteRow(index, data) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = 'Do you want to remove this data?';
                this.dialog.ok.function = () => {
                    axios.delete(`catapult-db-setup/${data.id}`)
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
                    
                    axios.post('catapult-db-setup', this.form.values)
                    .then(response => {
                        this.table.values.data.push({
                            id: response.data.data.id,
                            name: this.form.values.name,
                            host: this.form.values.host,
                            port: this.form.values.port,
                            db_name: this.form.values.db_name,
                            username: this.form.values.username,
                            status: this.form.values.status,
                            password: this.form.values.password
                        });

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

                    axios.put(`catapult-db-setup/${this.form.values.id}`, this.form.values)
                    .then(response => {
                        this.table.values.data[index] = {
                            id: this.form.values.id,
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
                    id: data.id,
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