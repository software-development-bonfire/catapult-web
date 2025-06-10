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
        <div class="box-row box-row--white d-flex justify-content-between position-relative">
            <div class="form-inline">
                <div class="form-group my-2 mx-3">
                    <label>{{ $t('label.date') }}:&nbsp;&nbsp;</label>
                    <div class="input-group">
                        <date-picker
                            v-model="filters.date"
                            value-type="format"
                            format="YYYY-MM-DD"
                            :clearable="false"
                            :editable="false"
                            >
                        </date-picker>
                    </div>
                    <popper
                        class="ml-2"
                        trigger="hover"
                        :options="{ placement: 'right' }">
                        <div class="popper popover--modified popover--modified-default popover--override" width="80px">
                            <div class="text-left">
                                <b class="text-uppercase">{{ $t('label.note') }}:</b>
                                <br><span class="text-secondary">{{ $t('message.select_date_to_show_errors') }}</span>
                                <br><span class="text-secondary">{{ $t('message.date_today_will_be_set_if_blank') }}</span>
                            </div>
                        </div>
                        <i slot="reference" class="fa fa-question-circle fa-lg icon-gray"></i>
                    </popper>
                    <label>{{ $t('label.ecommerce') }}:&nbsp;&nbsp;</label>
                    <button @click="toggle">{{ isOn ? 'ON' : 'OFF' }}</button>
                </div>
            </div>  
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
                        <span v-text="tableData.date"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.log_type"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.filename"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.message"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <button class="button button--light w-100" @click="openDetails(tableData.details)">{{ $t('label.view_details') }}</button>
                    </td>
                </table-row>
            </template>
        </datatable>
        <modal
            class="modal--no-footer"
            width="700px"
            v-if="modal.detail.visible"
            @close="modal.detail.visible = false">
            <template slot="header">
                {{ $t('label.error_log_details') }}
            </template>
            <template slot="content">
                <div class="
                    table-design
                    table-design--default
                    table-design--bordered
                    table-design--full-width">
                    <table>
                        <thead>
                            <th align="center">{{ $t('label.error_type') }}</th>
                            <th align="center">{{ $t('label.description') }}</th>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(tableData, tableDataIndex) in modal.detail.data"
                                :key="tableDataIndex">
                                <td align="center">
                                    <span v-text="tableData.error_type"></span>
                                </td>
                                <td align="left">
                                    <span v-text="tableData.description"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div align="right" class="mt-4">
                    <button class="button button--light" @click="modal.detail.visible = false">{{ $t('label.close') }}</button>
                </div>
            </template>
        </modal>
    </div>
</template>

