<template>
    <tr
        class="datatable-row"
        @click.stop="rowClick($event)"
        @dblclick.stop="dblClickRow($event)"
        tabindex="0"
        @keyup.delete.stop.prevent="deleteRow($event)">
        <template v-if="rowType === 'add'">
            <td
                class="datatable-cell datatable-cell--row-number"
                v-show="!fullAddDisplay"
                v-if="settings.withRowNumbers"></td>
            <slot></slot>
            <td class="datatable-cell datatable-cell--action row-add"><i class="fa fa-save fa-lg" @click.stop="addRow($event)"></i></td>
        </template>
        <template v-else>
            <td class="datatable-cell datatable-cell--row-number" v-if="settings.withRowNumbers">{{ rowNumber($attrs.rowIndex) }}</td>
            <slot></slot>
            <td class="datatable-cell datatable-cell--action" v-if="settings.hasView"><i class="fa fa-eye fa-lg row-view" @click.stop="viewRow($event)"></i></td>
            <td class="datatable-cell datatable-cell--action" v-if="settings.hasEdit">
                <i :class="[updateClass]" @click.stop="updateRow($event)"></i>
            </td>
        </template>
        <td class="datatable-cell datatable-cell--action" v-if="settings.hasDelete">
            <i :class="[deleteClass]" @click.stop="deleteRow($event)"></i>
        </td>
    </tr>
</template>

<script>
    export default {
        props: {
            fullAddDisplay: {
                type: Boolean,
                default: false
            },
            settings: {
                type: Object
            },
            type: {
                type: String
            },
            values: {
                type: Object
            },
        },
        data() {
            return {
                rowType: this.type,
                rowValues: this.values,
                defaults: {
                    rowValues: {}
                }
            }
        },
        computed: {
            updateClass() {
                return {
                    'fa fa-lg row-update': true,
                    'fa-gear': this.rowType === 'view',
                    'fa-save': this.rowType === 'edit',
                }
            },
            deleteClass() {
                return {
                    'fa fa-lg row-delete': true,
                    'fa-trash': this.isTypeDestroy,
                    'fa-times-circle-o':
                        this.isTypeRemove
                        || this.isTypeRevert
                        || this.isTypeClear,
                    'row-revert': this.isTypeRemove
                        || this.isTypeRevert
                        || this.isTypeClear,
                }
            },
            isTypeDestroy() {
                return ! this.values.isNew && this.rowType === 'view';
            },
            isTypeRemove() {
                return this.values.isNew && this.rowType === 'view';
            },
            isTypeRevert() {
                return this.rowType === 'edit';
            },
            isTypeClear() {
                return this.rowType === 'add';
            }
        },
        mounted() {
            this.defaults.rowValues = {...this.rowValues};
        },
        methods: {
            updateRow(event) {
                let row = event.target.closest('.datatable-row');

                if (this.rowType === 'view') {
                    this.rowType = 'edit';

                    this.$emit('enable-row', {
                        state: true
                    });
                } else if (this.rowType === 'edit') {
                    this.$parent.$emit('update-row', {
                        values: this.rowValues,
                        rowIndex: this.$attrs.rowIndex,
                        type: 'update',
                        new: this.rowValues.isNew,
                        done: () => {
                            this.rowType = 'view';
                            this.updateObjectChild(this.defaults.rowValues, this.rowValues);

                            this.$emit('enable-row', {
                                state: false
                            });
                        }
                    });
                }
            },

            deleteRow(event) {
                if (event.keyCode !== 46 && event.keyCode != undefined) {
                    return;
                }

                let row = event.target.closest('.datatable-row');

                let type =
                    this.isTypeDestroy
                        ? 'destroy'
                        : this.isTypeRemove
                            ? 'remove'
                            : this.isTypeRevert
                                ? 'revert'
                                : this.isTypeClear
                                    ? 'clear'
                                    : '';

                this.$parent.$emit('delete-row', {
                    values: this.rowValues,
                    rowIndex: this.$attrs.rowIndex,
                    type: type,
                    new: this.rowValues.isNew
                });

                this.updateObjectChild(this.rowValues, this.defaults.rowValues);

                if (this.rowType === 'edit') {
                    this.rowType = 'view';
                    this.isActive = false;
                }

                this.$emit('enable-row', {
                    state: false
                });
            },

            updateObjectChild(subject, object, reactive = true) {
                if (reactive) {
                    $.each(object, (key, value) => {
                        subject[key] = value;
                    });
                } else {
                    subject = {...object};
                }
            },

            addRow(event) {
                this.$parent.$emit('add-row', {
                    event: event,
                    values: this.rowValues,
                    rowIndex: this.$attrs.rowIndex,
                    type: 'add',
                    new: this.rowValues.isNew,
                    done: () => {}
                });
            },

            viewRow(event) {
                this.$parent.$emit('view-row', this.$attrs.values);
            },

            rowClick: function(event) {
                let that = this;
                let row = event.target;

                if (this.rowType === 'add') {
                    return false;
                }

                if (this.settings.select.active) {
                    if (this.settings.select.type == 'single') {
                        row.closest('tbody').querySelectorAll('tr').forEach(function(item) {
                            item.classList.remove('highlighted');
                        });

                        row.closest('tr').classList.add('highlighted');
                    } else if (this.settings.select.type == 'multiselect') {
                        if (row.closest('tr').classList.contains('highlighted')) {
                            row.closest('tr').classList.remove('highlighted');
                        } else {
                            row.closest('tr').classList.add('highlighted');
                        }
                    }
                }

                this.$emit('row-click', {
                    event: event,
                    item: that.values,
                    index: that.$attrs.rowIndex,
                    rowNumber: that.rowNumber(that.$attrs.rowIndex)
                });
            },

            dblClickRow: function(event) {
                let that = this;
                let row = event.target;

                this.$emit('dbl-row-click', {
                    event: event,
                    item: that.values,
                    index: that.$attrs.rowIndex,
                    rowNumber: that.rowNumber(that.$attrs.rowIndex)
                });
            },

            rowNumber(index) {
                return (this.$parent.currentItemsPerPage * (this.$parent.currentPage - 1)) + (parseInt(index) + 1);
            },
        },

        watch: {
            'values': function(value) {
                this.defaults.rowValues = {...value};
                this.rowValues = value;
            },
        }
    }
