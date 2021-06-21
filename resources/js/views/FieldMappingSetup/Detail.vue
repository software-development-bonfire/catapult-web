<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1 d-flex justify-content-between align-content-center">
            <a href="/field-mapping-setup" class="back-to-list ml-2">
                <i class="fa fa-arrow-circle-left fa-lg"></i>
                <span>{{ $t('label.back_to_list') }}</span>
            </a>
            <button class="button button--light module-action-button" @click="save">{{ $t('label.save') }}</button>
        </div>
        <div class="container-fluid">
            <table class="table-layout pull-left col-xl-4">
                <tr>
                    <td valign="top" align="right">{{ $t('label.mapping_type') }}</td>
                    <td width="200px">
                        <select class="form-control" v-model="form.values.mapping_type" @change="getPreset()">
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
                <tr v-if="this.form.mode === 'create'">
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
                    <!-- <table-data
                        valign="center"
                        :error="tableData.error">
                        <template v-if="tableData.edit">
                            <input type="text" class="form-control" v-model="tableData.field">
                        </template>
                        <template v-else>
                            <span v-text="tableData.field"></span>
                        </template>
                    </table-data> -->
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
                        <input type="text" class="form-control" v-model="tableData.csv_file_name_identifier" disabled>
                    </table-data>
                    <table-data
                        v-if="form.values.mapping_type === 2"
                        valign="center">
                        <template v-if="tableData.edit">
                            <input type="text" class="form-control" v-model="tableData.default_value">
                        </template>
                        <template v-else>
                            <span v-text="tableData.default_value"></span>
                        </template>
                    </table-data>
                    <table-data
                        valign="center">
                        <input type="text" class="form-control" v-model="tableData.csv_column_name" disabled>
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
                            v-model="table.add.csv_file_name_identifier"
                            disabled>
                    </table-data>
                    <table-data
                        v-if="form.values.mapping_type === 2">
                        <input
                            type="text"
                            class="form-control"
                            v-model="table.add.default_value">
                    </table-data>
                    <table-data>
                        <input
                            type="text"
                            class="form-control"
                            v-model="table.add.csv_column_name"
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
                this.form.values.api_endpoint = urlData.data.api_endpoint;
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
                            required: element.required,
                            mapping_type: element.mapping_type
                        }
                    });
                }
            }
            this.getPreset();
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
                                name: "mapping_type",
                                label: this.$t('label.mapping_type'),
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
                                name: "mapping_type",
                                label: this.$t('label.mapping_type'),
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
                        csv_file_name_identifier: '',
                        default_value: '',
                        csv_column_name: '',
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
                if (this.form.values.copy_preset_from) {
                    var presets = preset.details;
                    this.dialog.visible = true;
                    this.dialog.status = 'confirm';
                    this.dialog.message = this.$t('message.are_you_sure_you_want_to_load_this_preset');
                    this.dialog.ok.function = () => {
                        presets.forEach(element => {
                            this.table.values.data.push({
                                edit: false,
                                required: element.required,
                                field: element.field,
                                description: element.description,
                                mapping_type: element.mapping_type,
                                csv_file_name_identifier: element.file_name,
                                default_value: element.default_value,
                                csv_column_name: element.csv_column_name,
                            });
                        })
                        this.dialog.visible = false;
                    };

                    this.dialog.cancel.function = () => {
                        this.dialog.visible = false;
                    };
                }

            },
            save() {
                if (this.form.mode === 'create') {
                    var config = {
                        method: 'create',
                        type: this.form.values.mapping_type,
                        api_endpoint: this.form.values.api_endpoint,
                        api_version_name: this.form.values.api_version_name,
                        status: this.form.values.version_status,
                        details: this.table.values.data
                    }
                    axios.post('/field-mapping-setup/detail', config)
                    .then(response => {
                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_created', { value: this.$t('label.field_mapping_setup') });
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            window.open('/field-mapping-setup', '_self');
                        };
                        this.errors.add = {};
                    }).catch(error => {
                        this.errors.add = error.response.data.errors;
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
                    csv_file_name_identifier: '',
                    default_value: '',
                    csv_column_name: '',
                };
                this.errors.add.field = '';
            },

            addRow() {
                if (this.form.mode === 'create') {
                    var exist = this.table.values.data.some(element => element.field == this.table.add.field)
                    
                    if (this.table.add.field && !exist) {
                        this.table.values.data.push({
                            edit: false,
                            required: this.table.add.required ? 1 : 0,
                            field: this.table.add.field,
                            description: this.table.add.description,
                            mapping_type: this.table.add.mapping_type,
                            csv_file_name_identifier: this.table.add.csv_file_name_identifier,
                            default_value: this.table.add.default_value === "" ? '\"\"' : this.table.add.default_value,
                            csv_column_name: this.table.add.csv_column_name,
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
                        file_name: this.table.add.csv_file_name_identifier,
                        default_value: this.table.add.default_value === "" ? '\"\"' : this.table.add.default_value,
                        column_name: this.table.add.csv_column_name,
                    }
                    axios.post('/field-mapping-setup/detail-create', data)
                    .then(response => {
                        this.table.values.data.push({
                            edit: false,
                            field_mapping_bid: this.form.values.bid,
                            required: this.table.add.required ? 1 : 0,
                            field: this.table.add.field,
                            description: this.table.add.description,
                            mapping_type: this.table.add.mapping_type,
                            csv_file_name_identifier: this.table.add.csv_file_name_identifier,
                            default_value: this.table.add.default_value === "" ? '\"\"' : this.table.add.default_value,
                            csv_column_name: this.table.add.csv_column_name,
                        });
                        this.clearFields();
                        
                    }).catch(error => {
                        this.errors.add.field = error.response.data.errors.field[0];
                    })
                }
            },

            updateRow(data) {
                console.log(data)
                console.log(this.table.values.data)
                if (this.form.mode === 'create') {
                    var exist = this.table.values.data.some((element, index) => element.field == data.values.field && data.rowIndex !== index)

                    if (data.values.field && !exist) {
                        data.done();
                        this.dialog.status = 'success';
                        this.dialog.message = this.$t('success.successfully_updated_the_data');
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
                        this.errors.add.edit = null
                        this.table.values.data[data.rowIndex].error = ''
                    } else {
                        if (exist) {
                            this.errors.error = {};
                            this.table.values.data[data.rowIndex].error = this.$t('error.cdis_field_unique');
                        } else {
                            this.table.values.data[data.rowIndex].error = this.$t('validation.the_cdis_field_is_required')
                            this.errors.error = {};
                        }
                    }
                } else {
                    axios.put(`/field-mapping-setup/detail-update/${data.values.bid}`, data.values)
                    .then(response => {
                        data.done();
                    }).catch(error => {
                        this.table.values.data[data.rowIndex].error = error.response.data.errors.field[0];
                        this.errors.error = {};
                    })
                }
            },

            deleteRow(data) {
                if (this.form.mode === 'create') {
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
