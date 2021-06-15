<template>
    <div class="tab-pane fade" id="api-setup" role="tabpanel" aria-labelledby="api-setup-tab">
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
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:row-click="openDetail(tableData, tableDataIndex)">
                    <td class="datatable-cell">
                        <span v-text="tableData.name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.end_point"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-if="tableData.status == 1">{{ $t('label.active') }}</span>
                        <span v-if="tableData.status == 0">{{ $t('label.inactive') }}</span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <i class="fa fa-times-circle fa-lg row-delete" @click.stop="deleteRow(tableDataIndex, tableData)"></i>
                    </td>
                </table-row>
            </template>
        </datatable>
        <modal
            centered-display
            v-if="modal.visible"
            @close="modal.visible = false">
            <template slot="header">
                {{ $t('label.api_setup_detail') }}
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>{{ $t('label.api_setup_name') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.name"
                    :class="{ 'is-invalid': errors.hasOwnProperty('name') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('name')">
                        {{errors.name[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.end_point') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" v-model="form.values.end_point"
                    :class="{ 'is-invalid': errors.hasOwnProperty('end_point') }">
                    <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('end_point')">
                        {{errors.end_point[0]}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.status') }}</label>
                    <select class="form-control" v-model="form.values.status">
                        <option :value="1">{{ $t('label.active') }}</option>
                        <option :value="0">{{ $t('label.inactive') }}</option>
                    </select>
                </div>
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
    import Util from '../../../mixins/Util.vue';

    export default {
        components: {
            Datatable,
            TableRow,
            Modal,
            DialogBox
        },
        mounted() {
            this.paginate()
        },
        mixins: [ Util ],
        data() {
            return {
                errors: {},
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
                    mode: 'create',
                    values: {
                        id: '',
                        name: '',
                        end_point: '',
                        status: 1,
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: this.$t('label.api_setup_name'),
                            width: '200'
                        },
                        {
                            name: "end_point",
                            label: this.$t('label.end_point'),
                            width: '200'
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
                        data: [
                            {
                                name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
                                end_point: 'ftp://pathto POS Transactions',
                                status: 'Active',
                            },
                            {
                                name: 'Transactions',
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
                this.errors = {}
                this.form.mode = 'create';

                this.form.values = {
                    name: '',
                    end_point: '',
                    status: 1,
                }
            },

            deleteRow(index, data) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = 'Do you want to remove this data?';
                this.dialog.ok.function = () => {
                    axios.delete(`api-setup/${data.bid}`)
                    .then(response => {
                        this.table.values.data.splice(index, 1);
                        this.dialog.status = 'success';
                        this.dialog.message = response.data.message;
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
                    })
                };
                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
            },

            save() {
                if (this.form.mode === 'create') {
                    
                    axios.post('api-setup', this.form.values)
                    .then(response => {
                        this.paginate();
                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = response.data.message;
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            this.modal.visible = false;
                        };
                        this.errors = {}
                    }).catch(error => {
                        this.errors = error.response.data.errors
                    })
                } else {
                    let index = this.form.values.index;

                    axios.put(`api-setup/${this.form.values.bid}`, this.form.values)
                    .then(response => {
                        
                        this.table.values.data[index] = {
                            bid: this.form.values.bid,
                            name: this.form.values.name,
                            end_point: this.form.values.end_point,
                            status: this.form.values.status
                        }

                        this.dialog.visible = true;
                        this.dialog.status = 'success';
                        this.dialog.message = response.data.message;
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                            this.modal.visible = false;
                        };
                        this.errors = {}
                    }).catch(error => {
                        this.errors = error.response.data.errors
                    })
                }
            },

            openDetail(data, index) {
                console.log(data)
                this.errors = {}
                this.form.mode = 'update';

                this.form.values = {
                    index: index,
                    bid: data.bid,
                    name: data.name,
                    end_point: data.end_point,
                    status: data.status
                }

                this.modal.visible = true;
            }
        }
    }
</script>