<template>
    <div class="module-container overflow-auto">
        <div class="box-row box-row--white p-1 d-flex justify-content-between align-content-center">
            <a href="#" class="back-to-list ml-2" @click="backToList">
                <i class="fa fa-arrow-circle-left fa-lg"></i>
                <span>{{ $t('label.back_to_list') }}</span>
            </a>
            <div>
                <button class="button button--light module-action-button" @click="saveConnection">{{ $t('label.save_connection') }}</button>
            </div>
        </div>
        <div class="box-row box-row--white">
            <div class="container-fluid">
                <div class="row my-2">
                    <div class="col-xl-12">
                        <h5 class="text-uppercase"><b>{{ $t('label.connection_setup') }}</b></h5>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-3">
                        <div class="mb-1">
                            <label>{{ $t('label.field_mapping_name') }}</label>
                            <input type="text" class="form-control" v-model="form.connection_setup.field_mapping_name">
                            <label
                                class="text-danger error-message mb-0">
                                Mapping is required.
                            </label>
                        </div>
                        <div class="mb-1">
                            <label>{{ $t('label.mapping_type') }}</label>
                            <select class="form-control" v-model="form.connection_setup.mapping_type">
                                <option :value="1">{{ $t('label.cdis_to_pos') }}</option>
                                <option :value="2">{{ $t('label.pos_to_cdis') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>{{ $t('label.setup_status') }}</label>
                            <select class="form-control" v-model="form.connection_setup.setup_status">
                                <option :value="1">{{ $t('label.active') }}</option>
                                <option :value="0">{{ $t('label.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="mb-1">
                            <label>{{ $t('label.remote_setup_name') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly v-model="form.connection_setup.remote_setup_name">
                                <div class="input-group-append" @click="openSearchModal('remote')">
                                    <span class="input-group-text search-button">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            <label
                                class="text-danger error-message mb-0">
                                Remote Setup Name is required.
                            </label>
                        </div>
                        <div class="mb-1">
                            <label>{{ $t('label.catapult_db_setup_name') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly v-model="form.connection_setup.catapult_db_setup_name">
                                <div class="input-group-append" @click="openSearchModal('catapult')">
                                    <span class="input-group-text search-button">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            <label
                                class="text-danger error-message mb-0">
                                Catapult DB Setup Name is required.
                            </label>
                        </div>
                        <div class="mb-3">
                            <label>{{ $t('label.api_setup_name') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly v-model="form.connection_setup.api_setup_name">
                                <div class="input-group-append" @click="openSearchModal('api')">
                                    <span class="input-group-text search-button">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            <label
                                class="text-danger error-message mb-0">
                                API Setup Name is required.
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-row box-row--white">
            <div class="container-fluid">
                <div class="row my-2">
                    <div class="col-xl-6">
                        <h5 class="text-uppercase"><b>{{ $t('label.data_mapping') }}</b></h5>
                    </div>
                    <div class="col-xl-6" align="right">
                        <button class="button button--light module-action-button" @click="saveMapping">{{ $t('label.save_mapping') }}</button>
                    </div>
                </div>
                <div class="row">
                    <table class="table-layout pull-left col-xl-4">
                        <tr>
                            <td valign="top" align="right">{{ $t('label.select_api_endpoint_to_map') }}</td>
                            <td width="200px">
                                <select class="form-control" v-model="form.data_mapping.api_endpoint">
                                    <template v-if="form.connection_setup.mapping_type === 1">
                                        <option :value="label.product">{{ $t('label.product') }}</option>
                                        <option :value="label.brand">{{ $t('label.brand') }}</option>
                                        <option :value="label.category">{{ $t('label.category') }}</option>
                                        <option :value="label.vendor">{{ $t('label.vendor') }}</option>
                                        <option :value="label.uom">{{ $t('label.uom') }}</option>
                                    </template>
                                    <template v-else>
                                        <option :value="label.transactions">{{ $t('label.transactions') }}</option>
                                        <option :value="label.zread">{{ $t('label.zread') }}</option>
                                        <option :value="label.audit_trail">{{ $t('label.audit_trail') }}</option>
                                        <option :value="label.cash_breakdown">{{ $t('label.cash_breakdown') }}</option>
                                        <option :value="label.cash_drawer">{{ $t('label.cash_drawer') }}</option>
                                    </template>
                                </select>
                                <label
                                    class="text-danger error-message mb-0">
                                    API Endpoint is required.
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="right">{{ $t('label.api_version') }}</td>
                            <td>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.data_mapping.api_version">
                                <label
                                    class="text-danger error-message mb-0">
                                    API Version is required.
                                </label>
                            </td>
                        </tr>
                    </table>
                    <ul class="unindented-list pull-left ml-4">
                        <li>
                            <i class="fa fa-check-square checkbox--required"></i>
                            <span class="ml-1">{{ $t('label.required_field') }}</span>
                        </li>
                        <li>
                            <i class="fa fa-check-square checkbox--not-required"></i>
                            <span class="ml-1">{{ $t('label.not_required_field') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <datatable
                class="
                    overflow-initial
                    datatable--overflow-initial
                    datatable--font-sm"
                :header-fields="form.connection_setup.mapping_type === 1 ? table.cdis_to_pos.header : table.pos_to_cdis.header"
                :settings="table.settings"
                :table="table.values">
                <template slot="content">
                    <table-row
                        class="datatable-row--sm"
                        v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                        :values="tableData"
                        :settings="table.settings"
                        :rowIndex="tableDataIndex">
                        <table-data
                            align="center"
                            valign="center">
                            <i
                                class="fa fa-check-square"
                                :class="tableData.required ? 'checkbox--required' : 'checkbox--not-required'">
                            </i>
                        </table-data>
                        <table-data
                            align="center"
                            valign="center">
                            <input type="text" class="form-control" v-model="tableData.field">
                        </table-data>
                        <table-data
                            align="center"
                            valign="center">
                            <popper
                                trigger="hover"
                                :options="{ placement: 'top' }">
                                <div class="popper popover--modified popover--modified-default">
                                    <span v-text="tableData.description"></span>
                                </div>
                                <i slot="reference" class="fa fa-question-circle fa-lg"></i>
                            </popper>
                        </table-data>
                        <table-data
                            align="center"
                            valign="center">
                            <input type="text" class="form-control" v-model="tableData.data_type">
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2">
                            <input type="text" class="form-control" v-model="tableData.csv_file_name_identifier">
                        </table-data>
                        <table-data
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2">
                            <span v-text="tableData.default_field_values"></span>
                        </table-data>
                        <table-data
                            align="center"
                            valign="center">
                            <template v-if="tableData.required">
                                <input type="text" class="form-control" v-model="tableData.csv_column_name">
                            </template>
                            <template v-else>
                                <span class="text-danger">{{ $t('message.default_values_will_be_used') }}</span>
                            </template>
                        </table-data>
                    </table-row>
                </template>
            </datatable>
        </div>
        <modal
            class="modal--no-footer"
            width="400px"
            v-if="modal.search.visible"
            @close="modal.search.visible = false">
            <template slot="header">
                {{ modal.search.title }}
            </template>
            <template slot="content">
                <table
                    class="
                        table-design
                        table-design--default
                        w-100">
                    <thead>
                        <tr>
                            <td align="center">#</td>
                            <td align="center">{{ $t('label.name') }}</td>
                            <td></td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, itemIndex) in selections.config.options"
                            :key="itemIndex"
                            :class="selections.config.selected === item.value ? 'tr--selected' : ''"
                            @click="selections.config.selected = item.value">
                            <td align="center" width="40px">{{ itemIndex + 1 }}</td>
                            <td>{{ item.label }}</td>
                            <td align="center" width="100px">
                                <button class="button button--light w-100" @click.stop="view(item)">{{ $t('label.view') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-2" align="center">
                    <button class="button button--light" @click="selectRow">{{ $t('label.select') }}</button>
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
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';
    import TableData from '../../components/Datatable2/TableData.vue';
    import Modal from '../../components/Modal/Modal.vue';
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';

    export default {
        components: {
            DialogBox,
            Datatable,
            TableRow,
            TableData,
            Modal,
            Popper
        },
        mounted() {
            let urlData = QueryString.parse(window.location.search.substr(1));
            console.log(urlData.data.name)
            if (urlData.data) {
                this.form.mode = 'update';
                this.form.connection_setup.field_mapping_name = urlData.data.name;
                this.form.connection_setup.mapping_type = Number(urlData.data.mapping_type);
                this.form.connection_setup.remote_setup_name = urlData.data.remote_setup_name;
                this.form.connection_setup.catapult_db_setup_name = urlData.data.catapult_db_setup_name;
                this.form.connection_setup.api_setup_name = urlData.data.api_setup_name;
                this.form.connection_setup.setup_status = Number(urlData.data.status);

                this.form.data_mapping.api_endpoint = urlData.data.api_to_map;
            }
        },
        data() {
            return {
                modal: {
                    search: {
                        title: '',
                        selection: '',
                        visible: false
                    }
                },
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
                form: {
                    mode: 'create',
                    connection_setup: {
                        field_mapping_name: '',
                        mapping_type: 2,
                        setup_status: 1,
                        remote_setup_name: '',
                        catapult_db_setup_name: '',
                        api_setup_name: '',
                    },
                    data_mapping: {
                        api_endpoint: '',
                        api_version: '',
                    }
                },
                errors: {},
                table: {
                    cdis_to_pos: {
                        header: [
                            {
                                name: "required",
                                label: '',
                                width: '50'
                            },
                            {
                                name: "fields",
                                label: this.$t('label.cdis_fields'),
                                width: '200'
                            },
                            {
                                name: "description",
                                width: '30'
                            },
                            {
                                name: "data_type",
                                label: this.$t('label.data_type'),
                                width: '120'
                            },
                            {
                                name: "csv_column_name",
                                label: this.$t('label.csv_column_name'),
                                width: '200'
                            }
                        ],
                    },
                    pos_to_cdis: {
                        header: [
                            {
                                name: "required",
                                width: '50'
                            },
                            {
                                name: "fields",
                                label: this.$t('label.cdis_fields'),
                                width: '200'
                            },
                            {
                                name: "description",
                                width: '30'
                            },
                            {
                                name: "data_type",
                                label: this.$t('label.data_type'),
                                width: '120'
                            },
                            {
                                name: "csv_file_name_identifier",
                                label: this.$t('label.csv_file_name_identifier'),
                                width: '200'
                            },
                            {
                                name: "default_field_values",
                                label: this.$t('label.default_field_values'),
                                width: '200'
                            },
                            {
                                name: "csv_column_name",
                                label: this.$t('label.csv_column_name'),
                                width: '200'
                            },
                        ],
                    },
                    values: {
                        data: [
                            {
                                required: true,
                                field: 'branch_code',
                                description: 'Code of your branch.',
                                data_type: 'VARCHAR',
                                csv_file_name_identifier: 'TR',
                                default_field_values: '0',
                                csv_column_name: 'BranchCode',
                            },
                            {
                                required: false,
                                field: 'terminal_number',
                                description: 'Your POS Terminal Number.',
                                data_type: 'BIGINT',
                                csv_file_name_identifier: 'TR',
                                default_field_values: '0',
                                csv_column_name: 'TerminalNumber',
                            },
                            {
                                required: true,
                                field: 'branch_code',
                                description: 'Code of your branch.',
                                data_type: 'INT',
                                csv_file_name_identifier: 'TR',
                                default_field_values: '0',
                                csv_column_name: 'BranchCode',
                            },
                            {
                                required: true,
                                field: 'branch_code',
                                description: 'Code of your branch.',
                                data_type: 'VARCHAR',
                                csv_file_name_identifier: 'TR',
                                default_field_values: '0',
                                csv_column_name: 'BranchCode',
                            },
                            {
                                required: false,
                                field: 'terminal_number',
                                description: 'Your POS Terminal Number.',
                                data_type: 'BIGINT',
                                csv_file_name_identifier: 'TR',
                                default_field_values: '0',
                                csv_column_name: 'TerminalNumber',
                            },
                            {
                                required: true,
                                field: 'branch_code',
                                description: 'Code of your branch.',
                                data_type: 'INT',
                                csv_file_name_identifier: 'TR',
                                default_field_values: '0',
                                csv_column_name: 'BranchCode',
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
                },
                label: {
                    product: this.$t('label.product'),
                    brand: this.$t('label.brand'),
                    category: this.$t('label.category'),
                    vendor: this.$t('label.vendor'),
                    uom: this.$t('label.uom'),
                    transactions: this.$t('label.transactions'),
                    zread: this.$t('label.zread'),
                    audit_trail: this.$t('label.audit_trail'),
                    cash_breakdown: this.$t('label.cash_breakdown'),
                    cash_drawer: this.$t('label.cash_drawer'),
                },
                selections: {
                    config: {
                        selected: '',
                        options: []
                    }
                }
            }
        },
        methods: {
            saveConnection() {
                if (this.form.mode === 'create') {
                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_added', { value: this.$t('label.connection_setup')});
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                    this.dialog.cancel.function = () => {
                        this.dialog.visible = false;
                    };
                } else {
                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.connection_setup')});
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                    this.dialog.cancel.function = () => {
                        this.dialog.visible = false;
                    };
                }
            },
            saveMapping() {
                if (this.form.mode === 'create') {
                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_added', { value: this.$t('label.data_mapping')});
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                    this.dialog.cancel.function = () => {
                        this.dialog.visible = false;
                    };
                } else {
                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.data_mapping')});
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                    this.dialog.cancel.function = () => {
                        this.dialog.visible = false;
                    };
                }
            },
            openSearchModal(selection) {
                this.selections.config.options = [];
                this.selections.config.selected = '';

                if (selection === 'remote') {
                    this.modal.search.title = this.$t('label.remote_setup_detail');

                    // Dummy options
                    this.selections.config.options = [
                        { label: 'Transaction', value: '1001' },
                        { label: 'Z Read', value: '1002' },
                        { label: 'Product', value: '1003' }
                    ];
                } else if (selection === 'catapult') {
                    this.modal.search.title = this.$t('label.catapult_db_setup_detail');

                    // Dummy options
                    this.selections.config.options = [
                        { label: 'Product', value: '1001' },
                        { label: 'POS', value: '1002' },
                        { label: 'Z Read', value: '1003' },
                    ];
                } else if (selection === 'api') {
                    this.modal.search.title = this.$t('label.api_setup_detail');

                    // Dummy options
                    this.selections.config.options = [
                        { label: 'API Transaction', value: '1001' },
                        { label: 'API POS', value: '1002' },
                        { label: 'API Z Read', value: '1003' },
                    ];
                }

                this.modal.search.selection = selection;
                this.modal.search.visible = true;
            },
            view() {
                window.open('/configurations?' + QueryString.stringify({
                    redirect: this.modal.search.selection
                }), '_blank');
            },
            selectRow() {
                let that = this;

                if (this.modal.search.selection === 'remote') {
                    this.selections.config.options.forEach(function(item) {
                        if (item.value === that.selections.config.selected) {
                            that.form.connection_setup.remote_setup_name = item.label;
                        }
                    });
                } else if (this.modal.search.selection === 'catapult') {
                    this.selections.config.options.forEach(function(item) {
                        if (item.value === that.selections.config.selected) {
                            that.form.connection_setup.catapult_db_setup_name = item.label;
                        }
                    });
                } else if (this.modal.search.selection === 'api') {
                    this.selections.config.options.forEach(function(item) {
                        if (item.value === that.selections.config.selected) {
                            that.form.connection_setup.api_setup_name = item.label;
                        }
                    });
                }

                this.modal.search.visible = false;
            },
            backToList() {
                this.dialog.visible = true;
                this.dialog.status = 'confirm-yes-no';
                this.dialog.message = this.$t('message.are_you_sure_you_want_to_leave_the_page');
                this.dialog.ok.function = () => {
                    this.dialog.visible = false;
                    window.open('/field-mapping', '_self');
                };
                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
            },
            getError(object, name) {
                let error = object[name];
                return error ? error[0] : '';
                
            },
            removeError(object, name) {
                delete object[name];
            },
        }
    }
</script>

<style lang="scss" scoped>
    .back-to-list {
        color: #212529;
        text-decoration: none;
    }
    .search-button {
        &:hover {
            cursor: pointer;
            i {
                color: lighten(#212529, 40%);
            }
        }
    }
    .input-group {
        .form-control:read-only {
            background-color: #fff !important;
        }
    }
    .checkbox {
        &--required {
            color: #2A68F8;
        }
        &--not-required {
            color: #242424;
        }
    }
</style>
