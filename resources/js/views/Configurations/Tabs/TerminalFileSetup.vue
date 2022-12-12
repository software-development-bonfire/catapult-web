<template>
    <div class="tab-pane fade show active" id="api-setup" role="tabpanel" aria-labelledby="api-setup-tab">
        <div class="m-1">
            <button class="button button--dark" @click="create">{{ $t('label.add_new') }}</button>
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
                    v-on:row-click="openDetail(tableData, tableDataIndex)">
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.name"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.terminal_code"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.endpoint"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.type"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.terminal_path"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-if="tableData.status == 1">{{ $t('label.active') }}</span>
                        <span v-if="tableData.status == 0">{{ $t('label.inactive') }}</span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <i class="fa fa-edit fa-lg row-update ml-1" @click.stop="editRow(tableDataIndex, tableData)"></i>
                        <i class="fa fa-times-circle fa-lg row-delete ml-1 mr-1" @click.stop="deleteRow(tableDataIndex)"></i>
                    </td>
                </table-row>
            </template>
        </datatable>
        <modal
            centered-display
            v-if="modal.visible"
            @close="modal.visible = false">
            <template slot="header">
                Terminal File Setup Details
            </template>
            <template slot="content">
                <form-field
                    class="form-group"
                    :error="errors.terminal_code">
                    <label>{{ $t('label.terminal_code') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.terminal_code"
                        :class="errors.terminal_code !== '' ? 'is-invalid' : ''"
                        @keypress="errors.terminal_code = ''">
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.name">
                    <label>{{ $t('label.name') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.name"
                        :class="errors.name !== '' ? 'is-invalid' : ''"
                        @keypress="errors.name = ''">
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.endpoint">
                    <label>{{ $t('label.endpoint') }} <span class="required">*</span></label>
                    <v-select
                        class="v-select--hide-selected"
                        :class="errors.endpoint !== '' ? 'is-invalid' : ''"
                        :clearable="false"
                        v-model="form.values.endpoint"
                        :options="selections.endpoint.options"
                        @option:selected="errors.endpoint = ''">
                    </v-select>
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.type">
                    <label>{{ $t('label.type') }} <span class="required">*</span></label>
                    <select
                        class="form-control"
                        :class="errors.type !== '' ? 'is-invalid' : ''"
                        v-model="form.values.type"
                        @change="errors.type = ''">
                        <option :value="1">{{ $t('label.transactions') }}</option>
                        <option :value="2">{{ $t('label.x_reading') }}</option>
                        <option :value="3">{{ $t('label.y_reading') }}</option>
                        <option :value="4">{{ $t('label.z_reading') }}</option>
                    </select>
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.terminal_path">
                    <label>{{ $t('label.terminal_path') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.terminal_path"
                        :class="errors.terminal_path !== '' ? 'is-invalid' : ''"
                        @keypress="errors.terminal_path = ''">
                </form-field>
                <form-field
                    class="form-group">
                    <label>{{ $t('label.status') }}</label>
                    <select class="form-control" v-model="form.values.status">
                        <option :value="1">{{ $t('label.active') }}</option>
                        <option :value="0">{{ $t('label.inactive') }}</option>
                    </select>
                </form-field>
            </template>
            <template slot="footer">
                <div align="center">
                    <button class="button button--light" @click="save">{{ $t('label.save') }}</button>
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
    import FormField from '../../../components/Containers/FormField.vue';
    import Util from '../../../mixins/Util.vue';

    export default {
        components: {
            Datatable,
            TableRow,
            Modal,
            DialogBox,
            FormField
        },
        mixins: [ Util ],
        props: {
            activeTab: {
                type: Boolean
            }
        },
        mounted() {
            this.paginate();
        },
        data() {
            return {
                errors: {
                    terminal_code: '',
                    name: '',
                    endpoint: '',
                    type: '',
                    terminal_path: '',
                },
                filters: {},
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
                    index: 0,
                    mode: 'create',
                    values: {
                        id: '',
                        terminal_code: '',
                        name: '',
                        endpoint: '',
                        type: '',
                        terminal_path: '',
                        status: 1,
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: this.$t('label.name'),
                            width: '200'
                        },
                        {
                            name: "terminal_code",
                            label: this.$t('label.terminal_code'),
                            width: '150'
                        },
                        {
                            name: "endpoint",
                            label: this.$t('label.endpoint'),
                            width: '150'
                        },
                        {
                            name: "type",
                            label: this.$t('label.type'),
                            width: '120'
                        },
                        {
                            name: "terminal_path",
                            label: this.$t('label.terminal_path'),
                            width: '250'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
                            width: '90'
                        },
                        {
                            name: "actions",
                            label: '',
                            width: '50'
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
                        hasEdit: false,
                        hasDelete: false,
                    }
                },
                selections: {
                    endpoint: {
                        options: [
                            {
                                label: 'Endpoint 1',
                                value: '10001',
                            },
                            {
                                label: 'Endpoint 2',
                                value: '10002',
                            },
                            {
                                label: 'Endpoint 3',
                                value: '10003',
                            },
                        ]
                    },
                }
            }
        },
        methods: {
            paginate(page = 1) {
                if (this.$root.isLoading) return;
                axios.get('api-setup'+'?page='+page, {
                    params: {
                        itemsPerPage: this.table.settings.itemsPerPage,
                    }
                })
                .then(response => {
                   this.table.values.data = response.data.data.data
                   this.table.values.meta  = response.data.data.meta

                })
            },

            create() {
                this.clearForm();
                this.modal.visible = true;
            },

            clearForm() {
                this.errors = {
                    terminal_code: '',
                    name: '',
                    endpoint: '',
                    type: '',
                    terminal_path: '',
                };

                this.form.index = 0;
                this.form.mode = 'create';

                this.form.values = {
                    id: '',
                    terminal_code: '',
                    name: '',
                    endpoint: '',
                    type: '',
                    terminal_path: '',
                    status: 1,
                }
            },

            editRow(index, data) {
                this.clearForm();

                this.form.index = index;
                this.form.mode = 'update';

                this.form.values = {
                    id: index,
                    terminal_code: data.terminal_code,
                    name: data.name,
                    endpoint: data.endpoint_object,
                    type: data.type,
                    terminal_path: data.terminal_path,
                    status: 1,
                };

                this.modal.visible = true;
            },

            deleteRow(index) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = 'Do you want to remove this data?';
                this.dialog.ok.function = () => {
                    this.table.values.data.splice(index, 1);
                    this.dialog.status = 'success';
                    this.dialog.message = this.$t('success.value_successfully_deleted', { value: this.$t('label.terminal_file_setup') });
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                };
                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
            },

            save() {
                let i = this.form.index;

                this.errors.terminal_code = this.form.values.terminal_code === '' ? this.$t('error.the_value_field_is_required', { value: this.$t('label.terminal_code') }) : '';
                this.errors.name = this.form.values.name === '' ? this.$t('error.the_value_field_is_required', { value: this.$t('label.terminal_receipt_name') }) : '';
                this.errors.endpoint = this.form.values.endpoint === '' ? this.$t('error.the_value_field_is_required', { value: this.$t('label.endpoint') }) : '';
                this.errors.type = this.form.values.type === '' ? this.$t('error.the_value_field_is_required', { value: this.$t('label.type') }) : '';
                this.errors.terminal_path = this.form.values.terminal_path === '' ? this.$t('error.the_value_field_is_required', { value: this.$t('label.terminal_path') }) : '';

                if (this.form.values.terminal_code === ''
                    || this.form.values.name === ''
                    || this.form.values.endpoint === ''
                    || this.form.values.type === ''
                    || this.form.values.terminal_path === '') {
                    return;
                }

                this.dialog.visible = true;
                this.dialog.status = 'success';

                if (this.form.mode === 'create') {
                    this.table.values.data.push({
                        terminal_code: this.form.values.terminal_code,
                        name: this.form.values.name,
                        endpoint: this.form.values.endpoint.label,
                        endpoint_object: this.form.values.endpoint,
                        type: this.form.values.type,
                        terminal_path: this.form.values.terminal_path,
                        status: this.form.values.status
                    });

                    this.dialog.message = this.$t('success.value_successfully_created', { value: this.$t('label.terminal_file_setup') });
                } else {
                    this.table.values.data[i] = {
                        terminal_code: this.form.values.terminal_code,
                        name: this.form.values.name,
                        endpoint: this.form.values.endpoint.label,
                        endpoint_object: this.form.values.endpoint,
                        type: this.form.values.type,
                        terminal_path: this.form.values.terminal_path,
                        status: this.form.values.status
                    }

                    this.dialog.message = this.$t('success.value_successfully_updated', { value: this.$t('label.terminal_file_setup') });
                }

                this.dialog.ok.function = () => {
                    this.modal.visible = false;
                    this.dialog.visible = false;
                    this.clearForm();
                };

                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
            },

            openDetail(data, index) {
                this.errors = {}
                this.form.mode = 'update';

                this.form.values = {
                    index: index,
                    bid: data.bid,
                    name: data.name,
                    endpoint: data.endpoint,
                    status: data.status
                }

                this.modal.visible = true;
            }
        }
    }
</script>
