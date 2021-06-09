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
            :table="table.values">
            <template slot="content">
                <table-row
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:row-click="openDetail(tableData, tableDataIndex)">
                    <td class="datatable-cell">
                        <span v-text="tableData.catapult_db_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.ip_address"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.port"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.db_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.db_user"></span>
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
                Catapult DB Setup Detail
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>Catapult DB Setup Name <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.catapult_db_setup_name">
                    <label class="text-danger error-message m-0">
                        Catapult DB Setup is required.
                    </label>
                </div>
                <div class="form-group">
                    <label>IP Address <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.ip_address">
                    <label class="text-danger error-message m-0">
                        IP Address is required.
                    </label>
                </div>
                <div class="form-group">
                    <label>Port <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.port">
                    <label class="text-danger error-message m-0">
                        Port is required.
                    </label>
                </div>
                <div class="form-group">
                    <label>DB Name <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.db_name">
                    <label class="text-danger error-message m-0">
                        DB Name is required.
                    </label>
                </div>
                <div class="form-group">
                    <label>DB User <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.db_user">
                    <label class="text-danger error-message m-0">
                        DB User is required.
                    </label>
                </div>
                <div class="form-group">
                    <label>DB Password <span class="required">*</span></label>
                    <input type="password" class="form-control" v-model="form.values.db_password">
                    <label class="text-danger error-message m-0">
                        DB Password is required.
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
                        catapult_db_setup_name: '',
                        ip_address: '',
                        port: '',
                        db_name: '',
                        db_user: '',
                        db_password: '',
                        status: 'Active',
                    }
                },
                table: {
                    header: [
                        {
                            name: "catapult_db_setup_name",
                            label: "Catapult DB Setup Name",
                            width: '250'
                        },
                        {
                            name: "ip_address",
                            label: 'IP Address',
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
                            name: "db_user",
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
                                catapult_db_setup_name: 'Catapult DB',
                                ip_address: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                db_user: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                catapult_db_setup_name: 'Catapult DB',
                                ip_address: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                db_user: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                catapult_db_setup_name: 'Catapult DB',
                                ip_address: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                db_user: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                catapult_db_setup_name: 'Catapult DB',
                                ip_address: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                db_user: 'Catapult Admin',
                                status: 'Active',
                            },
                            {
                                catapult_db_setup_name: 'Catapult DB',
                                ip_address: '192.168.5.334',
                                port: '80',
                                db_name: 'db_catapult',
                                db_user: 'Catapult Admin',
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
                    catapult_db_setup_name: '',
                    ip_address: '',
                    port: '',
                    db_name: '',
                    db_user: '',
                    db_password: '',
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
                        catapult_db_setup_name: this.form.values.catapult_db_setup_name,
                        ip_address: this.form.values.ip_address,
                        port: this.form.values.port,
                        db_name: this.form.values.db_name,
                        db_user: this.form.values.db_user,
                        status: this.form.values.status
                    });

                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = 'Successfully added a new Catapult DB Setup!';
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                        this.modal.visible = false;
                    };
                } else {
                    let index = this.form.values.index;

                    this.table.values.data[index] = {
                        catapult_db_setup_name: this.form.values.catapult_db_setup_name,
                        ip_address: this.form.values.ip_address,
                        port: this.form.values.port,
                        db_name: this.form.values.db_name,
                        db_user: this.form.values.db_user,
                        status: this.form.values.status
                    }

                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = 'Successfully updated the Catapult DB Setup!';
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
                    catapult_db_setup_name: data.catapult_db_setup_name,
                    ip_address: data.ip_address,
                    port: data.port,
                    db_name: data.db_name,
                    db_user: data.db_user,
                    db_password: '',
                    status: data.status
                }

                this.modal.visible = true;
            }
        }
    }
</script>