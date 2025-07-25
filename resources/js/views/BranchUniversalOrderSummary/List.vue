<template>
    <div class="module-container">
        <div class="box-row box-row--white d-flex justify-content-between position-relative">
            <div class="flex-container">
                <div class="form-inline">
                    <div class="m-1">
                        <button
                            class="button button--light">
                            {{ $t('label.display_option') }}
                        </button>
                    </div>
                    <div class="m-1">
                        <button
                            class="button button--light">
                            {{ $t('label.alarm_option') }}
                        </button>
                    </div>
                </div>
                <div class="form-inline m-2">
                    <div class="m-1">
                        <input
                            type="text"
                            class="form-control form-search"
                            v-model="filters.search_keyword"
                            :placeholder="$t('label.search')"
                            @keypress.enter="paginate(null, null, true)"
                        >
                    </div>
                    <div class="m-1">
                        <button
                            class="button button--light"
                            @click="paginate()"
                            @keypress.enter="paginate()">
                            {{ $t('label.ok') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-1 overflow-hidden">
            <datatable
                class="
                    datatable--overflow-scroll"
                    :header-fields="table.header"
                    :settings="item.settings !== undefined
                        ? item.settings
                        : $set(item, 'settings', {...table.settings})
                    "
                    :table="item.values"
                    v-on:paginate="paginate"
                    v-on:show-all="showAll($event, item)"
                    v-on:sort="sort($event)">
                <template slot="content">
                    <table-row
                        class="table-row--cells-no-padding"
                        v-for="(tableData, tableDataIndex) in item.values.data" :key="tableDataIndex"
                        :values="tableData"
                        :settings="item.settings"
                        :rowIndex="tableDataIndex">
                        <td class="datatable-cell tc--order-no">
                            <div v-text="tableData.order_no" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--date-time">
                            <div v-text="tableData.date_and_time" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--date-time">
                            <div v-text="tableData.time_needed" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--time-needed">
                            <span 
                                class="device-type device-type--box"
                                :class="tableData.device_type === device.sirius_pos ? 'device-type--pos'
                                    : tableData.device_type === device.pda ?  'device-type--pda'
                                    : tableData.device_type === device.kiosk ?  'device-type--kiosk'
                                    : tableData.device_type === device.qr_mobile ?  'device-type--mobile'
                                    : tableData.device_type === device.kds ?  'device-type--kds'
                                    : tableData.device_type === device.ecommerce ?  'device-type--ecommerce'
                                    : ''"
                                v-text="tableData.ordertaker_id">
                            </span>
                        </td>
                        <td class="datatable-cell tc--ordertaker-id">
                            <div v-text="tableData.order_reference" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--type">
                            <div v-text="tableData.order_type" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--payment">
                            <div v-text="tableData.payment" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--process-in">
                            <div v-text="tableData.process_in" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--status">
                            <div v-text="tableData.status" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--action">
                            <div class="flex-container-center">
                                <button class="button button--black"><i class="fa fa-search"></i></button>
                                <button class="button"><i class="fa fa-clone"></i></button>
                                <button class="button button--black"><i class="fa fa-address-book"></i></button>
                            </div>
                        </td>
                    </table-row>
                </template>
            </datatable>
        </div>
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

<style lang="scss" scoped>
    .fixed-table-headers {
        overflow: hidden;
        table {
            thead {
                background-color: #F2F2F2;
                td {
                    text-align: center;
                    border-color: #ccc;
                }
            }
        }
    }
    .tc {
        &--row-number {
            min-width: 50px;
            max-width: 50px;
        }
        &--order-no {
            min-width: 220px;
            max-width: 220px;
        }
        &--date-time{
            min-width: 160px;
            max-width: 160px;
        }
        &--time-needed {
            min-width: 150px;
            max-width: 150px;
        }
        &--ordertaker-id {
            min-width: 150px;
            max-width: 150px;
        }
        &--type {
            min-width: 150px;
            max-width: 150px;
        }
        &--payment {
            min-width: 150px;
            max-width: 150px;
            text-align: center;
        }
        &--process-in {
            min-width: 150px;
            max-width: 150px;
        }
        &--status {
            min-width: 150px;
            max-width: 150px;
        }
        &--action {
            min-width: 130px;
            max-width: 130px;
        }
    }
    .form-search {
        width: 300px;
    }
    .flex-container {
        display: flex;
        justify-content: space-between;
        width: 100%;
    }
    .flex-container-center {
        display: flex;
        justify-content: center;
        width: 100%;
    }
    .fa {
        font-size: 14px !important;
    }
    .fa-search, .fa-address-book {
        color: white !important;
    }
    .button--black {
        border: 1px #ced4da solid;
        background: #000000;
    }
    .fa-clone {
        font-size: 19px !important;
    }
</style>

<script>
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';
    import CategoryPicker from '../../components/Forms/CategoryPicker.vue';
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Modal from '../../components/Modal/Modal.vue';
    import Util from '../../mixins/Util.vue';

    export default {
        props: {
            header: {
                type: Array
            },
            categories: {
                type: Object
            }
        },
        components: {
            Datatable,
            TableRow,
            DialogBox,
            Modal,
            Popper,
            DatePicker,
            CategoryPicker
        },
        mixins: [ Util ],
        data() {
            return {
                device: {
                    sirius_pos: POS.SIRIUS_POS,
                    pda: POS.PDA,
                    kiosk: POS.KIOSK,
                    qr_mobile: POS.QR_MOBILE,
                    kds: POS.KDS,
                    queueing: POS.QUEUEING,
                    ecommerce: POS.ECOMMERCE,
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
                sort_values: {},
                filters: {
                    search_keyword: '',
                },
                table: {
                    header: [
                        {
                            name: "pos_terminal_transactions.or_number",
                            label: this.$t('label.branch_universal_order_no'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "pos_terminal_transactions.log_date",
                            label: this.$t('label.order_date_/_time'),
                            width: '160',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "pos_terminal_transactions.order_schedule",
                            label: this.$t('label.time_needed'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "pos_terminal_transactions.device_type",
                            label: this.$t('label.ordertaker_id'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "pos_terminal_transactions.order_number",
                            label: this.$t('label.order_reference'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "pos_terminal_transactions.type",
                            label: this.$t('label.type'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "pos_terminal_transactions.payment_status",
                            label: this.$t('label.payment'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "cdis_terminal.name",
                            label: this.$t('label.process_in'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "pos_terminal_transactions.status",
                            label: this.$t('label.status'),
                            width: '150',
                            margin: 2,
                            sort: true,
                        },
                        {
                            name: "action",
                            label: this.$t('label.action'),
                            width: '130',
                            margin: 2,
                        },
                    ],
                    settings: {
                        itemsPerPage: 25,
                        withRowNumbers: true,
                        withTableHeaders: true,
                        withShowAll: true,
                        fixedHeaderScroll: true,
                        hasEdit: false,
                        hasDelete: false
                    }
                },
                item: {
                    values: {
                        data: [],
                        meta: {
                            pagination: {
                                count: 1,
                                current_page: 1,
                                links: {},
                                per_page: 25,
                                total: 1,
                                total_pages: 1
                            }
                        }
                    },
                }
            }
        },

        created() {
            this.setItemsPerPage();
        },

        mounted() {
            this.paginate();
        },

        methods: {
            setItemsPerPage() {
                this.table.settings.itemsPerPage = 25;
            },

            async paginate(
                page = 1, 
                data = false, 
                tableFilters = null,
                itemsPerPage = null
            ) {
                let filters = itemsPerPage !== null && tableFilters !== null ? tableFilters : {...this.filters},
                    self = this;

                return await axios.get('branch-universal-order-summary/list', {
                    params: {
                        filters: filters,
                        page: page,
                        itemsPerPage: itemsPerPage !== null
                            ? itemsPerPage
                            : self.item.settings.itemsPerPage,
                        isTablePaginate: (data !== null) ? true : false,
                        sort: self.sort_values
                    }
                }).then(response => {
                    this.item.values.data = response.data.data.list.data;
                    this.item.values.meta = response.data.data.list.meta;
                    return true;
                })
            },

            async showAll(emitted, data = null) {
                let itemsPerPage = emitted.status ? emitted.table.meta.pagination.total : this.table.settings.itemsPerPage;
                let filters = emitted.table.filters;
                let hasResponse = await this.paginate(1, data, filters, itemsPerPage);

                if (hasResponse) {
                    this.item.settings.itemsPerPage = itemsPerPage;
                    emitted.done(emitted.status);
                }

                this.show_all = emitted.status;
            },

            sort(event) {
                this.sort_values = {...event};
                this.paginate();
            },
        }
    }
</script>
