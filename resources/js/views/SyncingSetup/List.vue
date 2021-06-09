<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            <button class="button button--light module-action-button" @click="save">Save</button>
        </div>
        <div class="overflow-auto p-4">
            <h3>Batch Syncing</h3>
            <div class="form-group">
                <label>Set the entry limit per batch syncing (POS TO CDIS PROCESS)</label>
                <input
                    type="text"
                    class="form-control w-25 text-left"
                    v-model.number="form.values.pos_to_cdis_entry_limit"
                    v-mask="{
                        alias: 'integer',
                        autoGroup: true,
                        digitsOptional: false,
                        showMaskOnHover: false,
                        showMaskOnFocus : false,
                        min: 0
                    }">
            </div>
            <div class="form-group">
                <label>Set the entry limit per batch syncing (CDIS TO POS PROCESS)</label>
                <input
                    type="text"
                    class="form-control w-25 text-left"
                    v-model.number="form.values.cdis_to_pos_entry_limit"
                    v-mask="{
                        alias: 'integer',
                        autoGroup: true,
                        digitsOptional: false,
                        showMaskOnHover: false,
                        showMaskOnFocus : false,
                        min: 0
                    }">
            </div>
            <h3>Syncing Prioritization</h3>
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
                        pos_to_cdis_entry_limit: 0,
                        cdis_to_pos_entry_limit: 0
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
            }
        },
        methods: {
            save() {
                this.dialog.visible = true;
                this.dialog.status = 'success';
                this.dialog.message = 'Syncing Setup saved successfully!';
                this.dialog.ok.function = () => {
                    this.dialog.visible = false;
                };
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
</style>
