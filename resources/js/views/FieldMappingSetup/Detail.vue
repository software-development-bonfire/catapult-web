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
                    <td align="right">{{ $t('label.mapping_type') }}</td>
                    <td>
                        <select class="form-control" v-model="form.values.mapping_type">
                            <option value="cdis-to-pos">{{ $t('label.cdis_to_pos') }}</option>
                            <option value="pos-cdis">{{ $t('label.pos_to_cdis') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right">{{ $t('label.select_api_endpoint_to_map') }}</td>
                    <td>
                        <select class="form-control" v-model="form.values.api_endpoint">
                            <option value=""></option>
                            <template v-if="form.values.mapping_type === 'cdis-to-pos'">
                                <option value="product">{{ $t('label.product') }}</option>
                            </template>
                            <template v-else>
                                <option value="transactions">{{ $t('label.transactions') }}</option>
                                <option value="zread">{{ $t('label.zread') }}</option>
                                <option value="audit-trail">{{ $t('label.audit_trail') }}</option>
                                <option value="cash-breakdown">{{ $t('label.cash_breakdown') }}</option>
                                <option value="cash-drawer">{{ $t('label.cash_drawer') }}</option>
                            </template>
                        </select>
                    </td>
                </tr>
            </table>
            <table class="table-layout pull-left col-xl-4">
                <tr>
                    <td align="right">{{ $t('label.api_version_name') }}</td>
                    <td>
                        <input type="text" class="form-control" v-model="form.values.api_version_name">
                    </td>
                </tr>
                <tr>
                    <td align="right">{{ $t('label.copy_preset_from') }}</td>
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
                    <td align="right">{{ $t('label.version_status') }}</td>
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
                datatable--sm"
            :header-fields="table.header"
            :settings="table.settings"
            :table="table.values"
            v-on:add-row="addRow"
            v-on:update-row="updateRow"
            v-on:delete-row="deleteRow">
            <template slot="content">
                <table-row
                    class="datatable-row--sm"
                    type="view"
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:enable-row="tableData.edit = ! tableData.edit">
                    <td class="datatable-cell" align="center">
                        <input type="checkbox" v-model="tableData.required" :disabled="! tableData.edit">
                    </td>
                    <td class="datatable-cell">
                        <template v-if="tableData.edit">
                            <input type="text" class="form-control" v-model="tableData.field">
                        </template>
                        <template v-else>
                            <span v-text="tableData.field"></span>
                        </template>
                    </td>
                    <td class="datatable-cell">
                        <template v-if="tableData.edit">
                            <input type="text" class="form-control" v-model="tableData.description">
                        </template>
                        <template v-else>
                            <span v-text="tableData.description"></span>
                        </template>
                    </td>
                    <td class="datatable-cell" align="center">
                        <template v-if="tableData.edit">
                            <select class="form-control" v-model="tableData.data_type">
                                <option value=""></option>
                                <option value="INT">INT</option>
                                <option value="VARCHAR">VARCHAR</option>
                                <option value="BIGINT">BIGINT</option>
                                <option value="DATETIME">DATETIME</option>
                                <option value="DECIMAL">DECIMAL</option>
                            </select>
                        </template>
                        <template v-else>
                            <span v-text="tableData.data_type"></span>
                        </template>
                    </td>
                    <td class="datatable-cell">
                        <input type="text" class="form-control" v-model="tableData.csv_column_name" disabled>
                    </td>
                </table-row>
                <table-row
                    class="datatable-row--sm"
                    type="add"
                    :values="table.add"
                    :settings="table.settings">
                    <td class="datatable-cell" align="center">
                        <input type="checkbox" v-model="table.add.required">
                    </td>
                    <td class="datatable-cell">
                        <input type="text" class="form-control" v-model="table.add.field">
                    </td>
                    <td class="datatable-cell">
                        <input type="text" class="form-control" v-model="table.add.description">
                    </td>
                    <td class="datatable-cell">
                        <select class="form-control" v-model="table.add.data_type">
                            <option value=""></option>
                            <option value="INT">INT</option>
                            <option value="VARCHAR">VARCHAR</option>
                            <option value="BIGINT">BIGINT</option>
                            <option value="DATETIME">DATETIME</option>
                            <option value="DECIMAL">DECIMAL</option>
                        </select>
                    </td>
                    <td class="datatable-cell">
                        <input type="text" class="form-control" v-model="table.add.csv_column_name" disabled>
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
    </div>
</template>

<script>
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';

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
                filters: {
                    mapping_type: '',
                    status: ''
                },
                form: {
                    mode: 'create',
                    values: {
                        mapping_type: 'cdis-to-pos',
                        api_endpoint: '',
                        api_version_name: '',
                        copy_preset_from: '',
                        version_status: 1
                    }
                },
                table: {
                    header: [
                        {
                            name: "required",
                            label: this.$t('label.set_as_required'),
                            width: '100'
                        },
                        {
                            name: "field",
                            label: this.$t('label.cdis_field'),
                            width: '180'
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
                            name: "status",
                            label: this.$t('label.csv_column_name'),
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
                    add: {
                        edit: false,
                        required: false,
                        field: '',
                        description: '',
                        data_type: '',
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
                    data_type: '',
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
                    status: this.table.add.status,
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

            deleteRow(index) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                this.dialog.ok.function = () => {
                    this.table.values.data.splice(index, 1);
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