</script>

<style lang="scss" scoped>
    .datatable-row {
        button,
        input[type="button"] {
            padding: 0px 8px;
        }

        &:not(.datatable-row--add) {
            button,
            input[type="button"],
            input[type="text"],
            input[type="number"],
            select {
                &:disabled {
                    cursor: not-allowed;
                    background-color: #f3f3f3;
                    color: #7b7b7b;
                    outline: none;
                }
            }

            &:focus {
                background-color: #dcecf9
            }
        }

        &--hoverable {
            &:hover {
                background-color: #eee !important;
                cursor: pointer;
            }
        }

        &--hoverable-cell {
            .hoverable {
                &:hover {
                    background-color: #eee !important;
                    cursor: pointer;
                }
            }
        }

        &--sm {
            input[type="text"],
            select {
                padding: 4px;
                font-size: 14px;
                height: auto;
                border-radius: 0px;
            }
        }
    }

    .datatable-cell {
        padding: 4px;
        border: 1px transparent solid;
        border-color: #ccc;

        &--row-number {
            min-width: 50px;
            width: 50px;
            text-align: center;
        }

        &--action {
            min-width: 40px;
            width: 40px;
            text-align: center;
        }

        &.sorted-cell--active {
            background-color: #f3f3f3;
        }
    }

    .row {
        &-add {
            color: #00aa27;
            &:hover {
                color: darken(#00aa27, 5%);
                cursor: pointer;
            }
        }
        &-view {
            color: #1178f7;
            &:hover {
                color: darken(#1178f7, 5%);
                cursor: pointer;
            }
        }
        &-update {
            color: #e28000;
            &:hover {
                color: darken(#e28000, 5%);
                cursor: pointer;
            }
        }
        &-delete {
            color: #e01818;
            &:hover {
                color: darken(#e01818, 5%);
                cursor: pointer;
            }
        }
    }
</style>

<style lang="scss" scoped>
    @media (max-width: 575.98px) {
        .datatable-scroll {
            overflow: auto !important;
        }
    }

    // Small devices (landscape phones, less than 768px)
    @media (max-width: 767.98px) {}

    // Medium devices (tablets, less than 992px)
    @media (max-width: 991.98px) {}

    // Large devices (desktops, less than 1200px)
    @media (max-width: 1199.98px) {
    }
</style>
