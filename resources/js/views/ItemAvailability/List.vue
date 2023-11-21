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
            <button class="button button--light">{{ $t('label.generate_sample_csv') }}</button>
        </div>
        <div class="box-row box-row--white d-flex justify-content-between position-relative">
            <div class="form-inline">
                <div class="form-group my-2 mx-3">
                    <label>{{ $t('label.search') }}:&nbsp;&nbsp;</label>
                    <input type="text" class="form-control" v-model="filters.search">
                </div>
            </div>
        </div>
        <div class="fixed-table-headers">
            <table border="1" cellpadding="4">
                <thead>
                    <tr>
                        <td rowspan="2" class="tc--row-number">#</td>
                        <td rowspan="2" class="tc--item-code">{{ $t('label.item_code') }}</td>
                        <td rowspan="2" class="tc--barcode">{{ $t('label.barcode') }}</td>
                        <td rowspan="2" class="tc--long-description">{{ $t('label.long_description') }}</td>
                        <td rowspan="2" class="tc--short-description">{{ $t('label.short_description') }}</td>
                        <td
                            v-for="(header, headerIndex) in terminalHeaders"
                            :key="headerIndex">
                            {{ header.name }}
                        </td>
                    </tr>
                    <tr>
                        <td
                            class="p-0"
                            v-for="(header, headerIndex) in terminalHeaders"
                            :key="headerIndex">
                            <div class="d-flex">
                                <div
                                    v-for="(terminal, terminalIndex) in header.terminals"
                                    class="tc--terminal-checkbox"
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
                :settings="table.settings"
                :table="table.values"
                v-on:paginate="paginate">
                <template slot="content">
                    <table-row
                        class="table-row--cells-no-padding"
                        v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                        :values="tableData"
                        :settings="table.settings"
                        :rowIndex="tableDataIndex">
                        <td class="datatable-cell tc--item-code">
                            <span v-text="tableData.item_code" class="p-2"></span>
                        </td>
                        <td class="datatable-cell tc--barcode">
                            <span v-text="tableData.barcode" class="p-2"></span>
                        </td>
                        <td class="datatable-cell tc--long-description">
                            <span v-text="tableData.long_description" class="p-2"></span>
                        </td>
                        <td class="datatable-cell tc--short-description">
                            <span v-text="tableData.short_description" class="p-2"></span>
                        </td>
                        <td
                            class="datatable-cell"
                            v-for="(device, deviceIndex) in tableData.devices"
                            :key="deviceIndex">
                            <div class="d-flex">
                                <div
                                    class="tc--terminal-checkbox p-2"
                                    v-for="(terminal, terminalIndex) in device.terminals"
                                    :key="terminalIndex">
                                    <input type="checkbox" v-model="terminal.availability">
                                </div>
                            </div>
                        </td>
                    </table-row>
                </template>
            </datatable>
        </div>
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
        &--short-description {
            min-width: 200px;
            max-width: 200px;
        }
        &--terminal-checkbox {
            min-width: 120px;
            min-width: 120px;
            text-align: center;
            border-left: 1px #ccc solid;
            &:nth-of-type(1) {
                border-left: none;
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
    import Util from '../../mixins/Util.vue';

    export default {
        components: {
            Datatable,
            TableRow,
            DialogBox,
            Modal,
            Popper,
            DatePicker
        },
        mixins: [ Util ],
        data() {
            return {
                filters : {
                    search: '',
                },
                modal: {
                    detail: {
                        visible: false,
                        data: {},
                    }
                },
                summary: [],
                errors: {},
                terminalHeaders: [],
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
                    values: {
                        data: [
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
                            },
                            {
                                item_code: 'PR1001',
                                barcode: '100001',
                                long_description: 'SIZZLING DOUBLE PORKCHOP',
                                short_description: 'S-DOUBLE PORKCHOP',
                                devices: [
                                    {
                                        name: 'Sirius POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Calm POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 3',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Angry POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                    {
                                        name: 'Happy POS',
                                        terminals: [
                                            {
                                                name: 'Terminal 1',
                                                availability: true
                                            },
                                            {
                                                name: 'Terminal 2',
                                                availability: true
                                            }
                                        ]
                                    },
                                ]
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
                        withTableHeaders: false,
                        withPagination: true,
                        fixedHeaderScroll: true,
                        hasEdit: false,
                        hasDelete: false
                    }
                },
            }
        },
        mounted() {
            this.generateTerminalHeaders();
        },
        methods: {
            generateTerminalHeaders() {
                let self = this;
                let table = [...this.table.values.data];
                let headers = [];

                table.forEach(function(item) {
                    item.devices.forEach(function(device) {
                        headers.push({
                            name: device.name,
                            terminals: device.terminals
                        })
                    })
                });

                this.terminalHeaders = headers;
            },
            paginate() {},
        }
    }
</script>
