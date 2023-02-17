<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            <button class="button button--light module-action-button" @click="create">{{ $t('label.add_new') }}</button>
        </div>
        <datatable
            class="datatable--hoverable"
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
                    v-on:dbl-row-click="openDetail(tableData, tableDataIndex)">
                    <td class="datatable-cell">
                        <span v-text="tableData.id"></span>
                    </td>
                    <td class="datatable-cell">
                        <span v-text="tableData.username"></span>
                    </td>
                    <td class="datatable-cell" align="center">
                        <span v-text="tableData.name"></span>
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
            width="500px"
            v-if="modal.detail.visible"
            @close="modal.detail.visible = false">
            <template slot="header">
                {{ $t('label.user_account_detail') }}
            </template>
            <template slot="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="form-group">
                                <label>{{ $t('label.id') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.values.id"
                                    disabled>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="form-group">
                                <label>{{ $t('label.status') }}</label>
                                <select class="form-control" v-model="form.values.status">
                                    <option :value="1">{{ $t('label.active') }}</option>
                                    <option :value="0">{{ $t('label.inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6">
                            <form-field
                                class="form-group">
                                <label>{{ $t('label.username') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.hasOwnProperty('username') && form.errors.username !== '' }"
                                    @keypress="form.errors.username = ''"
                                    v-model="form.values.username">
                                <label class="text-danger error-message m-0" v-if="form.errors.hasOwnProperty('username')">
                                    {{form.errors.username[0]}}
                                </label>
                            </form-field>
                        </div>
                        <div class="col-xl-6">
                            <form-field
                                class="form-group">
                                <label>{{ $t('label.password') }}</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.hasOwnProperty('password') && form.errors.password !== '' }"
                                    @keypress="form.errors.password = ''"
                                    v-model="form.values.password">
                                <label class="text-danger error-message m-0" v-if="form.errors.hasOwnProperty('password')">
                                    {{form.errors.password[0]}}
                                </label>
                            </form-field>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6">
                            <form-field
                                class="form-group">
                                <label>{{ $t('label.name') }}</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.hasOwnProperty('name') && form.errors.name !== '' }"
                                    @keypress="form.errors.name = ''"
                                    v-model="form.values.name">
                                <label class="text-danger error-message m-0" v-if="form.errors.hasOwnProperty('name')">
                                    {{form.errors.name[0]}}
                                </label>
                            </form-field>
                        </div>
                        <div class="col-xl-6">
                            <form-field
                                class="form-group">
                                    <label>{{ $t('label.retype_password') }}</label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.hasOwnProperty('retype_password') && form.errors.retype_password !== '' }"
                                        @keypress="form.errors.retype_password = ''"
                                        v-model="form.values.retype_password">
                                    <label class="text-danger error-message m-0" v-if="form.errors.hasOwnProperty('retype_password')">
                                        {{form.errors.retype_password[0]}}
                                    </label>
                            </form-field>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="form-group">
                    <h5>{{ $t('label.module_permission') }}</h5>
                    <div class="ml-4">
                        <div v-for="(data, index) in permission" :key="index">
                            <label class="radio-checkbox">
                                <input type="checkbox" :value="getPermissionCode(data.code_name)" v-model="data.value">
                                <span>{{data.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <label class="text-danger error-message m-0" v-if="form.errors.hasOwnProperty('permission')">
                    {{ form.errors.permission[0] }}
                </label>
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
            this.permissionCodes()
        },
        data() {
            return {
                permission: [],
                permissions: [
                    {
                        name: this.$t('label.dashboard'),
                        code_name: 'view.dashboard',
                        value: null
                    },
                    {
                        name: this.$t('label.logs'),
                        code_name: 'view.logs',
                        value: null,
                    },
                    {
                        name: this.$t('label.user_account'),
                        code_name: 'view.user_account',
                        value: null,
                    }
                ],
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
                        id: '',
                        username: '',
                        name: '',
                        status: 1,
                        password: '',
                        retype_password: ''
                    },
                    errors: {
                        username: '',
                        name: '',
                        password: '',
                        retype_password: ''
                    }
                },
                table: {
                    header: [
                        {
                            name: "id",
                            label: this.$t('label.id'),
                            width: '180'
                        },
                        {
                            name: "username",
                            label: this.$t('label.username'),
                            width: '150'
                        },
                        {
                            name: "name",
                            label: this.$t('label.name'),
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
            permissionCodes() {
                this.permissions = this.permissions.map(element => {
                    return {
                        name: element.name,
                        value: element.value,
                        code_name: element.code_name,
                        code: this.getPermissionCode(element.code_name)
                    }
                })
            },
            paginate(page = 1) {
                axios.get('user'+'?page='+page, {
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
                this.permission = this.permissions
                this.clearFields();
                this.modal.detail.visible = true;
            },

            clearFields() {
                this.form.errors = {}
                this.form.mode = 'create';
                this.form.values = {
                    id: '',
                    username: '',
                    name: '',
                    status: 1,
                    password: '',
                    retype_password: ''
                };
            },

            openDetail(data, index) {
                this.form.mode = 'update';
                this.form.errors = {}
                var permission = [];
                this.permissions.forEach(element => {
                    if (data.permission.some(e => (element.code === e.code))) {
                        permission.push({
                            value: true,
                            code: element.code,
                            code_name: element.code_name,
                            name: element.name,
                        });
                    } else {
                        permission.push({
                            value: false,
                            code: element.code,
                            code_name: element.code_name,
                            name: element.name,
                        });
                    }

                });
                
                this.permission = permission
                this.form.values = {
                    mode: 'update',
                    index: index,
                    id: data.id,
                    bid: data.bid,
                    name: data.name,
                    username: data.username,
                    status: data.status,
                    password: data.password,
                    retype_password: data.retype_password,
                    permission: this.permission
                };

                this.modal.detail.visible = true;
            },

            save() {
                if (this.form.mode === 'create') {
                    var permission = [];
                    this.permissions.forEach(element => {
                        if (element.value === true) {
                            permission.push({
                                code: element.code
                            })
                        }
                    })
                    var config = {
                        mode: this.form.mode,
                        bid: this.form.values.bid,
                        name: this.form.values.name,
                        username: this.form.values.username,
                        password: this.form.values.password,
                        status: this.form.values.status,
                        password: this.form.values.password,
                        retype_password: this.form.values.retype_password,
                        permission: permission
                    }

                    axios.post('user', config)
                        .then(response => {
                            this.paginate();
                            this.form.errors = {};
                            this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_created', { value: this.$t('label.user_account') });
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                                this.modal.detail.visible = false;
                            };
                        }).catch(error => {
                            this.form.errors = error.response.data.errors;
                        })
                } else {
                    var permission = [];
                    this.permission.forEach(element => {
                        if (element.value === true) {
                            permission.push({
                                code: element.code
                            })
                        }
                    })
                    var config = {
                        mode: this.form.mode,
                        bid: this.form.values.bid,
                        name: this.form.values.name,
                        username: this.form.values.username,
                        password: this.form.values.password,
                        status: this.form.values.status,
                        password: this.form.values.password,
                        retype_password: this.form.values.retype_password,
                        permission: permission
                    }
                    
                    axios.put('user/'+this.form.values.bid, config)
                        .then(response => {
                            this.paginate()
                            this.dialog.visible = true;
                            this.dialog.status = 'success';
                            this.dialog.message = this.$t('success.successfully_updated', { value: this.$t('label.user_account') });
                            this.dialog.ok.function = () => {
                                this.dialog.visible = false;
                                this.modal.detail.visible = false;
                            };

                        }).catch(error => {
                            this.form.errors = error.response.data.errors;
                        })
                }
            },

            deleteRow(data) {
                
                this.dialog.visible = true;
                this.dialog.status = 'confirm';
                this.dialog.message = this.$t('message.do_you_want_to_remove_this_data');
                this.dialog.ok.function = () => {

                axios.delete(`user/${data.values.bid}`)
                    .then(response => {
                        this.table.values.data.splice(data.rowIndex, 1);
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
