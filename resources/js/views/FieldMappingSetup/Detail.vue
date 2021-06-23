<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1 d-flex justify-content-between align-content-center">
            <a href="/field-mapping-setup" class="back-to-list ml-2">
                <i class="fa fa-arrow-circle-left fa-lg"></i>
                <span>{{ $t('label.back_to_list') }}</span>
            </a>
            <div>
                <button class="button button--light module-action-button" v-if="mode === 'create' && form.mode !== 'update'" @click="setMapping">{{ $t('label.set_mapping') }}</button>
                <button class="button button--light module-action-button" :disabled="table.values.data.length === 0" @click="save">{{ $t('label.save') }}</button>
            </div>
        </div>
        <div class="container-fluid">
            <table class="table-layout pull-left col-xl-4">
                <tr>
                    <td valign="top" align="right">{{ $t('label.mapping_type') }}</td>
                    <td width="200px">
                        <select :disabled="mode === 'update' || form.mode === 'update'" class="form-control" v-model="form.values.mapping_type" @change="getPreset()">
                            <option :value="1">{{ $t('label.cdis_to_pos') }}</option>
                            <option :value="2">{{ $t('label.pos_to_cdis') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td valign="top" align="right">{{ $t('label.select_api_endpoint_to_map') }}</td>
                    <td>
                        <select class="form-control" v-model="form.values.api_endpoint">
                            <template v-if="form.values.mapping_type === 1">
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
                            class="text-danger error-message mb-0"
                            v-if="errors.add.hasOwnProperty('api_endpoint')">
                            {{errors.add.api_endpoint[0]}}
                        </label>
                    </td>
                </tr>
            </table>
            <table class="table-layout pull-left col-xl-4">
                <tr>
                    <td valign="top" align="right">{{ $t('label.api_version_name') }}</td>
                    <td>
                        <input
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.error.hasOwnProperty('api_version_name') }"
                            v-model="form.values.api_version_name">
                        <label
                            class="text-danger error-message mb-0"
                            v-if="errors.add.hasOwnProperty('api_version_name')">
                            {{errors.add.api_version_name[0]}}
                        </label>
                    </td>
                </tr>
                <tr v-if="this.form.values.bid || bid">
                    <td valign="top" align="right">{{ $t('label.copy_preset_from') }}</td>
                    <td>
                        <select class="form-control" v-model="form.values.copy_preset_from">
                            <option value=""></option>
                            <option :value="preset.bid" v-for="(preset, index) in presets" :key="index">{{preset.api_version_name}}</option>
                        </select>
                    </td>
                    <td>
                        <button class="button button--light" @click="copyPreset()">{{ $t('label.load') }}</button>
                    </td>
                </tr>
            </table>
            <table class="table-layout pull-left col-xl-3">
                <tr>
                    <td valign="top" align="right">{{ $t('label.version_status') }}</td>
                    <td>
                        <select class="form-control" v-model="form.values.version_status">
                            <option :value="1">{{ $t('label.active') }}</option>
                            <option :value="0">{{ $t('label.inactive') }}</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <label
            class="text-danger error-message mb-0"
            v-if="errors.add.hasOwnProperty('details')">
            {{errors.add.details[0]}}
        </label>
        <datatable
            v-if="bid || form.mode === 'update'"
            class="
                datatable--full-width
                datatable--font-sm"
            :header-fields="form.values.mapping_type === 1 ? table.cdis_to_pos.header : table.pos_to_cdis.header"
            :settings="table.settings"
            :table="table.values"
            v-on:add-row="addRow"
            v-on:update-row="updateRow"
            v-on:delete-row="deleteRow($event)">
            <template slot="content">
                <table-row
                    class="datatable-row--sm"
                    type="view"
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:enable-row="tableData.edit = $event.state">
                    <table-data
                        align="center"
                        valign="center">
                        <input type="checkbox" v-model="tableData.required" :disabled="! tableData.edit">
                    </table-data>
                    <table-data
                        valign="center"
                        :error="getError(errors.add, `details.${tableDataIndex}.field`) == false ? tableData.error : getError(errors.add, `details.${tableDataIndex}.field`)">
                        <template v-if="tableData.edit">
                            <input type="text" class="form-control" v-model="tableData.field"
                            v-on:input="removeError(errors.add, `fetailes.${tableDataIndex}.field`)">
                        </template>
                        <template v-else>
                            <span v-text="tableData.field"></span>
                        </template>
                    </table-data>
                    <table-data
                        valign="center">
                        <template v-if="tableData.edit">
                            <input type="text" class="form-control" v-model="tableData.description">
                        </template>
                        <template v-else>
                            <span v-text="tableData.description"></span>
                        </template>
                    </table-data>
                    <table-data
                        valign="center">
                        <template v-if="tableData.edit">
                            <select class="form-control" v-model="tableData.mapping_type">
                                <option value="DECIMAL">DECIMAL</option>
                                <option value="BIGINT">BIGINT</option>
                                <option value="TINYINT">TINYINT</option>
                                <option value="INT">INT</option>
                                <option value="VARCHAR">VARCHAR</option>
                                <option value="DATETIME">DATETIME</option>
                                <option value="DATE">DATE</option>
                                <option value="TIME">TIME</option>
                                <option value="TEXT">TEXT</option>
                            </select>
                        </template>
                        <template v-else>
                            <span v-text="tableData.mapping_type"></span>
                        </template>
                    </table-data>
                    <table-data
                        v-if="form.values.mapping_type === 2"
                        valign="center">
                        <input type="text" class="form-control" v-model="tableData.file_name" disabled>
                    </table-data>
                    <table-data
                        v-if="form.values.mapping_type === 2"
                        valign="center">
                        <template v-if="tableData.edit">
                            <input :type="defaultTypeUpdate(tableData.mapping_type)" class="form-control" v-model="tableData.default_value">
                        </template>
                        <template v-else>
                            <span v-text="tableData.default_value"></span>
                        </template>
                    </table-data>
                    <table-data
                        valign="center">
                        <input type="text" class="form-control" v-model="tableData.column_name" disabled>
                    </table-data>
                </table-row>
                <table-row
                    class="datatable-row--sm"
                    type="add"
                    :values="table.add"
                    :settings="table.settings">
                    <table-data
                        align="center"
                        valign="center">
                        <input type="checkbox" v-model="table.add.required">
                    </table-data>
                    <table-data
                        :error="errors.add.field">
                        <input
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.add.field != '' }"
                            v-model="table.add.field"
                            @keypress="errors.add.field = ''">
                    </table-data>
                    <table-data>
                        <input
                            type="text"
                            class="form-control"
                            v-model="table.add.description">
                    </table-data>
                    <table-data>
                        <select
                            class="form-control"
                            v-model="table.add.mapping_type">
                            <option value="DECIMAL">DECIMAL</option>
                            <option value="BIGINT">BIGINT</option>
                            <option value="TINYINT">TINYINT</option>
                            <option value="INT">INT</option>
                            <option value="VARCHAR">VARCHAR</option>
                            <option value="DATETIME">DATETIME</option>
                            <option value="DATE">DATE</option>
                            <option value="TIME">TIME</option>
                            <option value="TEXT">TEXT</option>
                        </select>
                    </table-data>
                    <table-data
                        v-if="form.values.mapping_type === 2">
                        <input
                            type="text"
                            class="form-control"
                            v-model="table.add.file_name"
                            disabled>
                    </table-data>
                    <table-data
                        v-if="form.values.mapping_type === 2">
                        <input
                            v-if="table.add.mapping_type == 'INT'
                            || table.add.mapping_type == 'TINYINT'
                            || table.add.mapping_type == 'BIGINT'"
                            step="1"
                            :type="defaultType()"
                            class="form-control"
                            v-model="table.add.default_value">
                        <input
                            v-if="table.add.mapping_type == 'DECIMAL'"
                            pattern="^\d*(\.\d{0,2})?$"
                            step="0.01"
                            :type="defaultType()"
                            class="form-control"
                            v-model="table.add.default_value">
                        <input
                            v-if="table.add.mapping_type == 'VARCHAR'
                            || table.add.mapping_type == 'DATETIME'
                            || table.add.mapping_type == 'DATE'
                            || table.add.mapping_type == 'TIME'
                            || table.add.mapping_type == 'TEXT'"
                            pattern="^\d*(\.\d{0,2})?$"
                            step="0.01"
                            :type="defaultType()"
                            class="form-control"
                            v-model="table.add.default_value">
                    </table-data>
                    <table-data>
                        <input
                            type="text"
                            class="form-control"
                            v-model="table.add.column_name"
                            disabled>
                    </table-data>
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
    </div>
