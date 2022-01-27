<template>
    <div class="module-container">
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
                        <span v-text="tableData.file_storage_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.catapult_db_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.api_setup_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.data_entry"></span>
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
                        <template v-if="form.values.transaction == 'Transaction'">
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
                        </template>
                        <template v-if="form.values.transaction == 'Z Read'">
                            <ul class="unindented-list">
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.zread_head">
                                        <span>{{ $t('label.zread_head') }}</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.cash_breakdown">
                                        <span>{{ $t('label.cash_breakdown') }}</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.cashier_summary">
                                        <span>{{ $t('label.cashier_summary') }}</span>
                                    </label>
                                </li>
                            </ul>
                            <ul class="unindented-list ml-4">
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.regular_discount">
                                        <span>{{ $t('label.regular_discount') }}</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.tender_details">
                                        <span>{{ $t('label.tender_details') }}</span>
                                    </label>
                                </li>
                            </ul>
                        </template>
                        <template v-if="form.values.transaction == 'Cash Breakdown'">
                            <ul class="unindented-list">
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.cash_breakdown_head">
                                        <span>{{ $t('label.cash_breakdown_head') }}</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.cash_breakdown_detail">
                                        <span>{{ $t('label.cash_breakdown_detail') }}</span>
                                    </label>
                                </li>
                            </ul>
                        </template>
                        <template v-if="form.values.transaction == 'Cash Drawer'">
                            <ul class="unindented-list">
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.cash_drawer">
                                        <span>{{ $t('label.cash_drawer') }}</span>
                                    </label>
                                </li>
                            </ul>
                        </template>
                        <template v-if="form.values.transaction == 'Audit Trail'">
                            <ul class="unindented-list">
                                <li>
                                    <label class="radio-checkbox">
                                        <input type="checkbox" v-model="form.values.audit_trail">
                                        <span>{{ $t('label.audit_trail') }}</span>
                                    </label>
                                </li>
                            </ul>
                        </template>
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
    var config = window.location.origin;
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
                        transaction: 'Transactions',
                        transaction_head: false,
                        transaction_detail: false,
                        products: false,
                        payment: false,
                        discounts: false,
                        add_ons: false,
                        price_override: false,
                        zread_head: false,
                        cash_breakdown: false,
                        cashier_summary: false,
                        regular_discount: false,
                        tender_details: false,
                        cash_breakdown_head: false,
                        cash_breakdown_detail: false,
                        cash_drawer: false,
                        audit_trail: false,
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
                            name: "file_storage_setup_name",
                            label: this.$t('label.file_storage_setup_name'),
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
                            name: "data_entry",
                            label: this.$t('label.data_entry'),
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
                        hasDelete: true,
                    }
                },
                selections: {
                    transaction: {
                        options: [
                            {
                                label: 'Transaction',
                                value: 'Transaction',
                            },
                            {
                                label: 'Z Read',
                                value: 'Z Read',
                            },
                            {
                                label: 'Cash Breakdown',
                                value: 'Cash Breakdown',
                            },
                            {
                                label: 'Cash Drawer',
                                value: 'Cash Drawer',
                            },
                            {
                                label: 'Audit Trail',
                                value: 'Audit Trail',
                            }
                        ]
                    }
                }
            }
        },
        methods: {
            paginate(page = 1) {

                axios.get(`${config}/field-mapping/list`+'?page='+page, {
                    params: {
                        itemsPerPage: this.table.settings.itemsPerPage
                    }
                })
                .then(response => {
                    this.table.values.data = response.data.data.data
                    this.table.values.meta  = response.data.data.meta
                })
            },

            create() {
                window.open('/field-mapping/detail', '_self');
            },

            openCSVFileGeneratorModal() {
                this.clearFields();
                this.modal.visible = true;
            },

            clearFields() {
                this.form.values = {
                    transaction: 'Transactions',
                    transaction_head: false,
                    transaction_detail: false,
                    products: false,
                    payment: false,
                    discounts: false,
                    add_ons: false,
                    price_override: false,
                    zread_head: false,
                    cash_breakdown: false,
                    cashier_summary: false,
                    regular_discount: false,
                    tender_details: false,
                    cash_breakdown_head: false,
                    cash_breakdown_detail: false,
                    cash_drawer: false,
                    audit_trail: false,
                }
            },

            generateCSVFile() {
                if (this.form.values.transaction == 'Transactions') {
                    var payload = {
                        transaction: this.form.values.transaction,
                        transaction_head: this.form.values.transaction_head,
                        transaction_detail: this.form.values.transaction_detail,
                        products: this.form.values.products,
                        payment: this.form.values.payment,
                        discounts: this.form.values.discounts,
                        add_ons: this.form.values.add_ons,
                        price_override: this.form.values.price_override,
                    }
                } else if (this.form.values.transaction == 'Z Read') {
                    var payload = {
                        transaction: this.form.values.transaction,
                        zread_head: this.form.values.zread_head,
                        cash_breakdown: this.form.values.cash_breakdown,
                        cashier_summary: this.form.values.cashier_summary,
                        regular_discount: this.form.values.regular_discount,
                        tender_details: this.form.values.tender_details,
                    }
                } else if (this.form.values.transaction == 'Cash Breakdown') {
                    var payload = {
                        transaction: this.form.values.transaction,
                        cash_breakdown_head: this.form.values.cash_breakdown_head,
                        cash_breakdown_detail: this.form.values.cash_breakdown_detail,
                    }
                } else if (this.form.values.transaction == 'Cash Drawer') {
                    var payload = {
                        transaction: this.form.values.transaction,
                        cash_drawer: this.form.values.cash_drawer,
                    }
                } else if (this.form.values.transaction == 'Audit Trail') {
                    var payload = {
                        transaction: this.form.values.transaction,
                        audit_trail: this.form.values.audit_trail,
                    }
                }

                axios.get(`${config}/field-mapping/detail/generate-csv`, { params: payload })
                    .then(response => {
                        var file_path = response.data.data;
                        file_path.forEach(path => {
                            this.downloadCSV(path);
                        })
                    }).catch(error => {
                        this.dialog.visible = true;
                        this.dialog.status = 'error';
                        this.dialog.message = this.$t('error.generate_csv_failed_create');
                        this.dialog.ok.function = () => {
                            this.dialog.visible = false;
                        };
                    })
            },

            downloadCSV(path) {
                var url = config+'/'+path
                        
                axios({url: url, method: 'GET', responseType: 'blob',
                    }).then((response) => {
                        var fileURL = window.URL.createObjectURL(new Blob([response.data]));
                        var fileLink = document.createElement('a');

                        fileLink.href = fileURL;
                        fileLink.setAttribute('download', path.split('-').pop());
                        document.body.appendChild(fileLink);

                        fileLink.click();
                    })
            },

            openDetail(data) {
                window.open('/field-mapping/detail?' + QueryString.stringify({
                    bid: data.bid
                }), '_self');
            },

            deleteRow(data) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm-yes-no';
                this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                this.dialog.ok.function = () => {

                    axios.delete(`${config}/field-mapping/detail/list/${data.values.bid}`)
                        .then(response => {
                            this.paginate();

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
        }
    }
</script>

<style lang="scss" scoped>
</style>
