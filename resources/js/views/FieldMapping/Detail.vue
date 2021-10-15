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
                                class="text-danger error-message mb-0" v-if="errors.hasOwnProperty('name')">
                                {{ errors.name[0] }}
                            </label>
                        </div>
                        <div class="mb-1">
                            <label>{{ $t('label.mapping_type') }}</label>
                            <select
                                :disabled="form.mode === 'update'"
                                class="form-control"
                                v-model="form.connection_setup.mapping_type"
                                v-on:change="getSyncEntryChosen"
                            >
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
                            <label>{{ $t('label.file_storage_setup_name') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly v-model="form.connection_setup.file_storage_setup_name">
                                <div class="input-group-append" @click="openSearchModal('file_storage')">
                                    <span class="input-group-text search-button">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            <label
                                class="text-danger error-message mb-0" v-if="errors.hasOwnProperty('file_storage_setup_bid')">
                                {{ errors.file_storage_setup_bid[0] }}
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
                                class="text-danger error-message mb-0"  v-if="errors.hasOwnProperty('catapult_db_setup_bid')">
                                {{ errors.catapult_db_setup_bid[0] }}
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
                                class="text-danger error-message mb-0"  v-if="errors.hasOwnProperty('api_setup_bid')">
                                {{ errors.api_setup_bid[0] }}
                            </label>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="mb-1" v-if="form.connection_setup.mapping_type == 1">
                            <label>{{ $t('label.customized_mapping') }}</label>
                            <select

                                :disabled="form.mode === 'update'"
                                class="form-control"
                                v-model="form.connection_setup.is_customized_mapping"
                            >
                                <option :value="1">{{ $t('label.yes') }}</option>
                                <option :value="0">{{ $t('label.no') }}</option>
                            </select>
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
                        <button class="button button--light module-action-button" :disabled="this.form.mode === 'create'" @click="saveMapping">{{ $t('label.save_mapping') }}</button>
                    </div>
                </div>
                <div class="row">
                    <table class="table-layout pull-left col-xl-4">
                        <tr v-if="form.connection_setup.is_customized_mapping === 0">
                            <td valign="top" align="right">{{ $t('label.select_entry_to_map') }}</td>
                            <td width="200px">
                                <select
                                    class="form-control"
                                    v-model="form.data_mapping.data_entry"
                                    @change="getDataEntries()">
                                    <option
                                        v-for="(item, index) in selections.sync_entry.options"
                                        :value="item.value">
                                        {{ item.label }}
                                    </option>
                                </select>
                                <label
                                    class="text-danger error-message mb-0" v-if="errors.hasOwnProperty('data_entry')">
                                    {{ errors.data_entry[0] }}
                                </label>
                            </td>
                        </tr>
                        <tr v-if="form.connection_setup.is_customized_mapping === 0">
                            <td valign="top" align="right">{{ $t('label.preset_name') }}</td>
                            <td>
                                <select
                                    @change="getFields()"
                                    type="text"
                                    class="form-control"
                                    v-model="form.data_mapping.field_mapping_preset_bid">
                                    <option v-for="(preset, index) in presets" :key="index"
                                    :value="preset.bid">
                                        {{ preset.preset_name }}
                                    </option>
                                </select>
                                <label
                                    class="text-danger error-message mb-0" v-if="errors.hasOwnProperty('preset_name')">
                                    {{ errors.preset_name[0] }}
                                </label>
                            </td>
                        </tr>
                        <tr v-if="form.connection_setup.is_customized_mapping === 1">
                            <td align="right">{{ $t('message.set_the_file_name_to_be_generated') }}</td>
                            <td width="200px">
                                <input type="text" class="form-control" v-model="form.data_mapping.data_entry">
                                <label
                                    class="text-danger error-message mb-0" v-if="errors.hasOwnProperty('data_entry')">
                                    {{ errors.data_entry[0] }}
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
                :header-fields="
                    form.connection_setup.mapping_type === 2
                        ? table.pos_to_cdis.header
                        : (form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 1)
                            ? table.cdis_to_pos_customized.header
                            : table.cdis_to_pos.header"
                :settings="table.settings"
                :table="table.values"
                v-on:add-row="addRow"
                v-on:delete-row="deleteRow"
            >
                <template slot="content">
                    <table-row
                        type="edit"
                        class="datatable-row--sm"
                        v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                        :values="tableData"
                        :settings="table.settings"
                        :rowIndex="tableDataIndex"
                        v-on:enable-row="tableData.edit = $event.state">
                        <table-data
                            align="center"
                            valign="center">
                            <input
                                type="checkbox"
                                v-model="tableData.required"
                            >
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 1">
                            <input
                                type="radio"
                                v-bind:value="tableData.bid"
                                v-model="form.data_mapping.primary_key"
                            >
                        </table-data>
                        <table-data
                            align="center"
                            valign="center">
                            <input
                                :disabled="! tableData.required"
                                type="text"
                                class="form-control"
                                v-model="tableData.field"
                            >
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2 || (form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 0)">
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
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2 || (form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 0)">
                            <input
                                :disabled="! tableData.required"
                                type="text"
                                class="form-control"
                                v-model="tableData.mapping_type"
                            >
                        </table-data>
                        <table-data
                            :error="getError(errors, `fields.${tableDataIndex}.file_name`)"
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2">
                            <input type="text" class="form-control" v-model="tableData.file_name"
                            v-on:input="removeError(errors, `fields.${tableDataIndex}.file_name`)">
                        </table-data>
                        <table-data
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2 || (form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 1)">
                            <input type="text" class="form-control" v-model="tableData.default_value">
                        </table-data>
                        <table-data
                            :error="getError(errors, `fields.${tableDataIndex}.column_name`)"
                            align="center"
                            valign="center">
                            <template v-if="(form.connection_setup.mapping_type === 2 && tableData.required) || form.connection_setup.mapping_type === 1">
                                <input type="text" class="form-control" v-model="tableData.column_name"
                                v-on:input="removeError(errors, `fields.${tableDataIndex}.column_name`)">
                            </template>
                            <template v-else>
                                <span class="text-danger">{{ $t('message.default_values_will_be_used') }}</span>
                            </template>
                        </table-data>
                        <table-data
                            :error="getError(errors, `fields.${tableDataIndex}.reference_column_name`)"
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2"
                        >
                            <input type="text" class="form-control" v-model="tableData.reference_column_name"
                                   v-on:input="removeError(errors, `fields.${tableDataIndex}.reference_column_name`)">
                        </table-data>
                        <table-data
                            :error="getError(errors, `fields.${tableDataIndex}.head_reference`)"
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2"
                        >
                            <input type="text" class="form-control" v-model="tableData.head_reference"
                                   v-on:input="removeError(errors, `fields.${tableDataIndex}.head_reference`)">
                        </table-data>
                    </table-row>

                    <table-row
                        class="datatable-row--sm"
                        type="add"
                        :values="form.data_mapping.add"
                        :settings="table.settings">
                        <table-data
                            align="center"
                            valign="center">
                            <input
                                type="checkbox"
                                v-model="form.data_mapping.add.required"
                            >
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 1">
                        </table-data>
                        <table-data
                            align="center"
                            valign="center">
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.data_mapping.add.field"
                            >
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2 || (form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 0)">
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2 || (form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 0)">
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.data_mapping.add.mapping_type">
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2">
                            <input type="text" class="form-control" v-model="form.data_mapping.add.file_name">
                        </table-data>
                        <table-data
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2 || (form.connection_setup.mapping_type === 1 && form.connection_setup.is_customized_mapping === 1)">
                            <input type="text" class="form-control" v-model="form.data_mapping.add.default_value">
                        </table-data>
                        <table-data
                            align="center"
                            valign="center">
                            <template>
                                <input type="text" class="form-control" v-model="form.data_mapping.add.column_name">
                            </template>
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2">
                            <input type="text" class="form-control" v-model="form.data_mapping.add.reference_column_name">
                        </table-data>
                        <table-data
                            align="center"
                            valign="center"
                            v-if="form.connection_setup.mapping_type === 2">
                            <input type="text" class="form-control" v-model="form.data_mapping.add.head_reference">
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
    var config = window.location.origin;
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';
    import TableData from '../../components/Datatable2/TableData.vue';
    import Modal from '../../components/Modal/Modal.vue';
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';

    export default {
        props: [
            'detail'
        ],
        components: {
            DialogBox,
            Datatable,
            TableRow,
            TableData,
            Modal,
            Popper
        },
        mounted() {
            let searchParams = QueryString.parse(window.location.search.substr(1));

            if (searchParams.bid) {
                this.form.mode = 'update';

                this.field_mapping_bid = searchParams.bid;
                this.form.connection_setup = this.detail;
                this.form.data_mapping.data_entry = this.detail.data_entry;
                this.form.data_mapping.primary_key = this.detail.primary_key;

                this.getDataEntries();

                if (this.detail.detail.length > 0) {
                    this.form.mapping_mode = 'update';
                }

                this.table.values.data = this.detail.detail;
            }

            this.getSyncEntryChosen();
        },
        data() {
            return {
                field_mapping_bid: null,
                presets: [],
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
                    mapping_mode: 'create',
                    connection_setup: {
                        field_mapping_name: '',
                        mapping_type: 1,
                        setup_status: 1,
                        file_storage_setup_name: '',
                        file_storage_setup_bid: '',
                        catapult_db_setup_name: '',
                        catapult_db_setup_bid: '',
                        api_setup_bid: '',
                        is_customized_mapping: 0,
                    },
                    data_mapping: {
                        data_entry: '',
                        field_mapping_preset_bid: '',
                        primary_key: '',
                        add: {
                            edit: false,
                            required: false,
                            field: '',
                            description: '',
                            mapping_type: 'DECIMAL',
                            file_name: '',
                            default_value: '',
                            column_name: '',
                            head_reference: '',
                        },
                    },
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
                    cdis_to_pos_customized: {
                        header: [
                            {
                                name: "required",
                                label: '',
                                width: '50'
                            },
                            {
                                name: "primary_key",
                                label: this.$t('label.primary_key'),
                                width: '100'
                            },
                            {
                                name: "fields",
                                label: this.$t('label.cdis_fields'),
                                width: '300'
                            },
                            {
                                name: "default_field_values",
                                label: this.$t('label.default_field_values'),
                                width: '250'
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
                                width: '320'
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
                                width: '150'
                            },
                            {
                                name: "default_field_values",
                                label: this.$t('label.default_field_values'),
                                width: '120'
                            },
                            {
                                name: "csv_column_name",
                                label: this.$t('label.csv_column_name'),
                                width: '200'
                            },
                            {
                                name: "reference_column_name",
                                label: this.$t('label.reference_column_name'),
                                width: '200'
                            },
                            {
                                name: "head_reference",
                                label: this.$t('label.head_reference'),
                                width: '320'
                            },
                        ],
                    },
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
                        withRowNumbers: false,
                        withPagination: false,
                        minHeight: 300,
                        hasEdit: true,
                        hasDelete: true,
                    }
                },
                label: {
                    brand: this.$t('label.brand'),
                    branch: this.$t('label.branch'),
                    vendor: this.$t('label.vendor'),
                    vendor_branch: this.$t('label.vendor_branch'),
                    unit_of_measurement: this.$t('label.unit_of_measurement'),
                    transaction: this.$t('label.transaction'),
                    zread: this.$t('label.zread'),
                    audit_trail: this.$t('label.audit_trail'),
                    cash_breakdown: this.$t('label.cash_breakdown'),
                    cash_drawer: this.$t('label.cash_drawer'),
                    product: this.$t('label.product'),
                    product_uom_packaging: this.$t('label.product_uom_packaging'),
                    product_branch_availability: this.$t('label.product_branch_availability'),
                    product_branch_price: this.$t('label.product_branch_price'),
                    packaging_vendor: this.$t('label.packaging_vendor'),
                    packaging_vendor_branch_cost: this.$t('label.packaging_vendor_branch_cost'),
                    product_structure: this.$t('label.product_structure'),
                    product_structure_detail: this.$t('label.product_structure_detail'),
                    product_category: this.$t('label.product_category'),
                    product_pricing_type: this.$t('label.product_pricing_type'),
                },
                selections: {
                    config: {
                        selected: '',
                        options: []
                    },
                    sync_entry: {
                        options: []
                    }
                }
            }
        },
        methods: {
            getDataEntries() {
                let that = this;
                this.form.data_mapping.field_mapping_preset_bid = '';
                this.errors = {};

                axios.get(`${config}/field-mapping/detail/get-data-entries`+'?page=1', {
                    params: {
                        itemsPerPage: 100,
                        filters: {
                            data_entry: this.form.data_mapping.data_entry,
                            type: this.form.connection_setup.mapping_type,
                        }
                    }
                }).then(response => {
                    that.presets = response.data.data.data
                })
            },
            getFields() {
                this.errors = {};
                var selectedPreset = this.presets.find(element => (element.bid === this.form.data_mapping.field_mapping_preset_bid));

                if (selectedPreset.details.length > 0) {
                    this.table.values.data = selectedPreset.details;
                } else {
                    this.table.values.data = [];
                }
            },
            saveConnection() {
                if (this.form.mode === 'create') {
                    var payload = {
                        name: this.form.connection_setup.field_mapping_name,
                        type: this.form.connection_setup.mapping_type,
                        status: this.form.connection_setup.setup_status,
                        file_storage_setup_bid: this.form.connection_setup.file_storage_setup_bid,
                        catapult_db_setup_bid: this.form.connection_setup.catapult_db_setup_bid,
                        api_setup_bid: this.form.connection_setup.api_setup_bid,
                    };

                    axios.post(`${config}/field-mapping/store`, payload)
                        .then(response => {
                            this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_added', { value: this.$t('label.connection_setup')});
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                            };
                            this.dialog.cancel.function = () => {
                                this.dialog.visible = false;
                            };

                            this.field_mapping_bid = response.data.data.bid
                            this.form.mode = "update"
                            this.errors = {}
                        }).catch(error => {
                            this.errors = error.response.data.errors;
                        })
                } else {
                    var payload = {
                        bid: this.field_mapping_bid,
                        name: this.form.connection_setup.field_mapping_name,
                        type: this.form.connection_setup.mapping_type,
                        status: this.form.connection_setup.setup_status,
                        file_storage_setup_bid: this.form.connection_setup.file_storage_setup_bid,
                        catapult_db_setup_bid: this.form.connection_setup.catapult_db_setup_bid,
                        api_setup_bid: this.form.connection_setup.api_setup_bid,
                        data_entry: this.form.data_mapping.data_entry
                    }

                    axios.patch(`${config}/field-mapping/update/${this.field_mapping_bid}`, payload)
                        .then(response => {
                            this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.connection_setup')});
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                            };
                            this.dialog.cancel.function = () => {
                                this.dialog.visible = false;
                            };

                            this.errors = [];
                        }).catch(error => {
                            this.errors = error.response.data.errors;
                        })
                }
            },
            saveMapping() {
                if (this.form.mapping_mode === 'create') {
                    var preset = this.presets.find(element => (element.bid === this.form.data_mapping.field_mapping_preset_bid))

                    var payload = {
                        mapping_type: this.form.connection_setup.mapping_type,
                        field_mapping_bid: this.field_mapping_bid,
                        data_entry: this.form.data_mapping.data_entry,
                        is_customized_mapping: this.form.connection_setup.is_customized_mapping,
                        primary_key: this.form.data_mapping.primary_key,
                        fields: this.table.values.data
                    };

                    axios.post(`${config}/field-mapping/detail/store`, payload)
                        .then(response => {
                            this.form.mapping_mode = 'update';
                            this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_added', { value: this.$t('label.data_mapping')});
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                                window.open('/field-mapping', '_self');
                            };
                            this.dialog.cancel.function = () => {
                                this.dialog.visible = false;
                            };
                        }).catch(error => {
                            this.errors = error.response.data.errors;
                        })
                } else {
                    var preset = this.presets.find(element => (element.bid == this.form.data_mapping.field_mapping_preset_bid));

                    var payload = {
                        mapping_type: this.form.connection_setup.mapping_type,
                        field_mapping_bid: this.field_mapping_bid,
                        data_entry: this.form.data_mapping.data_entry,
                        is_customized_mapping: this.form.connection_setup.is_customized_mapping,
                        primary_key: this.form.data_mapping.primary_key,
                        fields: this.table.values.data
                    };

                    axios.patch(`${config}/field-mapping/detail/update/${this.field_mapping_bid}`, payload)
                        .then(response => {
                            this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.data_mapping')});
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                                window.open('/field-mapping', '_self');
                            };
                            this.dialog.cancel.function = () => {
                                this.dialog.visible = false;
                            };
                        }).catch(error => {
                            this.errors = error.response.data.errors;
                        })
                }
            },
            openSearchModal(selection) {
                this.selections.config.options = [];
                this.selections.config.selected = '';

                axios.get(`${config}/field-mapping/detail/get-list`, { 
                    params: {
                        type: selection,
                        itemsPerPage: 1000,
                    } 
                })
                    .then(response => {
                        if (selection === 'file_storage') {
                            this.modal.search.title = this.$t('label.file_storage_setup_detail');
                        } else if (selection === 'catapult') {
                            this.modal.search.title = this.$t('label.catapult_db_setup_detail');
                        } else if (selection === 'api') {
                            this.modal.search.title = this.$t('label.api_setup_detail');
                        }
                        this.selections.config.options = response.data.data.data.map(element => {
                            return {
                                label: element.name,
                                value: element.bid
                            }
                        })
                    })
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

                if (this.modal.search.selection === 'file_storage') {
                    this.selections.config.options.forEach(function(item) {
                        if (item.value === that.selections.config.selected) {
                            that.form.connection_setup.file_storage_setup_name = item.label;
                            that.form.connection_setup.file_storage_setup_bid = item.value;
                        }
                    });
                } else if (this.modal.search.selection === 'catapult') {
                    this.selections.config.options.forEach(function(item) {
                        if (item.value === that.selections.config.selected) {
                            that.form.connection_setup.catapult_db_setup_name = item.label;
                            that.form.connection_setup.catapult_db_setup_bid = item.value;
                        }
                    });
                } else if (this.modal.search.selection === 'api') {
                    this.selections.config.options.forEach(function(item) {
                        if (item.value === that.selections.config.selected) {
                            that.form.connection_setup.api_setup_name = item.label;
                            that.form.connection_setup.api_setup_bid = item.value;
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
            async getSyncEntryChosen() {
                let that = this;

                await axios.get('/sync-entry/chosen', {
                    params: {
                        filters: {
                            type: this.form.connection_setup.mapping_type
                        }
                    }
                }).then(function(response) {
                    that.$set(that.selections.sync_entry, 'options', response.data.data);
                });
            },
            getError(object, name) {
                let error = object[name];
                return error ? error[0] : '';
                
            },
            removeError(object, name) {
                delete object[name];
            },
            addRow(data) {
                this.table.values.data.push({...data.values});
            },
            deleteRow(data) {
                this.table.values.data.splice(data.rowIndex, 1);
            },
        },
        watch: {
            'form.connection_setup.mapping_type': function(value) {
                if (value === 2) {
                    this.form.connection_setup.is_customized_mapping = 0;
                }
            },
            'form.connection_setup.is_customized_mapping': function(value) {
                this.form.data_mapping.field_mapping_preset_bid = '';
            }
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
