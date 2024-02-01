<template>
    <div
        class="category-picker"
        :class="{ 'category-picker--disabled': disabled }"
        @click.stop="togglePicker">
        <span
            class="category-placeholder"
            v-if="selections.first.index === '' && ! displayPicker && allSelected === ''">
            {{ $t('label.select_a') + ' ' + $t('label.category') }}
        </span>
        <div class="category-display">
            <div class="category-label">
                {{ ! allSelected ? categoryLabel : '' }}
            </div>
            <div class="category-actions">
                <div 
                    class="category-dropdown-button">
                    <i class="fa fa-lg fa-caret-down"></i>
                </div>
                <div
                    v-if="clearable"
                    class="category-dropdown-button action-clearable">
                    <i
                        class="fa fa-lg fa-close"
                        @click.stop="clearSelection">
                    </i>
                </div>
            </div>
        </div>
        <div
            class="category-dropdown"
            :class="directionStyling"
            :style="dropdownStyling"
            v-if="displayPicker">
            <div class="row no-gutters">
                <div class="col">
                    <div
                        class="category-child-panel"
                        :style="{ height: height - 10 + 'px'}">
                        <ul>
                            <li
                                v-for="(first, firstIndex) in categories"
                                :key="firstIndex"
                                :class="{ 'active': selections.first.index === firstIndex }"
                                @click.stop="selectFirst(firstIndex)">
                                <div class="d-flex align-items-center item-height">
                                    {{ first.text }}
                                    <i class="fa fa-caret-right ml-1" v-if="first.children.length !== 0"></i>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col">
                    <div
                        class="category-child-panel"
                        :style="{ height: height - 10 + 'px'}">
                        <ul v-if="selections.first.label !== ''">
                            <li
                                v-for="(second, secondIndex) in categories[selections.first.index].children"
                                :key="secondIndex"
                                :class="{ 'active': selections.second.index === secondIndex }"
                                @click.stop="selectSecond(secondIndex)">
                                <div class="d-flex align-items-center">
                                    {{ second.text }}
                                    <i class="fa fa-caret-right ml-1" v-if="second.children.length !== 0"></i>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col">
                    <div
                        class="category-child-panel"
                        :style="{ height: height - 10 + 'px'}">
                        <ul v-if="selections.second.label !== ''">
                            <li
                                v-for="(third, thirdIndex) in categories[selections.first.index].children[selections.second.index].children"
                                :key="thirdIndex"
                                :class="{ 'active': selections.third.index === thirdIndex }"
                                @click.stop="selectThird(thirdIndex)">
                                <div class="d-flex align-items-center">
                                    {{ third.text }}
                                    <i class="fa fa-caret-right ml-1" v-if="third.children.length !== 0"></i>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            tree: {
                type: Array,
                default: function() {
                    return []
                }
            },
            selected: {
                type: String,
                default: ''
            },
            disabled: {
                type: Boolean,
                default: false
            },
            width: {
                type: Number,
                default: -1
            },
            height: {
                type: Number,
                default: 300
            },
            direction: {
                type: String,
                default: 'left'
            },
            clearable: {
                type: Boolean,
                default: false
            },
            canSelectAll: {
                type: Boolean,
                default: false
            }
        },
        computed: {
            categoryLabel() {
                let first = this.selections.first.label;
                let second = (this.selections.second.index !== '') ? ' > ' + this.selections.second.label : '';
                let third = (this.selections.third.index !== '') ? ' > ' + this.selections.third.label : '';

                return first + second + third;
            },
            dropdownStyling() {
                return {
                    width: this.width !== -1 ? this.width + 'px' : '100%'
                };
            },
            directionStyling() {
                if (this.direction === 'left') {
                    return 'category-dropdown--left';
                } else {
                    return 'category-dropdown--right';
                }
            }
        },
        data() {
            return {
                categories: this.tree,
                selectedCategories: [],
                selections: {
                    first: {
                        label: '',
                        index: ''
                    },
                    second: {
                        label: '',
                        index: ''
                    },
                    third: {
                        label: '',
                        index: ''
                    }
                },
                allSelected: false,
                category: '',
                displayPicker: false,
            }
        },
        mounted() {
            this.setCategories();
        },
        methods: {
            setSelected() {
                this.selectedCategories = this.selected.length > 0 ? this.selected.replace(/\s+/g, '').split('>') : [];
            },
            setCategories() {
                let self = this;

                if (self.selectedCategories.length === 0) {
                    self.selections.first.index = "";
                    self.selections.first.label = "";
                    self.selections.second.index = "";
                    self.selections.second.label = "";
                    self.selections.third.index = "";
                    self.selections.third.label = "";

                    return;
                }

                this.categories.forEach(function(first, firstIndex) {
                    if (self.selectedCategories[0] === first.bid) {
                        self.selections.first.index = firstIndex;
                        self.selections.first.label = self.categories[firstIndex].text;
                        self.selections.first.bid = first.bid;
                    } else if (self.selectedCategories[0] === undefined) {
                        self.selections.first.index = "";
                        self.selections.first.label = "";
                        self.selections.first.bid = "";
                    }

                    first.children.forEach(function(second, secondIndex) {
                        if (self.selectedCategories[1] === second.bid) {
                            self.selections.second.index = secondIndex;
                            self.selections.second.label = self.categories[firstIndex].children[secondIndex].text;
                            self.selections.second.bid = second.bid;
                        } else if (self.selectedCategories[1] === undefined) {
                            self.selections.second.index = "";
                            self.selections.second.label = "";
                            self.selections.second.bid = "";
                        }

                        second.children.forEach(function(third, thirdIndex) {
                            if (self.selectedCategories[2] === third.bid) {
                                self.selections.third.index = thirdIndex;
                                self.selections.third.label = self.categories[firstIndex].children[secondIndex].children[thirdIndex].text;
                                self.selections.third.bid = third.bid;

                                return false;
                            } else if (self.selectedCategories[2] === undefined) {
                                self.selections.third.index = "";
                                self.selections.third.label = "";
                                self.selections.third.bid = "";
                            }
                        });
                    });
                });
            },
            selectFirst(index) {
                this.selections.first.label = this.categories[index].text;
                this.selections.first.index = index;
                this.selections.first.bid = this.categories[index].bid;
                this.allSelected = false;

                this.$emit('update:selected', this.selections.first.bid);

                this.selections.second = {
                    label: '',
                    index: ''
                };

                this.selections.third = {
                    label: '',
                    index: ''
                };

                this.$emit('category-data', {
                    label: this.categoryLabel,
                    selected: this.selected,
                });
            },
            selectSecond(index) {
                this.selections.second.index = index;
                this.selections.second.label = this.categories[this.selections.first.index].children[index].text;
                this.selections.second.bid = this.categories[this.selections.first.index].children[index].bid;
                this.allSelected = false;

                this.$emit('update:selected', this.selections.first.bid + ' > ' + this.selections.second.bid);

                this.selections.third = {
                    label: '',
                    index: ''
                };

                this.$emit('category-data', {
                    label: this.categoryLabel,
                    selected: this.selected,
                });
            },
            selectThird(index) {
                this.selections.third.index = index;
                this.selections.third.label = this.categories[this.selections.first.index].children[this.selections.second.index].children[index].text;
                this.selections.third.bid = this.categories[this.selections.first.index].children[this.selections.second.index].children[index].bid;
                this.allSelected = false;

                this.$emit('update:selected', this.selections.first.bid + ' > ' + this.selections.second.bid + ' > ' + this.selections.third.bid);

                this.$emit('category-data', {
                    label: this.categoryLabel,
                    selected: this.selected,
                });
            },
            selectAll() {
                this.clearSelection();
                this.allSelected = true;
            },
            showPicker() {
                document.addEventListener('click', this.documentClick);
                this.displayPicker = true;
            },
            hidePicker() {
                document.removeEventListener('click', this.documentClick);
                this.displayPicker = false;
            },
            togglePicker() {
                if (this.disabled) {
                    return;
                }

                this.displayPicker ? this.hidePicker() : this.showPicker();
            },
            documentClick(e) {
                let el = this.$el,
                    target = e.target;

                if (el !== target && !el.contains(target)) {
                    this.hidePicker()
                }
            },
            clearSelection() {
                this.selections = {
                    first: {
                        label: '',
                        index: ''
                    },
                    second: {
                        label: '',
                        index: ''
                    },
                    third: {
                        label: '',
                        index: ''
                    }
                };

                this.selectedCategories = [];
                this.allSelected = '';

                this.$emit('update:selected', '');

                this.$emit('category-data', {
                    label: this.categoryLabel,
                    selected: this.selected,
                });
            }
        },
        watch: {
            'tree': function(value) {
                this.categories = value;
                this.setCategories();
            },
            'selected': function() {
                this.setSelected();
                this.setCategories();
            }
        }
    }
