<template>
    <div class="tab-pane fade show active" id="error-logs" role="tabpanel" aria-labelledby="error-logs-tab">
        <div class="box-row box-row--white d-flex justify-content-between position-relative">
            <div class="form-inline">
                <div class="form-group my-2 mx-3">
                    <h5>{{ $t('message.enter_email_address_to_receive_notification') }}</h5>
                    <input
                        type="text"
                        class="form-control ml-2 email-address-input"
                        placeholder="email.address@gmail.com"
                        v-model="form.email_address">
                    <button class="button button--light ml-2">{{ $t('label.set_email_address') }}</button>
                    <popper
                        class="ml-2"
                        trigger="click"
                        :options="{ placement: 'right' }">
                        <div class="popper popover--modified popover--modified-default popover--override">
                            <h6 align="center">
                                <b>{{ $t('label.email_address_for_notification') }}</b>
                            </h6>
                            <div class="text-left">
                                <p>
                                    <b class="text-uppercase">{{ $t('label.note') }}:</b>
                                    <span class="text-secondary">{{ $t('message.email_address_for_notification_note') }}</span>
                                </p>
                                <span class="text-secondary">{{ $t('message.make_sure_email_active_valid') }}</span>
                            </div>
                        </div>
                        <i slot="reference" class="fa fa-question-circle fa-lg"></i>
                    </popper>
                </div>
            </div>
        </div>
        <div class="box-row box-row--white p-3">
            <label>
                {{ $t('label.instructions') }}:<br>
                {{ $t('message.error_logs_instructions_1') }}<br>
                {{ $t('message.error_logs_instructions_2') }}<br>
                {{ $t('message.error_logs_instructions_3') }}
            </label>
        </div>
        <div class="box-row box-row--white pt-2">
            <div class="container-fluid">
                <div class="row">
                    <div class="form-group col-xl-3">
                        <label>{{ $t('label.date_from') }}:</label>
                        <date-picker
                            v-model="filters.date_from"
                            format="MMMM DD, YYYY"
                            value-type="format"
                        ></date-picker>
                    </div>
                    <div class="form-group col-xl-3">
                        <label>{{ $t('label.date_to') }}:</label>
                        <date-picker
                            v-model="filters.date_to"
                            format="MMMM DD, YYYY"
                            value-type="format"
                        ></date-picker>
                    </div>
                    <div class="form-group col-xl-3">
                        <label>{{ $t('label.status') }}:</label>
                        <select class="form-control" v-model="filters.status">
                            <option :value="1">{{ $t('label.resolved') }}</option>
                            <option :value="0">{{ $t('label.failed_conversion') }}</option>
                        </select>
                    </div>
                    <div class="form-group col-xl-3">
                        <div class="button--mt">
                            <button class="button button--light" @click="paginate">{{ $t('label.search') }}</button>
                            <button class="button button--light">{{ $t('label.download_selected_files') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <datatable
            :header-fields="table.header"
            :settings="table.settings"
            :table="table.values"
            v-on:paginate="paginate">
            <template slot="content">
                <table-row
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex">
                    <td class="datatable-cell" align="center">
                        <input type="checkbox" v-model="tableData.checked">
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableDataIndex + 1"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.pos_entry"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.csv_file"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.date_detected"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-if="tableData.status == 1">{{ $t('label.resolved') }}</span>
                        <span v-if="tableData.status == 0">{{ $t('label.failed_conversion') }}</span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <button class="button button--light w-100" @click="viewFileErrors(tableData.csv_file)">{{ $t('label.view_file_errors') }}</button>
                    </td>
                    <td class="datatable-cell" align="center">
                        <button class="button button--light" @click="downloadCSV">{{ $t('label.download_csv') }}</button>
                        <button class="button button--light" @click="uploadCSV">{{ $t('label.upload_csv') }}</button>
                    </td>
                </table-row>
            </template>
        </datatable>
        <modal
            class="modal--no-footer"
            width="630px"
            v-if="modal.file_errors.visible"
            @close="modal.file_errors.visible = false">
            <template slot="header">
                {{ $t('label.file_errors') }}
            </template>
            <template slot="content">
                <div class="form-inline">
                    <div class="form-group mb-4">
                        <label>{{ $t('label.csv_file') }}:</label>
                        <span class="ml-2" v-text="file_errors.values.csv_file"></span>
                    </div>
                </div>
                <table
                    border="1"
                    class="
                        table-design
                        table-design--default
                        w-100">
                    <thead>
                        <tr>
                            <td align="center" width="150px">{{ $t('label.sheet') }}</td>
                            <td align="center" width="150px">{{ $t('label.error_type') }}</td>
                            <td align="center" width="300px">{{ $t('label.description') }}</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(tableData, tableDataIndex) in file_errors.values.data"
                            :key="tableDataIndex">
                            <td valign="top">{{ tableData.sheet }}</td>
                            <td valign="top">{{ tableData.error_type }}</td>
                            <td valign="top">{{ tableData.description }}</td>
                        </tr>
                    </tbody>
                </table>
                
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
    import DialogBox from '../../../components/Message/DialogBox.vue';
    import Modal from '../../../components/Modal/Modal.vue';
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';

    export default {
        components: {
            Datatable,
            TableRow,
            DialogBox,
            Modal,
            Popper,
            DatePicker
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
                filters: {
                    date_from: '',
                    date_to: '',
                    status: 1,
                },
                form: {
                    email_address: ''
                },
                modal: {
                    file_errors: {
                        visible: false
                    }
                },
                file_errors: {
                    values: {
                        csv_file: '',
                        data: [
                            {
                                sheet: 'Transaction Head',
                                error_type: 'Wrong column name',
                                description: '[column 2, row 3] Column Name mismatch',
                            },
                            {
                                sheet: 'Transaction Head',
                                error_type: 'Missing column name',
                                description: '[missing column] transaction_type',
                            },
                            {
                                sheet: 'Transaction Head',
                                error_type: 'Wrong column name',
                                description: '[column 2, row 3] Column Name mismatch',
                            }
                        ]
                    }
                },
                table: {
                    header: [
                        {
                            name: "checked",
                            width: '30'
                        },
                        {
                            name: "row_index",
                            label: "#",
                            width: '40'
                        },
                        {
                            name: "pos_entry",
                            label: this.$t('label.pos_entry'),
                            width: '180'
                        },
                        {
                            name: "csv_file",
                            label: this.$t('label.csv_file'),
                            width: '200'
                        },
                        {
                            name: "date_detected",
                            label: this.$t('label.date_detected'),
                            width: '150'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
                            width: '130'
                        },
                        {
                            name: "view_file_errors",
                            label: this.$t('label.view_file_errors'),
                            width: '125'
                        },
                        {
                            name: "action",
                            label: this.$t('label.action'),
                            width: '120'
                        }
                    ],
                    values: {
                        data: [
                            {
                                checked: false,
                                pos_entry: 'Transaction',
                                csv_file: 'SL_10291010201LF.csv',
                                date_detected: 'May 1, 2021 05:00AM',
                                status: 1
                            },
                            {
                                checked: true,
                                pos_entry: 'Transaction',
                                csv_file: 'VL_10291010201LF.csv',
                                date_detected: 'May 1, 2021 05:00AM',
                                status: 0
                            },
                            {
                                checked: true,
                                pos_entry: 'Transaction',
                                csv_file: 'DATA_203D03032.csv',
                                date_detected: 'May 1, 2021 05:00AM',
                                status: 0
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
                        withRowNumbers: false,
                        withPagination: false
                    }
                }
            }
        },
        methods: {
            paginate(page = 1) {},

            viewFileErrors(file) {
                this.file_errors.values.csv_file = file;
                this.modal.file_errors.visible = true;
            },

            downloadCSV() {},

            uploadCSV() {},
        }
    }
</script>

<style lang="scss" scoped>
    .email-address-input {
        width: 300px;
    }
    .popover--override {
        width: 300px;
        padding: 15px 10px;
    }
</style>