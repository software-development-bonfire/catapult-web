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
                        <select class="form-control" v-model="form.values.mapping_type">
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
                                <option :value="1">{{ $t('label.product') }}</option>
                                <option :value="2">{{ $t('label.brand') }}</option>
                                <option :value="3">{{ $t('label.category') }}</option>
                                <option :value="4">{{ $t('label.vendor') }}</option>
                                <option :value="5">{{ $t('label.uom') }}</option>
                            </template>
                            <template v-else>
                                <option :value="1">{{ $t('label.transactions') }}</option>
                                <option :value="2">{{ $t('label.zread') }}</option>
                                <option :value="3">{{ $t('label.audit_trail') }}</option>
                                <option :value="4">{{ $t('label.cash_breakdown') }}</option>
                                <option :value="5">{{ $t('label.cash_drawer') }}</option>
                            </template>
                        </select>
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
                            :class="{ 'is-invalid': form.values.api_version_name == '' }"
                            v-model="form.values.api_version_name">
                        <label
                            class="text-danger error-message mb-0"
                            v-if="form.values.api_version_name == ''">
                            API Version Name is required.
                        </label>
                    </td>
                </tr>
                <tr>
                    <td valign="top" align="right">{{ $t('label.copy_preset_from') }}</td>
                    <td>
                        <select class="form-control" v-model="form.values.copy_preset_from">
                            <option value=""></option>
                            <option value="transactions">Transaction API field v 1.0</option>
                        </select>
                    </td>
                    <td>
                        <button class="button button--light">{{ $t('label.load') }}</button>
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
                    <table-data
                        valign="center">
                        <template v-if="tableData.edit">
                            <input type="text" class="form-control" v-model="tableData.field">
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
                            <select class="form-control" v-model="tableData.data_type">
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
                            <span v-text="tableData.data_type"></span>
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
                            <input type="text" class="form-control" v-model="tableData.default_field_values">
                        </template>
                        <template v-else>
                            <span v-text="tableData.default_field_values"></span>
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
                            v-model="table.add.data_type">
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
                            v-model="table.add.default_field_values">
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
                this.form.values.mapping_type = Number(urlData.data.mapping_type);
                this.form.values.api_endpoint = urlData.data.api_endpoint;
                this.form.values.api_version_name = urlData.data.api_version_name;
                this.form.values.version_status = Number(urlData.data.status);
            }
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
                        api_endpoint: 1,
                        api_version_name: '',
                        copy_preset_from: '',
                        version_status: 1
                    }
                },
                errors: {
                    add: {
                        field: 'Field is required.'
                    }
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
                                name: "default_field_values",
                                label: this.$t('label.default_field_values'),
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
                        data_type: 'DECIMAL',
                        csv_file_name_identifier: '',
                        default_field_values: '',
                        csv_column_name: '',
                    },
                    settings: {
                        itemsPerPage: 10,
                        withRowNumbers: false,
                        hasEdit: true,
                        hasDelete: true,
                        withPagination: false
                    }
                }
            }
        },
        methods: {
            save() {
                if (this.form.mode === 'create') {
                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_created', { value: this.$t('label.field_mapping_setup') });
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                } else {
                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.field_mapping_setup') });
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                }
            },

            clearFields() {
                this.table.add = {
                    edit: false,
                    required: false,
                    field: '',
                    description: '',
                    data_type: 'INT',
                    csv_file_name_identifier: '',
                    default_field_values: '',
                    csv_column_name: '',
                };
            },

            addRow() {
                this.table.values.data.push({
                    edit: false,
                    required: this.table.add.required,
                    field: this.table.add.field,
                    description: this.table.add.description,
                    data_type: this.table.add.data_type,
                    csv_file_name_identifier: this.table.add.csv_file_name_identifier,
                    default_field_values: this.table.add.default_field_values === "" ? '\"\"' : this.table.add.default_field_values,
                    csv_column_name: this.table.add.csv_column_name,
                });
                
                this.dialog.status = 'success';
                this.dialog.message = this.$t('success.successfully_added_the_data');
                this.dialog.ok.function = () => {
                    this.dialog.visible = false;
                };

                this.clearFields();
            },

            updateRow(data) {
                data.done();
                this.dialog.status = 'success';
                this.dialog.message = this.$t('success.successfully_updated_the_data');
                this.dialog.ok.function = () => {
                    this.dialog.visible = false;
                };
            },

            deleteRow(data) {
                if (data.type === 'clear') {
                    return;
                }

                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                this.dialog.ok.function = () => {
                    this.table.values.data.splice(data.rowIndex, 1);
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.successfully_removed_the_data');
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                };
                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
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
