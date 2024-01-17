<template>
    <div class="module-container">
        <div class="row m-3">
            <div class="row align-items-stretch">
                <div class="summary_info col-lg-2 col-md-4" v-for="(data, dataIndex) in summary" :key="dataIndex">
                    <div class="wrap">
                        <h4 class="summary-info-header">{{ data.header }}</h4>
                        <span class="summary-info-count">{{ data.count }}</span>
                        <span class="summary-info-description">{{ data.description }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-row box-row--white p-2">
            <button class="button button--light" @click.stop="generateSample()">{{ $t('label.generate_sample_csv') }}</button>
        </div>
        <div class="box-row box-row--white d-flex justify-content-between position-relative">
            <table class="table-layout ml-2">
                <tr>
                    <td><b>{{ $t('label.category') }}:</b></td>
                    <td width="400px">
                        <category-picker
                            :tree.sync="selections.category.options"
                            :selected.sync="filters.category"
                            :width="700"
                            @category-data="($event) => {
                                filters.category_label = $event.label;
                            }"
                        />
                    </td>
                    <td><b>{{ $t('label.search') }}:</b></td>
                    <td width="200px">
                        <input
                            type="text"
                            class="form-control"
                            v-model="filters.search_keyword"
                            :placeholder="$t('label.filter_keyword')"
                            @keypress.enter="paginate(null, null, true)">
                    </td>
                    <td>
                        <button
                            class="button button--light"
                            @click="paginate(null, null, true)"
                            @keypress.enter="paginate(null, null, true)">
                            {{ $t('label.search') }}
                        </button>
                    </td>
                </tr>
            </table>
        </div>
        <div class="fixed-table-headers">
            <table border="1" cellpadding="4">
                <thead>
                    <tr>
                        <td rowspan="2" class="tc--row-number">#</td>
                        <td rowspan="2" class="tc--barcode">{{ $t('label.barcode') }}</td>
                        <td rowspan="2" class="tc--long-description">{{ $t('label.long_description') }}</td>
                        <td rowspan="2" class="tc--category">{{ $t('label.category') }}</td>
                        <td
                            v-for="(header, headerIndex) in terminalHeaders"
                            :key="headerIndex">
                            {{
                                header.name === deviceType.sirius_pos ? $t('label.sirius_pos')
                                : header.name === deviceType.pda ? $t('label.pda')
                                : header.name === deviceType.kiosk ? $t('label.kiosk')
                                : ''
                            }}
                        </td>
                    </tr>
                    <tr>
                        <td
                            class="p-0"
                            v-for="(header, headerIndex) in terminalHeaders"
                            :key="headerIndex">
                            <div class="d-flex">
                                <div
                                    class="tc--terminal-checkbox"
                                    v-for="(terminal, terminalIndex) in header.terminals"
                                    :key="terminalIndex">
                                    {{ terminal.name }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </thead>
            </table>
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
                    v-on:show-all="showAll($event, item)">
                <template slot="content">
                    <table-row
                        class="table-row--cells-no-padding"
                        v-for="(tableData, tableDataIndex) in item.values.data" :key="tableDataIndex"
                        :values="tableData"
                        :settings="item.settings"
                        :rowIndex="tableDataIndex">
                        <td class="datatable-cell tc--barcode">
                            <div v-text="tableData.barcode" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--long-description">
                            <div v-text="tableData.long_description" class="p-2"></div>
                        </td>
                        <td class="datatable-cell tc--category" align="center">
                            <div
                                class="category-display clearfix"
                                v-if="tableData.categories !== ''">
                                <div
                                    class="category-display-item"
                                    v-if="(typeof tableData.categories.category !== 'undefined')">
                                    {{ tableData.categories.category }}
                                    <i
                                        class="fa fa-caret-right category-display-item-caret"
                                        v-if="(typeof tableData.categories.sub_category_1 !== 'undefined')">
                                    </i>
                                </div>
                                <div
                                    class="category-display-item"
                                    v-if="(typeof tableData.categories.sub_category_1 !== 'undefined')">
                                    {{ tableData.categories.sub_category_1 }}
                                    <i
                                        class="fa fa-caret-right category-display-item-caret"
                                        v-if="(typeof tableData.categories.sub_category_2 !== 'undefined')">
                                    </i>
                                </div>
                                <div
                                    class="category-display-item"
                                    v-if="(typeof tableData.categories.sub_category_2 !== 'undefined')">
                                    {{ tableData.categories.sub_category_2 }}
                                </div>
                            </div>
                        </td>
                        <td
                            class="datatable-cell p-0 tc--terminal-checkbox"
                            v-for="(device, deviceIndex) in tableData.devices"
                            :key="deviceIndex">
                            <div
                                class="tc--terminal-checkbox"
                                v-if="device.item_availability_detail_bid !== null">
                                <input
                                    type="checkbox"
                                    v-model="device.is_available"
                                    @change="setItemCheckboxCooldown($event, device, tableData.product_uom_bid)">
                            </div>
                            <div class="tc--terminal-checkbox" v-else>
                                <input
                                    type="checkbox"
                                    disabled="true">
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
        &--item-code {
            min-width: 120px;
            max-width: 120px;
        }
        &--barcode {
            min-width: 120px;
            max-width: 120px;
        }
        &--long-description {
            min-width: 200px;
            max-width: 200px;
        }
        &--category {
            min-width: 300px;
            max-width: 300px;
        }
        &--terminal-checkbox {
            width: 120px;
            text-align: center;
            border-left: 1px #ccc solid;
            &:nth-of-type(1) {
                border-left: none;
            }
        }
        &--action {
            min-width: 90px;
            max-width: 90px;
        }
    }
    .category-display {
        display: flex;
        border: 1px #adadad solid;
        float: left;
        &-item {
            float: left;
            text-align: left;
            padding: 5px 10px;
            font-size: 12px;
            white-space: nowrap;
            position: relative;
            &-caret {
                font-size: 48px;
                position: absolute;
                top: -9px;
                z-index: 1;
            }
            &:nth-of-type(1) {
                background-color: #eaeaea;
                .category-display-item-caret {
                    color: #eaeaea;
                    right: -16px;
                }
            }
            &:nth-of-type(2) {
                padding-left: 25px;
                background-color: #f5f5f5;
                .category-display-item-caret {
                    color: #f5f5f5;
                    right: -16px;
                }
            }
            &:nth-of-type(3) {
                padding-left: 25px;
                background-color: #ddd;
            }
        }
    }
    .checkbox-cooldown {
        &--red {
            outline: 0;
            cursor: not-allowed;
            &::before {
                content: " ";
                position: relative;
                width: 13px;
                display: block;
                height: 13px;
                border-radius: 4px;
                color: #a42323;
                opacity: 0.3;
                background-color: #a42323;
            }
        }
    }
