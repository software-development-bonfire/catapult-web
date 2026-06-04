<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            You only allowed to update printer name, you cannot add new kitchen
            <button class="button button--primary module-action-button" @click="create">{{ $t('label.add_new') }}</button>
        </div>
        <datatable
            class="datatable--hoverable"
            :header-fields="table.header"
            :settings="table.settings"
            :table="table.values"
            v-on:paginate="paginate">
            <template slot="content">
                <table-row
                    type="custom-actions"
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:dbl-row-click="editRow(tableDataIndex, tableData)">
                   
                    <td class="datatable-cell">
                        <span v-text="tableData.code"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.description"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.device_printer"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.printer_host"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.local_printer"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span
                            class="status_label"
                            :class="tableData.status ? 'status_label--active' : 'status_label--inactive'"
                            v-text="tableData.status ? $t('label.active') : $t('label.inactive')">
                        </span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <i class="fa fa-print fa-lg row-print ml-1" @click.stop="printTest(tableDataIndex, tableData)" title="Print Test"></i>
                        <i class="fa fa-edit fa-lg row-update ml-1" @click.stop="editRow(tableDataIndex, tableData)"></i>
                    </td>
                </table-row>
            </template>
        </datatable>
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
        <modal
            class="modal--no-footer"
            width="500px"
            v-if="modal.detail.visible"
            @close="modal.detail.visible = false">
            <template slot="header">
                {{ $t('label.kitchen_device_printer') }}
            </template>
            <template slot="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="form-group">
                                <label>{{ $t('label.code') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.values.code"
                                    disabled>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="form-group">
                                <label>{{ $t('label.status') }}</label>
                                <select class="form-control" v-model="form.values.status">
                                    <option :value="1">{{ $t('label.active') }}</option>
                                    <option :value="0">{{ $t('label.inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-12">
                            <form-field
                                class="form-group">
                                <label>{{ $t('label.local_printer_configuration') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.hasOwnProperty('local_printer') && form.errors.local_printer !== '' }"
                                    @keypress="form.errors.local_printer = ''"
                                    v-model="form.values.local_printer">
                                <label class="text-danger error-message m-0" v-if="form.errors.hasOwnProperty('local_printer')">
                                    {{form.errors.local_printer[0]}}
                                </label>
                            </form-field>
                        </div>
                    </div>
                </div>
                <div align="center">
                    <button class="button button--light" @click="update">{{ $t('label.save') }}</button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
    import DatePicker from 'vue2-datepicker';
import 'vue2-datepicker/index.css';
import FormField from '../../components/Containers/FormField.vue';
import Datatable from '../../components/Datatable2/Datatable.vue';
import TableRow from '../../components/Datatable2/TableRow.vue';
import DialogBox from '../../components/Message/DialogBox.vue';
import Modal from '../../components/Modal/Modal.vue';

    export default {
        components: {
            DialogBox,
            Datatable,
            TableRow,
            Modal,
            DatePicker,
            FormField
        },
        mounted() {
            this.paginate();
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
                modal: {
                    detail: {
                        visible: false
                    }
                },
                filters: {
                    mapping_type: '',
                    status: ''
                },
                form: {
                    mode: 'create',
                    values: {
                        code: '',
                        local_printer: '',
                        status: 1,
                    },
                    errors: {
                        code: '',
                        description: '',
                    }
                },
                table: {
                    header: [                       
                        {
                            name: "code",
                            label: this.$t('label.code'),
                            width: '100'
                        },
                        {
                            name: "description",
                            label: this.$t('label.description'),
                            width: '200'
                        },
                        {
                            name: "device_printer",
                            label: this.$t('label.device_printer'),
                            width: '150'
                        },
                        {
                            name: "printer_host",
                            label: this.$t('label.printer_host'),
                            width: '200'
                        },
                        {
                            name: "local_printer",
                            label: this.$t('label.local_printer_configuration'),
                            width: '220'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
                            width: '90'
                        },
                        {
                            name: "actions",
                            label: this.$t('label.action'),
                            width: '90'
                        }
                    ],
                    values: {
                        data: [],
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
                        withRowNumbers: true,
                        hasDelete: false
                    }
                }
            }
        },
        methods: {
            paginate(page = 1) {
                this.$root.processing(true);
                axios.get('kitchen-printer'+'?page='+page, {
                    params: {
                        itemsPerPage: this.table.settings.itemsPerPage
                    }
                })
                .then(response => {
                    console.log(response);
                    this.table.values.data = response.data.data.data;
                    this.table.values.meta  = response.data.data.meta;
                    this.$root.processing(false);
                })
            },

            create() {
                this.clearFields();
                this.modal.detail.visible = true;
            },

            clearFields() {
                this.form.errors = {}
                this.form.mode = 'create';
                this.form.values = {
                    id: '',
                    code: '',
                    local_printer: '',
                    status: 1,
                };
            },

            editRow(index, data) {
                this.form.mode = 'update';
                this.form.errors = {}
                
                this.form.values = {
                    mode: 'update',
                    index: index,
                    bid: data.bid,
                    code: data.code,
                    local_printer: data.local_printer,
                    status: data.status,
                };

                this.modal.detail.visible = true;
            },

            update() {                
                var config = {
                    mode: this.form.mode,
                    bid: this.form.values.bid,
                    code: this.form.values.code,
                    status: this.form.values.status,
                    local_printer: this.form.values.local_printer,
                }

                axios.put('kitchen-printer/'+this.form.values.bid, config)
                    .then(response => {
                        this.paginate()
                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.kitchen_device_printer') });
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            this.modal.detail.visible = false;
                        };

                    }).catch(error => {
                        this.form.errors = error.response.data.errors;
                    })
            },

            printTest(index, data) {
                this.$root.processing(true);
                axios.post('kitchen-printer/' + data.bid + '/print-test')
                    .then(response => {
                        this.$root.processing(false);
                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_updated', { value: 'Test print sent to ' + data.code });
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
                    }).catch(error => {
                        this.$root.processing(false);
                        this.dialog.visible = true;
                        this.dialog.status = 'error';
                        this.dialog.message = error.response?.data?.message || 'Failed to send test print';
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
                    })
            },
        }
    }
</script>

<style lang="scss" scoped>
</style>
