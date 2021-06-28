<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            <button class="button button--light module-action-button" @click="create">{{ $t('label.add_new') }}</button>
        </div>
        <datatable
            class="
                datatable--full-width
                datatable--hoverable"
            :header-fields="table.header"
            :settings="table.settings"
            :table="table.values"
            v-on:paginate="paginate"
            v-on:delete-row="deleteRow">
            <template slot="content">
                <table-row
                    type="view"
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex"
                    v-on:row-click="openDetail(tableData, tableDataIndex)">
                    <td class="datatable-cell">
                        <span v-text="tableData.name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.checking_interval"></span>
                    </td>
                    <td class="datatable-cell">
                        <span
                            v-text="tableData.syncing_type === 1 ? $t('label.catapult_to_cdis')
                                : tableData.syncing_type === 2 ? $t('label.cdis_to_catapult')
                                : ''">
                        </span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.start_time"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.status ? $t('label.active') : $t('label.inactive')"></span>
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
        <modal
            class="modal--no-footer"
            width="400px"
            v-if="modal.detail.visible"
            @close="modal.detail.visible = false">
            <template slot="header">
                {{ $t('label.sync_interval_setting_detail') }}
            </template>
            <template slot="content">
                <div class="form-group">
                    <form-field
                        :error="form.errors.name">
                        <label>{{ $t('label.sync_interval_name') }}</label>
                        <input
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.name !== '' }"
                            @keypress="form.errors.name = ''"
                            v-model="form.values.name">
                    </form-field>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.syncing_type') }}</label>
                    <select class="form-control" v-model="form.values.syncing_type">
                        <option :value="1">{{ $t('label.catapult_to_cdis') }}</option>
                        <option :value="2">{{ $t('label.cdis_to_catapult') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.checking_interval') }}</label>
                    <select
                        class="form-control"
                        v-model="form.values.checking_interval">
                        <option value="5 mins">5 mins</option>
                        <option value="10 mins">10 mins</option>
                        <option value="15 mins">15 mins</option>
                        <option value="30 mins">30 mins</option>
                        <option value="1 hr">1 hr</option>
                        <option value="End of Day">{{ $t('label.end_of_day') }}</option>
                    </select>
                </div>
                <div class="form-group" v-if="form.values.checking_interval === 'End of Day'">
                    <label>{{ $t('label.start_time') }}</label>
                    <div>
                        <date-picker
                            v-model="form.values.start_time"
                            :minute-step="30"
                            :clearable="false"
                            format="hh:mm a"
                            value-type="format"
                            type="time"
                            placeholder="hh:mm a"
                        ></date-picker>
                    </div>
                    <label class="text-danger error-message m-0" v-if="form.errors.hasOwnProperty('start_time')">
                        {{form.errors.start_time}}
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ $t('label.status') }}</label>
                    <select class="form-control" v-model="form.values.status">
                        <option :value="1">{{ $t('label.active') }}</option>
                        <option :value="0">{{ $t('label.inactive') }}</option>
                    </select>
                </div>
                <div align="center">
                    <button class="button button--light" @click="save">{{ $t('label.save') }}</button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';
    import Modal from '../../components/Modal/Modal.vue';
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import FormField from '../../components/Containers/FormField.vue';

    export default {
        components: {
            DialogBox,
            Datatable,
            TableRow,
            Modal,
            DatePicker,
            FormField
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
                    detail: {
                        visible: false
                    }
                },
                filters: {
                    mapping_type: '',
                    status: ''
                },
                form: {
                    mode: 'create',
                    values: {
                        name: '',
                        syncing_type: 1,
                        checking_interval: '5 mins',
                        start_time: '',
                        status: 1,
                    },
                    errors: {
                        name: ''
                    }
                },
                table: {
                    header: [
                        {
                            name: "name",
                            label: this.$t('label.sync_interval_name'),
                            width: '180'
                        },
                        {
                            name: "checking_interval",
                            label: this.$t('label.checking_interval'),
                            width: '150'
                        },
                        {
                            name: "syncing_type",
                            label: this.$t('label.syncing_type'),
                            width: '150'
                        },
                        {
                            name: "start_time",
                            label: this.$t('label.start_time'),
                            width: '150'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
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
                    settings: {
                        itemsPerPage: 10,
                        withRowNumbers: true,
                        hasDelete: true
                    }
                }
            }
        },
        methods: {
            paginate(page = 1) {
                axios.get('sync-interval-settings'+'?page='+page, {
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
                this.clearFields();
                this.modal.detail.visible = true;
            },

            clearFields() {
                this.form.mode = 'create';
                this.form.values = {
                    name: '',
                    syncing_type: 1,
                    checking_interval: '5 mins',
                    start_time: '',
                    status: 1,
                };
                this.form.errors.name = '';
                this.form.errors.start_time = '';
            },

            openDetail(data, index) {
                this.form.mode = 'update';

                this.form.values = {
                    index: index,
                    name: data.name,
                    syncing_type: data.syncing_type,
                    checking_interval: data.checking_interval,
                    start_time: data.start_time,
                    status: data.status
                };

                this.form.errors.name = '';
                this.form.errors.start_time = '';
                this.modal.detail.visible = true;
            },

            createData(config) {
                axios.post('sync-interval-settings', config)
                    .then(response => {
                        this.paginate();

                        this.dialog.visible = false;
                        this.modal.detail.visible = false;
                        this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_created', { value: this.$t('label.sync_interval_setting') });
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                                this.modal.detail.visible = false;
                            };
                    }).catch(error => {
                        this.dialog.visible = false;
                        if (error.response.data.errors.hasOwnProperty('name')) {
                            this.form.errors.name = error.response.data.errors.name[0];
                        }
                        if (error.response.data.errors.hasOwnProperty('start_time')) {
                            this.form.errors.start_time = error.response.data.errors.start_time[0];
                        }
                    })
            },

            save() {
                if (this.form.mode === 'create') {
                    var config = {
                        name: this.form.values.name,
                        syncing_type: this.form.values.syncing_type,
                        checking_interval: this.form.values.checking_interval,
                        start_time: this.form.values.start_time,
                        status: this.form.values.status
                    }
                    if (config.status === 1) {
                        this.dialog.visible = true;
                        this.dialog.status = 'confirm-yes-no';
                        this.dialog.message = 'Do you want to use it as default?' , 'Set as Default';
                        this.dialog.ok.function = () => {
                            this.createData(config);
                        };
                        this.dialog.cancel.function = () => {
                            config.status = 0;
                            this.createData(config);
                        };
                    } else {
                        this.createData(config)
                    }
                    this.form.errors.name = ''; 
                    this.form.errors.start_time = ''; 
                } else {
                    let index = this.form.values.index;
                    
                    var config = {
                        bid: this.table.values.data[index].bid,
                        name: this.form.values.name,
                        syncing_type: this.form.values.syncing_type,
                        checking_interval: this.form.values.checking_interval,
                        start_time: this.form.values.start_time,
                        status: this.form.values.status
                    };

                    axios.put(`sync-interval-settings/${config.bid}`, config)
                        .then(response => {
                            this.paginate();

                            this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.sync_interval_setting') });
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                                this.modal.detail.visible = false;
                            };
                        }).catch(error => {
                            if (error.response.data.errors.hasOwnProperty('name')) {
                                this.form.errors.name = error.response.data.errors.name[0];
                            }
                            if (error.response.data.errors.hasOwnProperty('start_time')) {
                                this.form.errors.start_time = error.response.data.errors.start_time[0];
                            }
                        })
                }
            },

            deleteRow(index) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm-yes-no';
                this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                this.dialog.ok.function = () => {

                    axios.delete(`sync-interval-settings/${index.values.bid}`)
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
        },
        watch: {
            'form.values.start_time': function(time) {
                this.form.values.start_time_format = moment(time, ["h:mm A"]).format("HH:mm") + ':00';
            },
            'form.values.checking_interval': function(value) {
                if (value !== 'End of Day') {
                    this.form.values.start_time = '';
                    this.form.values.start_time_format = '';
                }
            }
        }
    }
</script>

<style lang="scss" scoped>
</style>