</style>

<script>
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Modal from '../../components/Modal/Modal.vue';
    import CategoryPicker from '../../components/Forms/CategoryPicker.vue';
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
                filters : {
                    search_keyword: '',
                    category: '',
                },
                modal: {
                    detail: {
                        visible: false,
                        data: {},
                    }
                },
                selections: {
                    category: {
                        options: []
                    }
                },
                terminalHeaders: this.header,
                deviceType: {
                    sirius_pos: POS.SIRIUS_POS,
                    pda: POS.PDA,
                    kiosk: POS.KIOSK,
                },
                summary: [],
                errors: {},
                table: {
                    header: [
                        {
                            name: "item_code",
                            label: this.$t('label.item_code'),
                            width: '150'
                        },
                        {
                            name: "barcode",
                            label: this.$t('label.barcode'),
                            width: '150'
                        },
                        {
                            name: "long_description",
                            label: this.$t('label.long_description'),
                            width: '220'
                        },
                        {
                            name: "short_description",
                            label: this.$t('label.short_description'),
                            width: '180'
                        },
                    ],
                    settings: {
                        itemsPerPage: 25,
                        withRowNumbers: true,
                        withTableHeaders: false,
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
            this.selections.category.options = this.categories.data;
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

                return await axios.get('item-availability/list', {
                    params: {
                        filters: filters,
                        page: page,
                        itemsPerPage: itemsPerPage !== null
                            ? itemsPerPage
                            : self.item.settings.itemsPerPage,
                        isTablePaginate: (data !== null) ? true : false,
                    }
                })
                .then(response => {
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

            setItemCheckboxCooldown(event, data, productBid) {
                let self = this;
                event.target.classList.add('checkbox-cooldown--red');
                event.target.disabled = true;
                self.update(data, productBid, event);

                setTimeout(() => {
                    event.target.classList.remove('checkbox-cooldown--red');
                    event.target.disabled = false;
                }, 3000);
            },

            async update(data, productBid) {
                let method = 'PATCH',
                    url = 'item-availability/update';

                return axios(url, {
                    method: method,
                    url: url,
                    data: {
                        product_uom_bid: productBid,
                        device: data.device_detail,
                        item_availability_detail_bid: data.item_availability_detail_bid,
                        is_available: data.is_available,
                    },
                }).then(function(response) {
                    return true;
                })
                .catch(error => {});
            },

            async generateSample() {
                let url = 'item-availability/store';
                return axios(url, {
                    method: 'POST',
                    url: url
                }).then(function(response) {
                    return true;
                })
                .catch(error => {});
            }
        }
    }
</script>