</script>

<style lang="scss" scoped>
    .category-placeholder {
        color: #c4c4c4;
        margin-left: 10px;
    }
    .category-picker {
        border: 1px #ccc solid;
        background-color: #fff;
        min-height: 36px;
        display: flex;
        align-items: center;
        position: relative;
        cursor: pointer;
        &--disabled {
            cursor: not-allowed;
            background-color: #e9ecef;
        }
    }
    .category-display {
        flex: 1;
        display: flex;
    }
    .category-label {
        padding: 3px 6px;
        flex: 1;
    }
    .category-actions {
        display: flex;
    }
    .action-clearable {
        i {
            color: #bfcbd9;
            &:hover {
                color: darken(#bfcbd9, 10%);
            }
        }
    }
    .category-dropdown-button {
        padding-right: 6px;
        padding-top: 3px;
    }
    .category-child-symbol {
        margin-left: 5px;
    }
    .category-dropdown {
        position: absolute;
        top: 100%;
        border: 1px #ccc solid;
        background-color: #fff;
        overflow: hidden;
        width: 100%;
        overflow: auto;
        z-index: 2;
        &--right {
            left: 0px;
            right: initial;
        }
        &--right {
            left: initial;
            right: 0px;
        }
    }
    .category-child-panel {
        margin: 5px;
        height: 298px;
        overflow-y: scroll;
        overflow-x: hidden;
        border: 1px #ccc solid;
        > ul {
            padding-left: 0px;
            margin-bottom: 0px;
            li {
                list-style: none;
                padding: 4px 8px;
                font-size: 12px;
                border: 1px transparent solid;
                &:hover {
                    background-color: #f2f2f2;
                    border-color: #ddd;
                    color: #333;
                    cursor: pointer;
                }
                &.active {
                    background-color: #185fa5;
                    border-color: darken(#185fa5, 10%);
                    color: #fff;
                    &:hover {
                        background-color: #185fa5;
                        border-color: darken(#185fa5, 10%);
                        color: #fff;
                        cursor: default;
                    }
                }
            }
        }
    }
</style>
