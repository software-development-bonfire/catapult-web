<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            <button class="button button--light module-action-button" @click="create">{{ $t('label.add_new') }}</button>
        </div>
        <div class="m-2">
            <button class="button button--light" @click="create">{{ $t('label.add_new') }}</button>
            <button class="button button--light" @click="openCSVFileGeneratorModal">{{ $t('label.generate_sample_csv') }}</button>
        </div>
        <datatable
            class="
                datatable--full-width
                datatable--hoverable"
            :header-fields="table.header"
            :settings="table.settings"
            :table="table.values"
            v-on:delete-row="deleteRow"
            v-on:paginate="paginate">
            <template slot="content">
                <table-row
                    type="view"
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:row-click="openDetail(tableData)">
                    <td class="datatable-cell">
                        <span v-text="tableData.name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span
                            v-text="
                                tableData.mapping_type === 1 ? $t('label.cdis_to_pos')
                                : tableData.mapping_type === 2 ? $t('label.pos_to_cdis')
                                : ''">
                        </span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.remote_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.catapult_db_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.api_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.api_to_map"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.status ? $t('label.active') : $t('label.inactive')"></span>
                    </td>
                </table-row>
            </template>
        </datatable>
        <modal
            centered-display
            v-if="modal.visible"
            @close="modal.visible = false">
            <template slot="header">
                {{ $t('label.generate_sample_csv_file') }}
            </template>
            <template slot="content">
                <div class="form-group">
                    <label>{{ $t('label.select_cdis_transaction') }} <span class="required">*</span></label>
                    <select
                        class="form-control"
                        v-model="form.values.transaction">
                        <option value="" hidden selected>{{ $t('label.select_transaction') }}</option>
                        <option
                            v-for="(item, itemIndex) in selections.transaction.options"
                            :key="itemIndex"
                            :value="item.value">
                            {{ item.label }}
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.select_csv_file_to_generate') }}</label>
                    <div class="d-flex">
                        <ul class="unindented-list">
                            <li>
                                <label class="radio-checkbox">
                                    <input type="checkbox" v-model="form.values.transaction_head">
                                    <span>{{ $t('label.transaction_head') }}</span>
                                </label>
                            </li>
                            <li>
                                <label class="radio-checkbox">
                                    <input type="checkbox" v-model="form.values.transaction_detail">
                                    <span>{{ $t('label.transaction_detail') }}</span>
                                </label>
                            </li>
                            <li>
                                <label class="radio-checkbox">
                                    <input type="checkbox" v-model="form.values.products">
                                    <span>{{ $t('label.products') }}</span>
                                </label>
                            </li>
                            <li>
                                <label class="radio-checkbox">
                                    <input type="checkbox" v-model="form.values.payment">
                                    <span>{{ $t('label.payment') }}</span>
                                </label>
                            </li>
                        </ul>
                        <ul class="unindented-list ml-4">
                            <li>
                                <label class="radio-checkbox">
                                    <input type="checkbox" v-model="form.values.discounts">
                                    <span>{{ $t('label.discounts') }}</span>
                                </label>
                            </li>
                            <li>
                                <label class="radio-checkbox">
                                    <input type="checkbox" v-model="form.values.add_ons">
                                    <span>{{ $t('label.add_ons') }}</span>
                                </label>
                            </li>
                            <li>
                                <label class="radio-checkbox">
                                    <input type="checkbox" v-model="form.values.price_override">
                                    <span>{{ $t('label.price_override') }}</span>
                                </label>
                            </li>
                        </ul>
                    </div>
                </div>
            </template>
            <template slot="footer">
                <div align="center">
                    <button class="button button--light" @click="generateCSVFile">{{ $t('label.generate') }}</button>
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
    import Modal from '../../components/Modal/Modal.vue';

    export default {
        components: {
            DialogBox,
            Datatable,
            TableRow,
            Modal
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
                    visible: false
                },
                form: {
                    values: {
                        transaction: '',
                        transaction_head: false,
                        transaction_detail: false,
                        products: false,
                        payment: false,
                        discounts: false,
                        add_ons: false,
                        price_override: false,
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: this.$t('label.field_mapping_name'),
                            width: '180'
                        },
                        {
                            name: "mapping_type",
                            label: this.$t('label.mapping_type'),
                            width: '180'
                        },
                        {
                            name: "remote_setup_name",
                            label: this.$t('label.remote_setup_name'),
                            width: '250'
                        },
                        {
                            name: "catapult_db_setup_name",
                            label: this.$t('label.catapult_db_setup_name'),
                            width: '180'
                        },
                        {
                            name: "api_setup_name",
                            label: this.$t('label.api_setup_name'),
                            width: '150'
                        },
                        {
                            name: "api_to_map",
                            label: this.$t('label.api_to_map'),
                            width: '150'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
                            width: '90'
                        }
                    ],
                    values: {
                        data: [
                            {
                                name: 'POS Transaction',
                                mapping_type: 1,
                                remote_setup_name: 'Transactions',
                                catapult_db_setup_name: 'Catapult_DB',
                                api_setup_name: 'Transactions',
                                api_to_map: 'Transactions',
                                status: 1,
                            },
                            {
                                name: 'POS Z Read',
                                mapping_type: 2,
                                remote_setup_name: 'Z Read',
                                catapult_db_setup_name: 'Catapult_DB',
                                api_setup_name: 'Z Read',
                                api_to_map: 'Transactions',
                                status: 1,
                            },
                            {
                                name: 'Product',
                                mapping_type: 1,
                                remote_setup_name: 'Transactions',
                                catapult_db_setup_name: 'Catapult_DB',
                                api_setup_name: 'Transactions',
                                api_to_map: 'Transactions',
                                status: 1,
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
                        withRowNumbers: true,
                        hasDelete: true
                    }
                },
                selections: {
                    transaction: {
                        options: [
                            {
                                label: 'Sales Transaction',
                                value: 'Sales Transaction',
                            },
                            {
                                label: 'Product Transaction',
                                value: 'Product Transaction',
                            },
                            {
                                label: 'POS Transaction',
                                value: 'POS Transaction',
                            }
                        ]
                    }
                }
            }
        },
        methods: {
            paginate(page = 1) {},

            create() {
                window.open('/field-mapping/detail', '_self');
            },

            openCSVFileGeneratorModal() {
                this.clearFields();
                this.modal.visible = true;
            },

            clearFields() {
                this.form.values = {
                    transaction: '',
                    transaction_head: false,
                    transaction_detail: false,
                    products: false,
                    payment: false,
                    discounts: false,
                    add_ons: false,
                    price_override: false,
                }
            },

            generateCSVFile() {
                this.modal.visible = false;
            },

            openDetail(data) {
                window.open('/field-mapping/detail?' + QueryString.stringify({
                    data: data
                }), '_self');
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
</style>