</template>

<script>
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';
    import TableData from '../../components/Datatable2/TableData.vue';

    export default {
        components: {
            DialogBox,
            Datatable,
            TableRow,
            TableData
        },
        mounted() {
            let urlData = QueryString.parse(window.location.search.substr(1));
            if (urlData.data) {
                this.form.mode = 'update';
                this.form.values.bid = Number(urlData.data.bid);
                this.form.values.mapping_type = Number(urlData.data.mapping_type);
                this.form.values.api_endpoint = String(urlData.data.api_endpoint);
                this.form.values.api_version_name = urlData.data.api_version_name;
                this.form.values.version_status = Number(urlData.data.status);
                if (urlData.data.details !== undefined) {
                    this.table.values.data = urlData.data.details.map(element => {
                        return {
                            edit: false,
                            id: element.id,
                            bid: element.bid,
                            column_name: element.column_name,
                            default_value: element.default_value,
                            description: element.description,
                            field: element.field,
                            field_mapping_bid: element.field_mapping_bid,
                            file_name: element.file_name,
                            required: element.required === "true" ? true : false,
                            mapping_type: element.mapping_type
                        }
                    });
                }
            }
            this.getPreset();
        },
        data() {
            return {
                mode: 'create',
                bid: null,
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
                    values: {
                        mapping_type: 1,
                        api_endpoint: '',
                        api_version_name: '',
                        copy_preset_from: '',
                        version_status: 1
                    }
                },
                errors: {
                    add: {
                        field: ''
                    },
                    error: {}
                },
                table: {
                    cdis_to_pos: {
                        header: [
                            {
                                name: "required",
                                label: this.$t('label.set_as_required'),
                                width: '90'
                            },
                            {
                                name: "field",
                                label: this.$t('label.cdis_fields'),
                                width: '90'
                            },
                            {
                                name: "description",
                                label: this.$t('label.add_tooltip_description'),
                                width: '200'
                            },
                            {
                                name: "data_type",
                                label: this.$t('label.data_type'),
                                width: '100'
                            },
                            {
                                name: "csv_column_name",
                                label: this.$t('label.csv_column_name'),
                                width: '90'
                            }
                        ],
                    },
                    pos_to_cdis: {
                        header: [
                            {
                                name: "required",
                                label: this.$t('label.set_as_required'),
                                width: '90'
                            },
                            {
                                name: "field",
                                label: this.$t('label.cdis_fields'),
                                width: '70'
                            },
                            {
                                name: "description",
                                label: this.$t('label.add_tooltip_description'),
                                width: '170'
                            },
                            {
                                name: "data_type",
                                label: this.$t('label.data_type'),
                                width: '80'
                            },
                            {
                                name: "csv_file_name_identifier",
                                label: this.$t('label.csv_file_name_identifier'),
                                width: '100'
                            },
                            {
                                name: "default_value",
                                label: this.$t('label.default_value'),
                                width: '100'
                            },
                            {
                                name: "csv_column_name",
                                label: this.$t('label.csv_column_name'),
                                width: '90'
                            }
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
                    add: {
                        edit: false,
                        required: false,
                        field: '',
                        description: '',
                        mapping_type: 'DECIMAL',
                        file_name: '',
                        default_value: '',
                        column_name: '',
                    },
                    settings: {
                        itemsPerPage: 10,
                        withRowNumbers: false,
                        hasEdit: true,
                        hasDelete: true,
                        withPagination: false
                    }
                },
                presets: [],
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
                }
            }
        },
        methods: {
            getPreset() {

                axios.get('/field-mapping-setup/index'+'?page='+1, {
                    params: {
                        mapping_type: this.form.values.mapping_type,
                        status: 1,
                        itemsPerPage: 100
                    }
                })
                .then(response => {
                    this.presets = response.data.data.data
                })
            },
            copyPreset() {
                var bid = this.form.values.copy_preset_from;
                var preset = this.presets.find(element => element.bid === bid);
                var presets = preset.details;
                if (this.form.values.copy_preset_from) {
                    this.dialog.visible = true;
                    this.dialog.status = 'confirm-yes-no';
                    this.dialog.message = this.$t('message.are_you_sure_you_want_to_overwrite_the_table_with_the_selected_preset');
                    this.dialog.ok.function = () => {
                        if (this.form.mode === 'create') {
                            this.table.values.data = [];
                            presets.forEach(element => {
                                this.table.values.data.push({
                                    edit: false,
                                    bid: element.bid,
                                    field_mapping_bid: this.form.mode === 'create' ? this.bid : this.form.values.bid,
                                    required: element.required,
                                    field: element.field,
                                    description: element.description,
                                    mapping_type: element.mapping_type,
                                    file_name: element.file_name,
                                    default_value: element.default_value,
                                    column_name: element.column_name,
                                });
                            })
                        }
                        if (this.form.mode === 'update') {
                            var config = {
                                bid: this.form.values.bid,
                                details: presets
                            }

                            axios.post('/field-mapping-setup/detail/preset', config)
                            .then(response => {
                                this.table.values.data= [];
                                response.data.data.forEach(element => {
                                    this.table.values.data.push({
                                        edit: false,
                                        bid: element.bid,
                                        field_mapping_bid: element.field_mapping_bid,
                                        required: element.required,
                                        field: element.field,
                                        description: element.description,
                                        mapping_type: element.mapping_type,
                                        file_name: element.file_name,
                                        default_value: element.default_value,
                                        column_name: element.column_name,
                                    })
                                })

                            })
                        }
                        this.dialog.visible = false;
                    };

                    this.dialog.cancel.function = () => {
                        this.dialog.visible = false;
                    };
                }
            },
            setMapping() {
                var config = {
                    method: 'create',
                    type: this.form.values.mapping_type,
                    api_endpoint: this.form.values.api_endpoint,
                    api_version_name: this.form.values.api_version_name,
                    status: this.form.values.version_status,
                }

                axios.post('/field-mapping-setup/detail', config)
                .then(response => {
                    this.bid = response.data.data.bid;
                    this.errors.add = {};
                    this.errors.add.field = '';
                    this.mode = "update";
                }).catch(error => {
                    this.errors.add = error.response.data.errors;
                    this.errors.add.field = '';
                })
            },
            save() {
                if (this.form.mode === 'create') {
                    var config = {
                        bid: this.bid,
                        method: 'create',
                        type: this.form.values.mapping_type,
                        api_endpoint: this.form.values.api_endpoint,
                        api_version_name: this.form.values.api_version_name,
                        status: this.form.values.version_status,
                        details: this.table.values.data
                    }

                    axios.post('/field-mapping-setup/details', config)
                    .then(response => {
                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_created', { value: this.$t('label.field_mapping_setup') });
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            window.open('/field-mapping-setup', '_self');
                        };
                        this.errors.add = {};
                        this.errors.add.field = '';
                    }).catch(error => {
                        this.errors.add = error.response.data.errors;
                        if (this.errors.add.hasOwnProperty('details')) {
                            this.dialog.visible = true;
                            this.dialog.status = 'error';
                            this.dialog.message = this.$t('validation.field_mapping_cdis_required');
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                            };
                        }
                        this.errors.add.field = '';
                    })

                } else {
                    var config = {
                        method: 'update',
                        bid: this.form.values.bid,
                        type: this.form.values.mapping_type,
                        api_endpoint: this.form.values.api_endpoint,
                        api_version_name: this.form.values.api_version_name,
                        status: this.form.values.version_status,
                    }
                    
                    axios.put(`/field-mapping-setup/detail/${this.form.values.bid}`, config)
                    .then(response => {
                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.field_mapping_setup') });
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            window.open('/field-mapping-setup', '_self');
                        };
                        this.errors.add.field = '';
                    }).catch(error => {
                        this.errors.add = error.response.data.errors;
                    })
                }
            },

            clearFields() {
                this.table.add = {
                    edit: false,
                    required: false,
                    field: '',
                    description: '',
                    mapping_type: 'INT',
                    file_name: '',
                    default_value: '',
                    column_name: '',
                };
                this.errors.add.field = '';
            },

            addRow() {
                if (this.form.mode === 'create') {
                    var exist = this.table.values.data.some(element => element.field == this.table.add.field.toLowerCase())
                    
                    if (this.table.add.field && !exist) {
                        this.table.values.data.push({
                            edit: false,
                            field_mapping_bid: this.bid,
                            required: this.table.add.required ? 1 : 0,
                            field: this.table.add.field.toLowerCase(),
                            description: this.table.add.description,
                            mapping_type: this.table.add.mapping_type,
                            file_name: this.table.add.file_name,
                            default_value: this.table.add.default_value === "" ? '""' : this.defaultValue(this.table.add.default_value, this.table.add.mapping_type),
                            column_name: this.table.add.column_name,
                        });
                        
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_added_the_data');
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
        
                        this.clearFields();
                        this.errors.error = {}
                    } else {
                        if (exist) {
                            this.errors.error = {};
                            this.errors.add.field = this.$t('error.cdis_field_unique');
                        } else {
                            this.errors.error = {};
                            this.errors.add.field = this.$t('error.field_is_required');
                        }
                    }
                } else {
                    var data = {
                        field_mapping_bid: this.form.values.bid,
                        required: this.table.add.required ? 1 : 0,
                        field: this.table.add.field,
                        description: this.table.add.description,
                        mapping_type: this.table.add.mapping_type,
                        file_name: this.table.add.file_name,
                        default_value: this.table.add.default_value === "" ? '""' : this.defaultValue(this.table.add.default_value, this.table.add.mapping_type),
                        column_name: this.table.add.column_name,
                    }

                    axios.post('/field-mapping-setup/detail-create', data)
                    .then(response => {
                        this.table.values.data.push({
                            edit: false,
                            bid: response.data.data.bid,
                            field_mapping_bid: response.data.data.field_mapping_bid,
                            required: response.data.data.required === 1 ? true : false,
                            field: response.data.data.field,
                            description: response.data.data.description,
                            mapping_type: response.data.data.mapping_type,
                            file_name: response.data.data.file_name,
                            default_value: response.data.data.default_value,
                            column_name: response.data.data.column_name,
                        });
                        this.clearFields();
                        
                    }).catch(error => {
                        this.errors.add.field = error.response.data.errors.field[0];
                    })
                }
            },

            updateRow(data) {
                if (this.form.mode === 'create') {
                    var exist = this.table.values.data.some((element, index) => element.field == data.values.field.toLowerCase() && data.rowIndex !== index)
                    if (data.values.field && !exist) {

                        data.done();
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_updated_the_data');
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
                        this.errors.add.edit = null
                        this.table.values.data[data.rowIndex].edit = false,
                        this.table.values.data[data.rowIndex].bid = data.values.bid,
                        this.table.values.data[data.rowIndex].required = data.values.required,
                        this.table.values.data[data.rowIndex].field = data.values.field.toLowerCase(),
                        this.table.values.data[data.rowIndex].description = data.values.description,
                        this.table.values.data[data.rowIndex].mapping_type = data.values.mapping_type,
                        this.table.values.data[data.rowIndex].file_name = data.values.file_name,
                        this.table.values.data[data.rowIndex].default_value = data.values.default_value === "" ? '""' : this.defaultValue(data.values.default_value, data.values.mapping_type),
                        this.table.values.data[data.rowIndex].column_name = data.values.column_name,
                        this.table.values.data[data.rowIndex].error = ''
                    } else {
                        if (exist) {
                            this.errors.add = {};
                            this.errors.error = {};
                            this.errors.add.field = '';
                            this.table.values.data[data.rowIndex].error = this.$t('error.cdis_field_unique');
                        } else {
                            this.table.values.data[data.rowIndex].error = this.$t('validation.the_cdis_field_is_required')
                            this.errors.error = {};
                        }
                    }
                } else {
                    var config = {
                        bid: data.values.bid,
                        field_mapping_bid: data.values.field_mapping_bid,
                        required: data.values.required ? 1 : 0,
                        field: data.values.field,
                        description: data.values.description,
                        mapping_type: data.values.mapping_type,
                        file_name: data.values.file_name,
                        default_value: data.values.default_value === "" ? '""' : this.defaultValue(data.values.default_value, data.values.mapping_type),
                        column_name: data.values.column_name,
                    }

                    axios.put(`/field-mapping-setup/detail-update/${data.values.bid}`, config)
                    .then(response => {
                        data.done();
                        this.table.values.data[data.rowIndex].edit = false,
                        this.table.values.data[data.rowIndex].bid = data.values.bid,
                        this.table.values.data[data.rowIndex].field_mapping_bid = data.values.field_mapping_bid,
                        this.table.values.data[data.rowIndex].required = data.values.required,
                        this.table.values.data[data.rowIndex].field = data.values.field,
                        this.table.values.data[data.rowIndex].description = data.values.description,
                        this.table.values.data[data.rowIndex].mapping_type = data.values.mapping_type,
                        this.table.values.data[data.rowIndex].file_name = data.values.file_name,
                        this.table.values.data[data.rowIndex].default_value = data.values.default_value === "" ? '""' : this.defaultValue(data.values.default_value, data.values.mapping_type),
                        this.table.values.data[data.rowIndex].column_name = data.values.column_name,
                        this.table.values.data[data.rowIndex].error = '';
                    }).catch(error => {
                        this.table.values.data[data.rowIndex].error = error.response.data.errors.field[0];
                        this.errors.error = {};
                    })
                }
            },

            deleteRow(data) {
                if (this.form.mode === 'create') {
                    if (data.type === 'clear') {
                        return;
                    }
                    this.table.values.data.splice(data.rowIndex, 1);
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_removed_the_data');
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                } else {
                    if (data.type === 'clear') {
                        return;
                    }

                    this.dialog.visible = true;
                    this.dialog.status = 'confirm';
                    this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                    this.dialog.ok.function = () => {

                    axios.delete(`/field-mapping-setup/detail_delete/${data.values.bid}`)
                        .then(response => {
                            this.table.values.data.splice(data.rowIndex, 1);
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_removed_the_data');
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                            };
                        })
                    };
                    this.dialog.cancel.function = () => {
                        this.dialog.visible = false;
                    };
                }
            },
            getError(object, name) {
                let error = object[name];
                return error ? error[0] : '';
                
            },
            removeError(object, name) {
                delete object[name];
            },
            defaultTypeUpdate(type) {
                if (type === "VARCHAR" || type === "TEXT") {
                    return 'text';
                } else if (type === "DATETIME") {
                    return 'datetime-local';
                } else if (type === "DATE") {
                    return 'date';
                } else if (type === "TIME") {
                    return 'time';
                } else {
                    return 'number';
                }
            },
            defaultType() {
                if (this.table.add.mapping_type === "VARCHAR" || this.table.add.mapping_type === "TEXT") {
                    return 'text';
                } else if (this.table.add.mapping_type === "DATETIME") {
                    return 'datetime-local';
                } else if (this.table.add.mapping_type === "DATE") {
                    return 'date';
                } else if (this.table.add.mapping_type === "TIME") {
                    return 'time';
                } else {
                    return 'number';
                }
            },
            defaultValue(value, type) {
                var n = value.length
                if (type === "INT" || type === "BIGINT" || type === "TINYINT") {
                    return Math.floor(value);
                } else if (type === "DECIMAL") {
                    let val = (value/1).toFixed(2).replace('.', '.');
                    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "")
                } else if (type === "TIME") {
                    if (n > 8) {
                        return value.substr(0, 8)
                    } else if ( n === 5){
                        return value+":00";
                    } else {
                        return value;
                    }
                } else if (type === "DATETIME") {
                    if (n === 19) {
                        return value;
                    } else if (n === 16) {
                        return moment(value).format('YYYY-MM-DD')+" "+value.substr(value.indexOf("T")+1, 5)+":00"
                    } else {
                        return moment(value).format('YYYY-MM-DD')+" "+value.substr(value.indexOf("T")+1, 8)
                    }
                } else {
                    return value;
                }
            }
        }
    }
</script>

<style lang="scss" scoped>
    .back-to-list {
        color: #212529;
        text-decoration: none;
    }
</style>
