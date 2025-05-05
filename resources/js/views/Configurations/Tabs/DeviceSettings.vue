<template>
    <div class="tab-pane fade show active" id="api-setup" role="tabpanel" aria-labelledby="api-setup-tab">
        <div class="m-1">
            <button class="button button--dark" @click="create">{{ $t('label.add_new') }}</button>
        </div>
        <datatable
            class="datatable--hoverable"
            :header-fields="table.header"
            :settings="devices.settings !== undefined
                ? devices.settings
                : $set(devices, 'settings', {...table.settings})
            "
            :table="devices.values"
            v-on:paginate="paginate"
            v-on:show-all="showAll($event, devices)">
            <template slot="content">
                <table-row
                    type="custom-actions"
                    v-for="(tableData, tableDataIndex) in devices.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="devices.settings"
                    :rowIndex="tableDataIndex"
                    v-on:dbl-row-click="editRow(tableDataIndex, tableData)">
                    <td class="datatable-cell" align="left">
                        <span 
                            class="device-type device-type--box"
                            :class="tableData.device_type === pos.sirius_pos ? 'device-type--pos'
                                : tableData.device_type === pos.pda ?  'device-type--pda'
                                : tableData.device_type === pos.kiosk ?  'device-type--kiosk'
                                : tableData.device_type === pos.qr_mobile ?  'device-type--mobile'
                                : tableData.device_type === pos.kds ?  'device-type--kds'
                                : tableData.device_type === pos.ecommerce ?  'device-type--ecommerce'
                                : ''"
                            v-text="tableData.device_type === pos.sirius_pos ? $t('label.sirius_pos')
                                : tableData.device_type === pos.pda ? $t('label.pda')
                                : tableData.device_type === pos.kiosk ? $t('label.kiosk')
                                : tableData.device_type === pos.qr_mobile ? $t('label.qr_mobile')
                                : tableData.device_type === pos.kds ? $t('label.kds')
                                : tableData.device_type === pos.ecommerce ? $t('label.ecommerce')
                                : ''">
                        </span>
                    </td>
                    <td class="datatable-cell" align="left">
                        
                        <div class="device--ip"> <i
                            class="fa fa-circle"
                            :class="tableData.socket_status ? 
                                'status--green': 
                                'status--gray'"
                        ></i>
                            <span v-text="tableData.name"></span>
                        </div>
                        <i class="device--uid" v-text="tableData.device_uid"></i>
                    </td>
                    <td class="datatable-cell" align="center">
                        <div class="device--ip" v-text="tableData.ip_address"></div>
                    </td>
                    <!--
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.api_endpoint"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.token"></span>
                    </td>
                    -->
                    <td class="datatable-cell" align="center">
                        <span v-if="tableData.device_type === pos.sirius_pos" v-text="tableData.background_process_priority"></span>
                        <span v-else></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.kitchen_station_name"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span
                            class="status_label"
                            :class="tableData.status ? 'status_label--active' : 'status_label--inactive'"
                            v-text="tableData.status ? $t('label.active') : $t('label.inactive')">
                        </span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <i class="fa fa-edit fa-lg row-update ml-1" @click.stop="editRow(tableDataIndex, tableData)"></i>
                        <i class="fa fa-times-circle fa-lg row-delete ml-1 mr-1" @click.stop="deleteRow(tableDataIndex, tableData.bid)"></i>
                    </td>
                </table-row>
            </template>
        </datatable>
        <modal
            centered-display
            v-if="modal.visible"
            :class="form.mode === 'view' ? 'modal--no-footer' : ''"
            @close="modal.visible = false">
            <template slot="header">
                {{ modal.title }}
            </template>
            <template slot="content">
                <!--
                <form-field
                    class="form-group"
                    :error="errors.device_type">
                    <label>{{ $t('label.device_type') }} <span class="required">*</span></label>
                    <select
                        class="form-control"
                        v-model="form.values.device_type"
                        @change="errors.device_type = ''">
                        <option value="" selected hidden disabled>{{ $t('label.select_device_type') }}</option>
                        <option :value="pos.sirius_pos">{{ $t('label.sirius_pos') }}</option>
                        <option :value="pos.pda">{{ $t('label.pda') }}</option>
                        <option :value="pos.kiosk">{{ $t('label.kiosk') }}</option>
                        <option :value="pos.qr_mobile">{{ $t('label.qr_mobile') }}</option>
                        <option :value="pos.kds">{{ $t('label.kds') }}</option>
                    </select>
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.device_name">
                    <label>{{ $t('label.device_name') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.name"
                        @keypress="errors.device_name = ''"
                        :placeholder="$t('label.enter_value', { value: $t('label.device_name') })">
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.ip_address">
                    <label>{{ $t('label.ip_address') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.ip_address"
                        @keypress="errors.ip_address = ''"
                        :placeholder="$t('label.enter_value', { value: $t('label.ip_address') })">
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.api_endpoint">
                    <label>{{ $t('label.api_endpoint') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.api_endpoint"
                        @keypress="errors.api_endpoint = ''"
                        :placeholder="$t('label.enter_value', { value: $t('label.api_endpoint') })">
                </form-field>
                <form-field
                    class="form-group"
                    :error="errors.token">
                    <label>{{ $t('label.token') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.token"
                        @keypress="errors.token = ''"
                        :placeholder="$t('label.enter_value', { value: $t('label.token') })">
                </form-field>
                --->
                <form-field v-if="form.values.device_type === pos.kds"
                    class="form-group"
                    :error="errors.kitchen_station_bid">
                    <label>{{ $t('label.kitchen_station') }} <span class="required">*</span></label>
                    <v-select
                        class="v-select--hide-selected"
                        :clearable="false"
                        v-model="form.values.kitchen_station"
                        :options="selections.kitchen_station.options"
                        @option:selected="onSelectedKitchenStation($event)">
                        >
                    </v-select>
                </form-field>
                <form-field v-if="form.values.device_type === pos.sirius_pos"
                    class="form-group"
                    :error="errors.background_process_priority">
                    <label>{{ $t('label.background_process_priority') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control text-left"
                        v-model.number="form.values.background_process_priority"
                        v-mask="{
                            alias: 'integer',
                            autoGroup: true,
                            digitsOptional: false,
                            showMaskOnHover: false,
                            showMaskOnFocus : false,
                        }"
                        @keypress="errors.background_process_priority = ''"
                        :placeholder="$t('label.enter_value', { value: $t('label.background_process_priority') })">
                </form-field>
                <form-field
                    class="form-group">
                    <label>{{ $t('label.status') }}</label>
                    <select
                        class="form-control"
                        v-model="form.values.status">
                        <option :value="status.active">{{ $t('label.active') }}</option>
                        <option :value="status.inactive">{{ $t('label.inactive') }}</option>
                    </select>
                </form-field>
            </template>
            <template slot="footer">
                <div align="right" v-if="form.mode !== 'view'">
                    <button class="button button--primary" @click="save">{{ $t('label.save') }}</button>
                    <button class="button button--red" @click="modal.visible = false">{{ $t('label.close') }}</button>
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
    import FormField from '../../../components/Containers/FormField.vue';
import Datatable from '../../../components/Datatable2/Datatable.vue';
import TableRow from '../../../components/Datatable2/TableRow.vue';
import DialogBox from '../../../components/Message/DialogBox.vue';
import Modal from '../../../components/Modal/Modal.vue';
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
        data() {
            return {
                status: {
                    active: STATUS.ACTIVE,
                    inactive: STATUS.INACTIVE,
                },
                pos: {
                    sirius_pos: POS.SIRIUS_POS,
                    pda: POS.PDA,
                    kiosk: POS.KIOSK,
                    qr_mobile: POS.QR_MOBILE,
                    kds: POS.KDS,
                    queueing: POS.QUEUEING,
                    ecommerce: POS.ECOMMERCE,
                },
                errors: {
                    device_type: '',
                    device_name: '',
                    ip_address: '',
                    api_endpoint: '',
                    token: '',
                    kitchen_station: {},
                    kitchen_station_bid: '',
                    background_process_priority: '',
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
                    title: this.$t('label.new_device_settings'),
                    visible: false
                },
                form: {
                    index: 0,
                    mode: 'create',
                    values: {
                        bid: '',
                        device_uid: '',
                        device_type: '',
                        name: '',
                        ip_address: '',
                        api_endpoint: '',
                        token: '',
                        kitchen_station: {},
                        kitchen_station_bid: '',
                        status: STATUS.ACTIVE,
                        background_process_priority: 1,
                    },
                    kitchen_stations: 1,
                },
                table: {
                    header: [
                        {
                            name: "device_type",
                            label: this.$t('label.device_type'),
                            width: '120'
                        },
                        {
                            name: "name",
                            label: this.$t('label.device_name'),
                            width: '200'
                        },
                        {
                            name: "ip_address",
                            label: this.$t('label.ip_address'),
                            width: '150'
                        },
                        /*
                        {
                            name: "api_endpoint",
                            label: this.$t('label.api_endpoint'),
                            width: '150'
                        },
                        {
                            name: "token",
                            label: this.$t('label.token'),
                            width: '150'
                        },
                        */
                        {
                            name: "priority",
                            label: this.$t('label.background_process_priority'),
                            width: '210'
                        },
                        {
                            name: "kitchen_station_name",
                            label: this.$t('label.kitchen_station_name'),
                            width: '180'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
                            width: '100'
                        },
                        {
                            name: "actions",
                            label: '',
                            width: '50'
                        }
                    ],
                    settings: {
                        itemsPerPage: 25,
                        withShowAll: true,
                        withRowNumbers: true,
                        hasEdit: false,
                        hasDelete: false,
                    }
                },

                devices: {
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
                },
                selections: {
                    kitchen_station: {
                        options: []
                    },
                }
            }
        },

        created() {
            this.setItemsPerPage();
        },

        mounted() {
            this.paginate();
            this.getKitchenStations();
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

                return await axios.get('device-settings/list', {
                    params: {
                        filters: filters,
                        page: page,
                        itemsPerPage: itemsPerPage !== null
                            ? itemsPerPage
                            : self.devices.settings.itemsPerPage,
                        isTablePaginate: (data !== null) ? true : false,
                    }
                })
                .then(response => {
                   this.devices.values.data = response.data.data.data;
                   this.devices.values.meta = response.data.data.meta;

                   return true;
                })
            },

            async showAll(emitted, data = null) {
                let itemsPerPage = emitted.status ? emitted.table.meta.pagination.total : this.table.settings.itemsPerPage;
                let filters = emitted.table.filters;
                let hasResponse = await this.paginate(1, data, filters, itemsPerPage);

                if (hasResponse) {
                    this.devices.settings.itemsPerPage = itemsPerPage;
                    emitted.done(emitted.status);
                }

                this.show_all = emitted.status;
            },


            create() {
                this.clearForm();
                this.modal.title = this.$t('label.new_device_settings');
                this.modal.visible = true;
            },

            editRow(index, data) {
                this.clearForm();

                this.form.index = index;
                this.form.mode = 'update';
                this.modal.title = this.$t('label.edit_device_settings');

                this.form.values.bid = data.bid;
                this.form.values.device_uid = data.device_uid;
                this.form.values.device_type = data.device_type;
                this.form.values.name = data.name;
                this.form.values.ip_address = data.ip_address;
                this.form.values.api_endpoint = data.api_endpoint;
                this.form.values.token = data.token;
                this.form.values.background_process_priority = data.background_process_priority;
                this.form.values.kitchen_station_bid = data.kitchen_station_bid;
                this.form.values.status = data.status;

                this.modal.visible = true;
            },

            deleteRow(index, bid) {
                let self = this;
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                this.dialog.ok.function = () => {
                    axios.delete('device-settings/delete', {
                        data: {
                            bid: bid
                        }
                    })
                    .then(response => {
                        self.devices.values.data.splice(index, 1);
                        self.dialog.status = 'success';
                        self.dialog.message = response.data.message;
                        self.dialog.ok.function = () => {
                            self.dialog.visible = false;
                        };
                    })
                }

                this.dialog.cancel.function = () => {
                    this.dialog.visible = false;
                };
            },

            save() {
                let method = this.form.mode === 'create' ? 'POST' : 'PATCH',
                    url = this.form.mode === 'create' ? 'device-settings/store' : 'device-settings/update',
                    data = this.form.values,
                    index = this.form.index,
                    self = this;

                return axios(url, {
                    method: method,
                    url: url,
                    data: data,
                }).then(function(response) {

                    if (self.form.mode === 'create') {
                        self.devices.values.data.push(response.data.data);
                        self.dialog.message = self.$t('success.success_successfully_created', { value: self.$t('label.device') });
                    } else {
                        self.devices.values.data[index] = {...self.form.values};
                        self.dialog.message = self.$t('success.success_successfully_updated', { value: self.$t('label.device') });
                    }

                    self.paginate();
                    self.clearForm();
                    self.dialog.visible = true;
                    self.dialog.status = 'success';
                    self.dialog.ok.function = () => {
                        self.dialog.visible = false;
                        self.modal.visible = false;
                    };

                    return true;
                })
                .catch(error => {
                    if (error.response != undefined) {
                        self.errors.device_type = error.response.data.errors.device_type ? error.response.data.errors.device_type[0] : '';
                        self.errors.device_name = error.response.data.errors.name ? error.response.data.errors.name[0] : '';
                        self.errors.ip_address = error.response.data.errors.ip_address ? error.response.data.errors.ip_address[0] : '';
                        self.errors.api_endpoint = error.response.data.errors.api_endpoint ? error.response.data.errors.api_endpoint[0] : '';
                        self.errors.token = error.response.data.errors.token ? error.response.data.errors.token[0] : '';
                        self.errors.kitchen_station_bid = error.response.data.errors.kitchen_station_bid ? error.response.data.errors.kitchen_station_bid[0] : '';
                        self.errors.background_process_priority = error.response.data.errors.background_process_priority ? error.response.data.errors.background_process_priority[0] : '';
                        self.$forceUpdate();
                    }
                });
            },

            clearFormErrors() {
                this.errors.device_type = '';
                this.errors.device_name = '';
                this.errors.ip_address = '';
                this.errors.api_endpoint = '';
                this.errors.token = '';
                this.errors.kitchen_station_bid = '';
                this.errors.background_process_priority = '';
            },

            clearForm() {
                this.clearFormErrors();

                this.form.index = 0;
                this.form.mode = 'create';

                this.form.values.device_uid = '';
                this.form.values.device_type = '';
                this.form.values.name = '';
                this.form.values.ip_address = '';
                this.form.values.api_endpoint = '';
                this.form.values.token = '';
                this.form.values.kitchen_station_bid = '';
                this.form.values.background_process_priority = '';
            },

            onSelectedKitchenStation(event) {
                console.log(event);
                this.errors.kitchen_station_bid = '';
                this.form.values.kitchen_station = event;
                this.form.values.kitchen_station_bid = event.value;
            },

            async getKitchenStations() {
                let self = this;

                await axios.get('device-settings/kitchen-stations', {
                    params: {}
                }).then(function(response) {
                    self.$set(self.selections.kitchen_station, 'options', response.data.data.data);
                });
            },
        }
    }
</script>

<style lang="scss" scoped>
   .endpoint {
        &-header {
            color: #001e07;
            font-weight: bold;
            &:hover {
                color: darken(#00aa27, 5%);
                cursor: pointer;
            }
        }
        &-description {
            color: #979797;
            &:hover {
                color: darken(#1178f7, 5%);
                cursor: pointer;
            }
        }
    }
    .status {

        &--green {
            color: green;
        }
        &--red {
            color: red;
        }
        &--gray {
            color: rgb(152, 152, 152);
        }
    }
    .device-type {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 4px;
        color: #fff;
        text-align: center;
        border: 1px transparent solid;
        text-transform: uppercase;
        width: 100%;
        &--box {
            width: 100%;
            padding: 5px 8px;
            border-radius: 0px;
            color: #fff;
            text-transform: uppercase;
            font-size: 14px;
        }
        &--pos {
            background-color: #155c61;
            border-color: darken(#155c61, 15%);
        }
        &--pda {
            background-color: #7f8c8d;
            border-color: darken(#7f8c8d, 15%);
        }
        &--mobile {
            background-color: #ff7686;
            border-color: darken(#ff7686, 15%);
        }
        &--kiosk {
            background-color: #f39c12;
            border-color: darken(#f39c12, 15%);
        }
        &--kds {
            background-color: #0051ff;
            border-color: darken(#0051ff, 4%);
        }
        &--queueing {
            background-color: #8e4fb0;
            border-color: darken(#8e4fb0, 4%);
        }
        &--ecommerce {
            background-color: #f73803;
            border-color: darken(#f73803, 4%);
        }
    }
    .device {
        &--ip {
            color: #001e07;
            font-weight: bold;
            &:hover {
                color: darken(#00aa27, 5%);
                cursor: pointer;
            }
        }
        &--uid {
            color: #979797;
            &:hover {
                color: darken(#1178f7, 5%);
                cursor: pointer;
            }
        }
    }
</style>
