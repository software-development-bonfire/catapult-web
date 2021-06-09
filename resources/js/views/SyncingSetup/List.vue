<template>
    <div class="module-container">
        <div class="box-row box-row--white p-1" align="right">
            <button class="button button--light module-action-button">Save</button>
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
    </div>
</template>

<script>
    import Draggable from 'vuedraggable';

    export default {
        components: {
            Draggable
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
