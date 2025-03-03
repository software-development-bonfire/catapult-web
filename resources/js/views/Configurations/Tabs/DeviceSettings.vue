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
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.device_type === pos.sirius_pos ? $t('label.sirius_pos')
                            : tableData.device_type === pos.pda ? $t('label.pda')
                            : tableData.device_type === pos.kiosk ? $t('label.kiosk')
                            : ''">
                        </span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.name"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.ip_address"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.api_endpoint"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.token"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableDataIndex + 1"></span>
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
                <form-field
                    class="form-group"
                    :error="errors.process_priority">
                    <label>{{ $t('label.background_process_priority') }} <span class="required">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        v-model="form.values.process_priority"
                        @keypress="errors.process_priority = ''"
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
                <div align="center" v-if="form.mode !== 'view'">
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
                },
                errors: {
                    device_type: '',
                    device_name: '',
                    ip_address: '',
                    api_endpoint: '',
                    token: '',
                    process_priority: 1,
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
                        device_type: '',
                        name: '',
                        ip_address: '',
                        api_endpoint: '',
                        token: '',
                        status: STATUS.ACTIVE,
                        process_priority: 1,
                    },
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
                        {
                            name: "priority",
                            label: this.$t('label.background_process_priority'),
                            width: '210'
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
                this.form.values.device_type = data.device_type;
                this.form.values.name = data.name;
                this.form.values.ip_address = data.ip_address;
                this.form.values.api_endpoint = data.api_endpoint;
                this.form.values.token = data.token;
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
            },

            clearForm() {
                this.clearFormErrors();

                this.form.index = 0;
                this.form.mode = 'create';

                this.form.values.device_type = '';
                this.form.values.name = '';
                this.form.values.ip_address = '';
                this.form.values.api_endpoint = '';
                this.form.values.token = '';
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
</style>
