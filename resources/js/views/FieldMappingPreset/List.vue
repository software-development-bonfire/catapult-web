<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            <button class="button button--light module-action-button" @click="create">{{ $t('label.add_new') }}</button>
        </div>
        <div class="container-fluid">
            <div class="row mt-2">
                <div class="col-xl-3">
                    <div class="form-group">
                        <b>{{ $t('label.mapping_type') }}</b>
                        <select class="form-control" v-model="filters.mapping_type">
                            <option value="">{{ $t('label.all') }}</option>
                            <option :value="1">{{ $t('label.cdis_to_pos') }}</option>
                            <option :value="2">{{ $t('label.pos_to_cdis') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="form-group">
                        <b>{{ $t('label.status') }}</b>
                        <select class="form-control" v-model="filters.status">
                            <option value="">{{ $t('label.all') }}</option>
                            <option :value="1">{{ $t('label.active') }}</option>
                            <option :value="0">{{ $t('label.inactive') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-xl-3">
                    <br>
                    <button class="button button--light" @click="paginate">{{ $t('label.search') }}</button>
                </div>
            </div>
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
                    v-on:dbl-row-click="openDetail(tableData)">
                    <td class="datatable-cell" align="center">
                        <span
                            class="status_label"
                            :class="tableData.mapping_type == 1 ? 'status_label--cdis_to_pos' : 'status_label--pos_to_cdis'"
                            v-text="
                                tableData.mapping_type === 1 ? $t('label.cdis_to_pos')
                                : tableData.mapping_type === 2 ? $t('label.pos_to_cdis')
                                : ''">
                        </span>
                    </td>
                    <td class="datatable-cell">
                       <span v-text="tableData.data_entry"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.preset_name"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.total_field_entries"></span>
                    </td>
                    <td class="datatable-cell">
                        <span
                            class="status_label"
                            :class="tableData.status ? 'status_label--active' : 'status_label--inactive'"
                            v-text="tableData.status ? $t('label.active') : $t('label.inactive')">
                        </span>
                    </td>
                    <td class="datatable-cell">
                        <span>{{ tableData.last_modified }}</span>
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
    import DialogBox from '../../components/Message/DialogBox.vue';
    import Datatable from '../../components/Datatable2/Datatable.vue';
    import TableRow from '../../components/Datatable2/TableRow.vue';

    export default {
        components: {
            DialogBox,
            Datatable,
            TableRow
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
                filters: {
                    mapping_type: '',
                    status: ''
                },
                table: {
                    header: [
                        {
                            name: "mapping_type",
                            label: this.$t('label.mapping_type'),
                            width: '180'
                        },
                        {
                            name: "data_entry",
                            label: this.$t('label.data_entry'),
                            width: '180'
                        },
                        {
                            name: "preset_name",
                            label: this.$t('label.preset_name'),
                            width: '250'
                        },
                        {
                            name: "total_field_entries",
                            label: this.$t('label.total_field_entries'),
                            width: '150'
                        },
                        {
                            name: "status",
                            label: this.$t('label.status'),
                            width: '90'
                        },
                        {
                            name: "last_modified",
                            label: this.$t('label.last_modified'),
                            width: '150'
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
                },
            }
        },
        methods: {
            paginate(page = 1) {
                axios.get('/field-mapping-preset/list', {
                    params: {
                        page: page,
                        mapping_type: this.filters.mapping_type,
                        status: this.filters.status,
                        itemsPerPage: this.table.settings.itemsPerPage
                    }
                }).then(response => {
                    this.table.values.data = response.data.data.data;
                    this.table.values.meta  = response.data.data.meta;
                })
            },

            create() {
                window.open('/field-mapping-preset/detail', '_self');
            },

            openDetail(data) {
                console.log(data);
                window.open('/field-mapping-preset/detail?' + QueryString.stringify({
                    bid: data.bid
                }), '_self');
            },

            deleteRow(index) {
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                this.dialog.ok.function = () => {
                    axios.delete(`field-mapping-preset/${index.values.bid}`)
                        .then(response => {
                            this.table.values.data.splice(index, 1);
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
