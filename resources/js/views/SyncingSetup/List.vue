<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            <button class="button button--light module-action-button" @click="save">Save</button>
        </div>
        <div class="overflow-auto p-4">
            <h3>Batch Syncing</h3>
            <label>
                NOTE: The entered value will apply on the 5 mins Interval setting ONLY. Limit for the other intervals will be automatically determined by the system multiplier<br>
                <span class="text-indent">(Ex. 5mins interval = 60 limit, 10mins interval = 120 limit and so on.)</span>
            </label>
            <div class="form-group">
                <label>Set the entry limit per batch syncing (POS TO CDIS PROCESS)</label>
                <input
                    type="text"
                    class="form-control w-25 text-left"
                    :class="{ 'is-invalid': errors.hasOwnProperty('pos_to_cdis_entry_limit') }"
                    v-model.number="form.values.pos_to_cdis_entry_limit"
                    v-mask="{
                        alias: 'integer',
                        autoGroup: true,
                        digitsOptional: false,
                        showMaskOnHover: false,
                        showMaskOnFocus : false,
                        min: 60,
                        max: 1000
                    }">
                <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('pos_to_cdis_entry_limit')">
                {{errors.pos_to_cdis_entry_limit[0]}}
                </label>
            </div>
            <div class="form-group">
                <label>Set the entry limit per batch syncing (CDIS TO POS PROCESS)</label>
                <input
                    type="text"
                    class="form-control w-25 text-left"
                    :class="{ 'is-invalid': errors.hasOwnProperty('cdis_to_pos_entry_limit') }"
                    v-model.number="form.values.cdis_to_pos_entry_limit"
                    v-mask="{
                        alias: 'integer',
                        autoGroup: true,
                        digitsOptional: false,
                        showMaskOnHover: false,
                        showMaskOnFocus : false,
                        min: 60,
                        max: 1000
                    }">
                <label class="text-danger error-message m-0" v-if="errors.hasOwnProperty('cdis_to_pos_entry_limit')">
                    {{errors.cdis_to_pos_entry_limit[0]}}
                </label>
            </div>
            <h3 class="mt-4">Syncing Prioritization</h3>
            <div class="w-50" align="center">
                <label>NOTE: Set the Syncing priority ranking of data entries from POS</label>
            </div>
            <div class="rankings">
                <draggable
                    :list="ranking"
                    class="list-group"
                    v-bind="dragOptions"
                    @start="dragging = true"
                    @end="dragging = false">
                    <div
                        class="list-group-item"
                        v-for="(rank, rankIndex) in ranking"
                        :key="rankIndex">
                        <span class="ranking-order mr-2">{{ rankIndex + 1 }}</span>
                        {{ rank.name }}
                    </div>
                </draggable>
            </div>
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

<script>
    import Draggable from 'vuedraggable';
    import DialogBox from '../../components/Message/DialogBox.vue';

    export default {
        components: {
            Draggable,
            DialogBox
        },
        mounted() {
            this.getData();
        },
        computed: {
            dragOptions() {
                return {
                    animation: 200,
                    group: "description",
                    disabled: false,
                    ghostClass: "ghost"
                };
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
                form: {
                    values: {
                        pos_to_cdis_entry_limit: 60,
                        cdis_to_pos_entry_limit: 60
                    }
                },
                ranking: [
                    { name: "Transaction" },
                    { name: "Zread" },
                    { name: "Cashier" },
                    { name: "Journal" },
                    { name: "Cash Drawer" },
                    { name: "Audit Trail" },
                ],
                errors: {}
            }
        },
        methods: {
            getData() {
                axios.get('configuration')
                .then(response => {
                    response.data.find(config => {
                        if (config.attribute === 'syncing_file') {
                            this.ranking = eval(response.data[0].value);
                        }
                        if (config.attribute === 'pos_to_cdis_entry_limit') {
                            this.form.values.pos_to_cdis_entry_limit = response.data[1].value;
                        }
                        if (config.attribute === 'cdis_to_pos_entry_limit') {
                            this.form.values.cdis_to_pos_entry_limit = response.data[2].value;
                        }
                    })
                })
            },
            save() {
                console.log(this.form.values.pos_to_cdis_entry_limit)
                var config = {
                    syncing_order: JSON.stringify(this.ranking),
                    pos_to_cdis_entry_limit: parseInt(this.form.values.pos_to_cdis_entry_limit),
                    cdis_to_pos_entry_limit: parseInt(this.form.values.cdis_to_pos_entry_limit)
                }
                axios.post('configuration', config)
                .then(response => {
                    this.dialog.visible = true;
                    this.dialog.status = 'success';
                    this.dialog.message = response.data.message;
                    this.dialog.ok.function = () => {
                        this.dialog.visible = false;
                    };
                    this.errors = {};
                }).catch(error => {
                    this.errors = error.response.data.errors;
                })
            }
        }
    }
</script>

<style lang="scss" scoped>
    .rankings {
        width: 50%;
        .list-group-item {
            padding-left: 15px;
            padding-right: 15px;
            cursor: move;
        }
        .ranking-order {
            border: 1px #ccc solid;
            background-color: #DE0900;
            color: #fff;
            padding: 5px 10px;
            font-size: 14px;
        }
    }
    .text-indent {
        margin-left: 40px;
    }
</style>