<style lang="scss" scoped>
    .top-navigation {
        border-bottom: 1px #ced6e0 solid;
        background-color: #fff;
        padding: 5px 15px;
        position: fixed;
        width: 100%;
        z-index: 1;

        .navbar-brand {
            padding: 0px;
        }

        .dropdown-item.active,
        .dropdown-item:active {
            background-color: #DE0900;
        }

        .user-menu {
            display: flex;
            align-items: center;
        }

        .navbar-nav .nav-link {
            color: #212529;

            &:hover {
                color: darken(#212529, 10%);
            }
        }

        .active-user {
            font-size: 16px;

            i {
                margin-left: 5px;
            }
        }
    }

    .sidebar-navigation {
        position: fixed;
        width: 18%;
        margin-top: 62px;
        border: 1px #ced6e0 solid;
        bottom: 0;
        top: 0;
        background: #fff;
        overflow: auto;
    }

    .main-content {
        margin-top: 62px;
        margin-left: 18%;
        margin-bottom: 43px;
        border: 1px red solid;
        position: fixed;
        top: 0;
        width: 82%;
        bottom: 0;
        overflow: auto;
    }

    .footer-panel {
        position: fixed;
        padding: 10px;
        margin-left: 18%;
        bottom: 0;
        background-color: #fff;
        width: 82%;
        text-align: right;
    }
</style>

<script>
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import Datatable from '../components/Datatable2/Datatable.vue';
    import TableRow from '../components/Datatable2/TableRow.vue';
    import DialogBox from '../components/Message/DialogBox.vue';
    import Modal from '../components/Modal/Modal.vue';
    import DateUtilities from '../mixins/DateUtilities.vue';
    import Util from '../mixins/Util.vue';

    export default {
        components: {
            Datatable,
            TableRow,
            DialogBox,
            Modal,
            Popper,
            DatePicker
        },
        mixins: [ Util, DateUtilities ],
        data() {
            return {
                isOn: true,
                base_url: process.env.MIX_CDIS_URL,
                app_key: process.env.MIX_CDIS_KEY,
                branch_code: process.env.MIX_CDIS_BRANCH_CODE,
                filters : {
                    date: new Date(),
                },
                modal: {
                    detail: {
                        visible: false,
                        data: {},
                    }
                },
                summary: [],
                errors: {},
                table: {
                    header: [
                        {
                            name: "date",
                            label: this.$t('label.date'),
                            width: '180'
                        },
                        {
                            name: "log_type",
                            label: this.$t('label.log_type'),
                            width: '200'
                        },
                        {
                            name: "filename",
                            label: this.$t('label.file'),
                            width: '150'
                        },
                        {
                            name: "message",
                            label: this.$t('label.message'),
                            width: '130'
                        },
                        {
                            name: "details",
                            label: this.$t('label.action'),
                            width: '125'
                        },
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
                        withRowNumbers: false,
                        withPagination: true
                    }
                },
            }
        },
        created() {
            this.filters.date = this.getCurrentDate('YYYY-MM-DD');
        },
        mounted() {
            this.isBranchAvailable();
        },
        methods: {

            async isBranchAvailable() {
                let self = this;
                 this.$root.processing(true);
                let url = this.base_url + '/api/catapult/v2/branch/available'
                let params = {
                    app_key : this.app_key,
                    branch: {
                        branch_code: this.$store.state.branchCode,
                    }
                }

                return await axios.post(url, params, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
                }).then(function(response){
                    self.$root.processing(false);
                    self.isOn = response.data.data.is_available;
                }).catch(function(error){
                    self.$root.processing(false);
                })

            },

            async toggle () {
                let self = this;
                this.isOn = !this.isOn;
                this.$root.processing(true);
                let url = this.base_url + '/api/catapult/v2/branch/availability'
                let params = {
                    app_key : this.app_key,
                    branch: {
                        branch_code: this.$store.state.branchCode,
                        branch_status: this.isOn
                    }
                }

                return await axios.post(url, params, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
                }).then(function(response){
                    self.$root.processing(false);
                }).catch(function(error){
                    self.$root.processing(false);
                })
            },

            async paginate(page = 1) {
                let self = this;
                this.errors = {};
                this.$root.processing(true);

                return await axios.get('/dashboard/summary?page='+page, {
                    params: {
                        filters: this.filters,
                        page: page,
                        itemsPerPage: this.table.settings.itemsPerPage
                    },
                }).then(function(response) {
                    let data = response.data.data;
                    self.table.values = data.logs;
                    self.summary = data.summary.data;
                    self.$root.processing(false);
                    return true;
                }).catch(function (error) {
                    self.$root.processing(false);
                });

            },
            openDetails(data) {
                this.modal.detail.data = data;
                this.modal.detail.visible = true;
            },
        },
        watch: {
            'filters.date': function (value) {
                this.paginate();
            },
        }
    }
</script>

<style lang="scss" scoped>
    .summary_info {
        margin-bottom: 15px;
    }

    .summary_info .wrap {
        background: #ffffff;
        box-shadow: 2px 10px 12px rgba(0, 0, 0, 0.1);
        border: 1px lighten($color: #ccc, $amount: 2) solid;
        border-radius: 7px;
        text-align: center;
        position: relative;
        overflow: hidden;
        padding: 10px;
        height: 100%;

        &:hover {
            cursor: pointer;
            background-color: #eee;
        }

        span {
            display: block;
        }
    }

    .summary-info-header {
        color: #444444;
        margin-top: 5px;
        font-size: 1.2em;
    }

    .summary-info-description {
        color: #999289;
        font-size: 1em;
    }

    .summary-info-count {
        font-weight: 600;
        font-size: 2.5em;
        line-height: 64px;
        color: #323c43;
    }

    .summary_info .wrap:after {
        display: block;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 10px;
        content: "";
    }

    .summary_info:nth-child(1) .wrap:after {
        background: linear-gradient(82.59deg, #00c48c 0%, #00a173 100%);
    }

    .summary_info:nth-child(2) .wrap:after {
        background: linear-gradient(69.83deg, #00c48c 0%, #0084f4 100%);
    }
    .summary_info:nth-child(3) .wrap:after {
        background: linear-gradient(81.67deg, #0084f4 0%, #1a4da2 100%);
    }

    .summary_info:nth-child(4) .wrap:after {
        background: linear-gradient(81.67deg, #1f5dc5 0%, #eb051c 100%);
    }

    .summary_info:nth-child(5) .wrap:after {
        background: linear-gradient(69.83deg, #eb051c 0%, #e5a505 100%);
    }

    .summary_info:nth-child(6) .wrap:after {
        background: linear-gradient(81.67deg, #e5a505 0%, #6f1fc5 100%);
    }

    .icon-gray {
        color: #bababa;
    }
</style>
