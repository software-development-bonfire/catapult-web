<template>
    <div class="tab-pane fade show active" id="system-logs" role="tabpanel" aria-labelledby="system-logs-tab">
        <div class="box-row box-row--white">
            <div class="container-fluid">
                <div class="row mt-3">
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label>{{ $t('label.users') }}:</label>
                            <v-select
                                class="v-select--hide-selected"
                                v-model="filters.users"
                                multiple
                                :options="selections.user.options">
                            </v-select>
                        </div>
                        <div class="form-group">
                            <button class="button button--light" @click="paginate">{{ $t('label.search') }}</button>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label>{{ $t('label.date_from') }}:</label>
                            <date-picker
                                v-model="filters.date_from"
                                format="MMMM DD, YYYY"
                                :default-value="new Date()"
                            ></date-picker>
                        </div>
                        <div class="form-group">
                            <label>{{ $t('label.date_to') }}:</label>
                            <date-picker
                                v-model="filters.date_to"
                                format="MMMM DD, YYYY"
                                :default-value="new Date()"
                            ></date-picker>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="form-group">
                            <label>{{ $t('label.module') }}:</label>
                            <v-select
                                class="v-select--hide-selected"
                                v-model="filters.modules"
                                multiple
                                :options="isSuperadmin ? selections.module.superadmin.options : selections.module.client.options">
                            </v-select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-row box-row--white p-3">
            <button class="button button--light">{{ $t('label.download_logs') }}</button>
        </div>
        <datatable
            :header-fields="table.header"
            :settings="table.settings"
            :table="table.values"
            v-on:paginate="paginate">
            <template slot="content">
                <table-row
                    v-for="(tableData, tableDataIndex) in table.values.data" :key="tableDataIndex"
                    :values="tableData"
                    :settings="table.settings"
                    :rowIndex="tableDataIndex">
                    <td class="datatable-cell">
                        <span v-text="tableData.initiator"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.timestamp"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.module_or_process"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.action"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.logs_description"></span>
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
    </div>
</template>

<script>
    import Datatable from '../../../components/Datatable2/Datatable.vue';
    import TableRow from '../../../components/Datatable2/TableRow.vue';
    import DialogBox from '../../../components/Message/DialogBox.vue';
    import Modal from '../../../components/Modal/Modal.vue';
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';

    export default {
        components: {
            Datatable,
            TableRow,
            DialogBox,
            Modal,
            Popper,
            DatePicker
        },
        mounted() {
            this.paginate();
        },
        computed: {
            isSuperadmin() {
                return this.$root.$children[0].$attrs.superadmin;
            }
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
                filters: {
                    users: [
                        {
                            label: 'ALL',
                            value: 'All',
                        },
                    ],
                    date_from: new Date(),
                    date_to: new Date(),
                    modules: [
                        {
                            label: 'ALL',
                            value: 'All',
                        },
                    ]
                },
                modal: {
                    file_errors: {
                        visible: false
                    }
                },
                selections: {
                    user: {
                        options: [
                            {
                                label: 'ALL',
                                value: 'All',
                            },
                            {
                                label: 'Juan Dela Cruz',
                                value: '10001',
                            },
                            {
                                label: 'Jane Doe',
                                value: '10002',
                            },
                            {
                                label: 'Anton Karpova',
                                value: '10003',
                            },
                        ]
                    },
                    module: {
                        superadmin: {
                            options: [
                                {
                                    label: this.$t('label.dashboard'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.licensing'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.remote_db_setup'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.catapult_db_setup'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.api_setup'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.field_mapping'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.syncing_interval'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.system_logs'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.error_logs'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.user_account'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.syncing_setup'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.field_mapping_setup'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.pos_to_cdis'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.cdis_to_pos'),
                                    value: '',
                                },
                            ]
                        },
                        client: {
                            options: [
                                {
                                    label: this.$t('label.dashboard'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.user_account'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.system_logs'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.error_logs'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.pos_to_cdis'),
                                    value: '',
                                },
                                {
                                    label: this.$t('label.cdis_to_pos'),
                                    value: '',
                                },
                            ]
                        }
                    }
                },
                table: {
                    header: [
                        {
                            name: "initiator",
                            label: this.$t('label.initiator'),
                            width: '200'
                        },
                        {
                            name: "timestamp",
                            label: this.$t('label.timestamp'),
                            width: '200'
                        },
                        {
                            name: "module_or_process",
                            label: this.$t('label.module_or_process'),
                            width: '200'
                        },
                        {
                            name: "action",
                            label: this.$t('label.action'),
                            width: '200'
                        },
                        {
                            name: "logs_description",
                            label: this.$t('label.logs_description'),
                            width: '400'
                        }
                    ],
                    values: {
                        data: [
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
                            },
                            {
                                initiator: 'system',
                                timestamp: 'May 3, 2021 04:00PM',
                                module_or_process: 'POS to CDIS',
                                action: 'Syncing',
                                logs_description: 'Transaction_1 has synced successfully.'
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
                        withRowNumbers: false,
                        withPagination: true
                    }
                }
            }
        },
        methods: {
            paginate(page = 1) {},
        }
    }
</script>

<style lang="scss" scoped>
    .email-address-input {
        width: 300px;
    }
    .popover--override {
        width: 300px;
        padding: 15px 10px;
    }
</style>