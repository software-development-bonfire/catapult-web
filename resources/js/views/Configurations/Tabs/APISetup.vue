<template>
    <div class="tab-pane fade" id="api-setup" role="tabpanel" aria-labelledby="api-setup-tab">
        <div class="m-1">
            <button class="button button--dark" @click="create">Add New</button>
        </div>
        <datatable
            class="datatable--hoverable"
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
                        <span v-text="tableData.api_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.end_point"></span>
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
            centered-display
            v-if="modal.visible"
            @close="modal.visible = false">
            <template slot="header">
                API Setup Detail
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>API Setup Name</label>
                    <input type="text" class="form-control" v-model="form.values.api_setup_name">
                </div>
                <div class="form-group">
                    <label>End Point</label>
                    <input type="text" class="form-control" v-model="form.values.end_point">
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
                        api_setup_name: '',
                        end_point: '',
                        status: 'Active',
                    }
                },
                table: {
                    header: [
                        {
                            name: "api_setup_name",
                            label: "API Setup Name",
                            width: '200'
                        },
                        {
                            name: "end_point",
                            label: 'End Point',
                            width: '200'
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
                                api_setup_name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                api_setup_name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                api_setup_name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                api_setup_name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                api_setup_name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
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
                    api_setup_name: '',
                    end_point: '',
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
                        api_setup_name: this.form.api_setup_name,
                        end_point: this.form.end_point,
                        status: this.form.status
                    });

                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = 'Successfully added a new API Setup!';
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                        this.modal.visible = false;
                    };
                } else {
                    let index = this.form.values.index;

                    this.table.values.data[index] = {
                        api_setup_name: this.form.values.api_setup_name,
                        end_point: this.form.values.end_point,
                        status: this.form.values.status
                    }

                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = 'Successfully updated the API Setup!';
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
                    api_setup_name: data.api_setup_name,
                    end_point: data.end_point,
                    status: data.status
                }

                this.modal.visible = true;
            }
        }
    }
</script>