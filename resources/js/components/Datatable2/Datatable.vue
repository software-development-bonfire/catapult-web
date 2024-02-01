<template>
    <div class="datatable" :style="'max-height: ' + datatableMaxHeight">
        <div class="datatable-title" v-if="!!this.$slots['title']">
            <slot name="title"></slot>
        </div>
        <div class="datatable-scroll"
            :style="{
                'minHeight': settings.minHeight + 'px',
                'maxHeight': settings.maxHeight + 'px',
                'height': settings.height + 'px',
            }"
            :class="{ 'datatable-scroll--flex-column': noDataDisplayType === 'with-headers' }"
            @scroll="scrollerX($event)">
            <slot name="custom-header"></slot>
            <table class="main-table" :class="{ 'auto-width' :settings.autoWidth }">
                <thead v-if="settings.withTableHeaders">
                    <tr>
                        <th class="datatable-header-cell datatable-header-cell--row-numbers" v-if="settings.withRowNumbers">#</th>
                        <th class="datatable-header-cell"
                            v-for="(item, itemIndex) in headers"
                            :key="item.label"
                            :style="'min-width: ' + item.width + 'px; max-width: ' + item.width + 'px;'"
                        >
                            <input
                                v-if="isFieldSpecial(item.name) && extractArgs(item.name) === 'checkboxes'"
                                type="checkbox"
                                :checked="checkedAll"
                                @click="checkAll"
                            >
                            <span
                                :class="item.sort ? 'sorting-enabled' : ''"
                                v-if="!isFieldSpecial(item.name) && !item.customHeader">
                                {{ item.label }}
                                <i
                                    class="fa fa-sort sort-button ml-1"
                                    @click="sortColumn($event, item, itemIndex)"
                                    v-if="item.sort && item.label !== 'Actions'">
                                </i>
                            </span>
                            <span v-if="isFieldSpecial(item.name) && extractArgs(item.name) === 'actions'">
                                Actions
                            </span>
                        </th>
                        <th
                            class="datatable-header-cell datatable-header-cell--action"
                            colspan="5"
                            v-if="settings.hasEdit || settings.hasDelete || settings.hasActions">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <slot name="content"></slot>
                    <template v-if="noDataDisplayType === 'inline' && table.data !== undefined && table.data.length == 0">
                        <tr
                            class="no-data-available--inline"
                            v-if="table.data !== undefined && table.data.length == 0">
                            <td colspan="99">
                                No Data available to show
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <template v-if="noDataDisplayType === 'with-headers' && table.data !== undefined && table.data.length == 0">
                <no-data-available class="no-data-available--with-headers" v-if="table.data !== undefined && table.data.length == 0"></no-data-available>
            </template>
            <template v-else>
                <no-data-available v-if="!this.$slots['content']"></no-data-available>
            </template>
        </div>
        <div class="datatable-footer" v-if="!!this.$slots['footer']">
            <slot name="footer"></slot>
        </div>
        <div class="datatable-pagination" v-if="settings.withPagination">
            <div class="pagination-controls">
                <div class="btn pagination-button first-page" @click="paginateControl('firstPage')">
                    <i class="fa fa-step-backward"></i>
                </div>
                <div class="btn pagination-button previous" @click="paginateControl('previousPage')">
                    <i class="fa fa-chevron-left"></i>
                </div>
                <div class="pagination-number">
                    <input
                        class="pagination-input page-number"
                        type="text"
                        :value="currentPage"
                        @keyup.enter="updatePage($event)"
                        v-mask="{alias: 'integer', rightAlign: false, allowMinus: false, min: 1}"
                        :disabled="settings.realtime"
                        v-on:blur="(event) => { if (! event.target.value) { currentPage = 1; event.target.value = 1 } }"
                    >
                    / <span v-text="totalPages"></span>
                </div>
                <div class="btn pagination-button next" @click="paginateControl('nextPage')">
                    <i class="fa fa-chevron-right"></i>
                </div>
                <div class="btn pagination-button last-page" @click="paginateControl('lastPage')">
                    <i class="fa fa-step-forward"></i>
                </div>
            </div>
            <div class="items-per-page-input">
                <div class="show-all" v-show="table.data.length > 0">
                    <span @click="showAll">{{ showAllLabels }}</span>
                </div>
                <input
                    v-show="settings.showItemsPerPage"
                    type="text"
                    @keyup.enter="updateItemsPerPage($event)"
                    v-mask="{alias: 'integer', rightAlign: false, allowMinus: false, min: 1}"
                    v-model.number="settings.itemsPerPage"
                    :disabled="settings.realtime"
                    v-on:blur="(event) => { if (! event.target.value) { settings.itemsPerPage = 1 } }"
                >
            </div>
        </div>
    </div>
</template>

<script>
    import Pagination from './Pagination.vue';
    import NoDataAvailable from './NoDataAvailable';
    import Util from '../../mixins/Util';

    export default {
        components: {
            Pagination,
            NoDataAvailable
        },
        mixins: [Util],
        props: {
            headerFields: {
                type: Array
            },
            table: {
                type: Object,
                default: function() {
                    return {
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
                    }
                }
            },
            trackBy: {
                type: String,
                default: 'id'
            },
            settings: {
                type: Object,
                required: true
            },
            maxHeight: {
                type: Number
            },
            noDataDisplayType: {
                type: String,
                default: 'none'
            }
        },
        created() {
            this.initialize();
            this.setDefaultSettings();
        },
        data: function() {
            return {
                hasEdit: true,
                hasDelete: true,
                checkedAll: false,
                addRow: {},
                itemsChecked: [],
                itemsOnEdit: [],
                selected: [],
                currentPage: 1,
                currentItemsPerPage: 10,
                isShowAll: false,
                showAllLabels: '',
                sort: {
                    current: '',
                    index: '',
                    order: 'ASC'
                }
            };
        },
        computed: {
            console: () => console,
            headers: function () {
                if (
                    this.headerFields &&
                    this.headerFields.constructor === Array &&
                    this.headerFields.length
                ) {
                    return Object.keys(this.headerFields).map(key => {
                        const field = this.headerFields[key]
                        if (typeof field === 'string') {
                            return { label: field, name: field }
                        }
                        return field
                    })
                }
                return []
            },
            totalPages() {
                return this.table.meta.pagination.total_pages;
            },
            datatableMaxHeight() {
                return (isNaN(this.maxHeight)) ? 'auto;' : this.maxHeight + 'px;';
            }
        },
        methods: {
            singleHighlightRow(event, item) {

            },
            initialize() {
                if(this.table.meta === undefined) {
                    this.table.meta = Object.assign({
                        current_page: 1,
                        count: 1,
                        total_pages: 1
                    })
                }

                if(this.table.data !== undefined && this.table.data.length == 0){
                    this.table.data = [];
                }
            },
            setDefaultSettings() {
                this.settings.showItemsPerPage = this.settings.showItemsPerPage === undefined ? true : this.settings.showItemsPerPage;
                this.settings.itemsPerPage = this.settings.itemsPerPage === undefined ? 10 : this.settings.itemsPerPage;
                this.settings.withTableHeaders = this.settings.withTableHeaders === undefined ? true : this.settings.withTableHeaders;
                this.settings.withPagination = this.settings.withPagination === undefined ? true : this.settings.withPagination;
                this.settings.withRowNumbers = this.settings.withRowNumbers === undefined ? false : this.settings.withRowNumbers;
                this.settings.hasEdit = this.settings.hasEdit === undefined ? false : this.settings.hasEdit;
                this.settings.hasDelete = this.settings.hasDelete === undefined ? false : this.settings.hasDelete;
                this.settings.withShowAll, this.isShowAll = this.settings.withShowAll === undefined ? false : this.settings.withShowAll;
                this.settings.autoWidth = this.settings.autoWidth === undefined ? true : this.settings.autoWidth;
                this.settings.fixedHeaderScroll = this.settings.fixedHeaderScroll === undefined ? false : this.settings.fixedHeaderScroll;
                this.settings.select = this.settings.select === undefined
                    ? { active: false, type: 'single' }
                    : this.settings.select;

                this.currentItemsPerPage = this.deepClone(this.settings.itemsPerPage);
            },
            paginateControl(button) {
                let currentPage = this.table.meta.pagination.current_page;

                if (button == 'firstPage') {
                    if (currentPage == 1) {
                        return false;
                    }

                    currentPage = 1;
                }
                if (button == 'nextPage') {
                    let nextPage = currentPage + 1;

                    if (nextPage > this.totalPages) {
                        return false;
                    }

                    currentPage += 1;
                }
                if (button == 'previousPage') {
                    let previousPage = currentPage - 1;

                    if (previousPage <= 0) {
                        return false;
                    }

                    currentPage -= 1;
                }
                if (button == 'lastPage') {
                    if (currentPage == this.totalPages) {
                        return false;
                    }

                    currentPage = this.totalPages;
                }

                this.paginate(currentPage);
            },

            updatePage(event) {
                let currentPageNumber = Number(event.target.value);

                if (currentPageNumber <= 0 || currentPageNumber > this.totalPages || isNaN(currentPageNumber)) {
                    currentPageNumber = 1;
                }

                this.paginate(currentPageNumber);
            },

            paginate(page) {
                this.setDefaultSettings();

                this.$emit('paginate', page);

                if (this.settings.withShowAll) {
                    this.isShowAll = true;
                }

            },

            updateItemsPerPage: function(event) {
                let currentPageLength = Number(event.target.value);
                if (currentPageLength <= 0 || isNaN(currentPageLength)) {
                    this.settings.itemsPerPage = 10;
                }

                this.paginate(1);
            },

            scrollerX(event) {
                if (!this.settings.fixedHeaderScroll) {
                    return;
                }

                let scrollPosLeft = event.target.scrollLeft;
                let scrollers = document.querySelectorAll('.datatable-scroll, .fixed-table-headers');

                scrollers.forEach(function(scroller) {
                    scroller.scrollLeft = scrollPosLeft;
                });
            },

            checkAll: function () {
                this.checkedAll = !this.checkedAll
                if (this.checkedAll) {
                    this.itemsChecked = this.table.data
                } else {
                    this.itemsChecked = []
                }
                this.$emit('on-check-all', this.itemsChecked)
            },

            checkItem: function (item) {
                const found = this.itemsChecked.find(
                    itemChecked => itemChecked[this.trackBy] === item[this.trackBy]
                )
                if (found) {
                    this.itemsChecked = this.itemsChecked.filter(
                        itemChecked => itemChecked[this.trackBy] !== item[this.trackBy]
                    )
                    this.$emit('on-unchecked-item', item)
                } else {
                    this.itemsChecked = [...this.itemsChecked, item]
                    this.$emit('on-checked-item', item)
                }
            },

            isCheckedItem: function (item) {
                return !!this.itemsChecked.find(
                    itemChecked => itemChecked[this.trackBy] === item[this.trackBy]
                )
            },

            isFieldSpecial: field => field.indexOf('__') > -1,

            extractArgs: string => string.split(':')[1],

            extractActionID: string => {
                const list = string.split(':')
                return list.length === 3 ? list[2] : 'actions'
            },

            rowNumber: function(index) {
                return (this.currentItemsPerPage * (this.currentPage - 1)) + (parseInt(index) + 1);
            },

            showAll() {
                this.$emit('show-all', {
                    table: this.table,
                    status: this.isShowAll,
                    done: (value) => {
                        this.isShowAll = !value;
                    }
                });
            },

            sortColumn(event, column, index) {
                if (event) {
                    let sorters = document.querySelectorAll('.sort-button');
                    let sortBtn = event.target;

                    this.sort.current = column.name;
                    this.sort.index = index;

                    sorters.forEach(function(element) {
                        element.classList.remove('sort-button--active');
                    });

                    sortBtn.classList.add('sort-button--active');

                    if (sortBtn.classList.contains('fa-sort')) {
                        sortBtn.classList.remove('fa-sort');
                        sortBtn.classList.add('fa-sort-up');

                        this.sort.order = 'ASC';
                    } else if (sortBtn.classList.contains('fa-sort-up')) {
                        sortBtn.classList.remove('fa-sort-up');
                        sortBtn.classList.add('fa-sort-down');

                        this.sort.order = 'DESC';
                    } else if (sortBtn.classList.contains('fa-sort-down')) {
                        sortBtn.classList.remove('fa-sort-down');
                        sortBtn.classList.add('fa-sort-up');

                        this.sort.order = 'ASC';
                    }
                }

                let allSorters = document.querySelectorAll('.sort-button');

                allSorters.forEach(function(element) {
                    if (!element.classList.contains('sort-button--active')) {
                        element.classList.remove('fa-sort-up');
                        element.classList.remove('fa-sort-down');
                        element.classList.add('fa-sort');
                    }
                });

                this.$emit('sort', {
                    current: this.sort.current,
                    order: this.sort.order,
                    index: this.sort.index
                });
            },

            colorSortedColumn() {
                if (document.querySelector('.sort-button--active') === null) {
                    return;
                }

                let sortButton = document.querySelector('.sort-button--active');
                let sortIndex = sortButton.closest('.datatable-header-cell').cellIndex;
                let rows = document.querySelectorAll('.datatable-row');

                document.querySelectorAll('.sorted-cell--active').forEach(function(element) {
                    element.classList.remove('sorted-cell--active');
                });

                rows.forEach(function(row) {
                    row.querySelectorAll('.datatable-cell')[sortIndex].classList.add('sorted-cell--active');
                });
            }
        },

        watch: {
            'table.meta.pagination.current_page': function (value, old) {
                this.currentPage = value;
            },

            'table.meta.pagination.per_page': function (value, old) {
                this.currentItemsPerPage = this.settings.itemsPerPage;
            },

            'table': function(value) {
                if (this.settings.withShowAll) {
                    this.isShowAll = true;
                }
            },

            'isShowAll' : function(value) {
                this.showAllLabels = value ? 'Show All' : 'Hide Details'
            }
        }
    }
</script>

<style lang="scss" scoped>
    .datatable {
        display: flex;
        flex-direction: column;
        flex: 1;
        overflow: auto;

        &--modal {
            .datatable-scroll {
                flex: initial;
            }
        }

        &--blue {
            .main-table {
                thead {
                    background-color: #1C4D7C !important;
                    color: #fff;
                    .datatable-header-cell {
                        border-color: #ccc;
                    }
                }
            }
        }

        &--full-border {
            border: 1px #ccc solid;
            .datatable-pagination {
                border: 1px #ccc solid;
            }
        }

        &--font-sm {
            table.main-table {
                thead {
                    th, td {
                        font-size: 12px !important;
                    }
                }
                tbody {
                    td {
                        font-size: 12px !important;
                    }
                }
            }
        }

        &--full-width {
            table.main-table {
                width: 100%;
            }
        }

        &--no-wrap {
            table.main-table {
                .datatable-cell {
                    white-space: normal;
                }
            }
        }

        &--no-border {
            border: none !important;
        }

        &--no-row-color {
            table.main-table {
                tbody {
                    tr {
                        &:nth-of-type(odd) {
                            background-color: #fff;
                        }
                        &:nth-of-type(even) {
                            background-color: #fff;
                        }
                    }
                    .datatable-cell {
                        white-space: normal !important;
                    }
                }
            }
        }

        &--hoverable {
            table.main-table {
                tbody {
                    tr {
                        &:hover {
                            background-color: #eee;
                            cursor: pointer;
                        }
                    }
                }
            }
        }

        &--overflow-only-y {
            .datatable-scroll {
                overflow-y: scroll;
                overflow-x: hidden;
            }
        }

        &--overflow-only-x {
            .datatable-scroll {
                overflow-y: hidden;
                overflow-x: scroll;
            }
        }

        &--overflow-scroll-y-auto-x {
            .datatable-scroll {
                overflow-y: scroll;
                overflow-x: auto;
            }
        }

        &--overflow-scroll {
            .datatable-scroll {
                overflow: scroll;
            }
        }

        &--overflow-initial {
            .datatable-scroll {
                overflow: initial;
            }
        }

        &:last-child {
            table {
                border-left: none;
            }
        }

        &-title {
            padding: 6px 12px;
            background-color: #fff;
            border-bottom: 1px #ccc solid;
            .header-title {
                font-size: 14px;
            }
            .header-form {
                .table-layout {
                    td {
                        font-size: 14px;
                        padding: 3px;
                    }
                }
            }
        }

        .main-table {
            border: 1px #ccc solid;
            input[type="checkbox"] {
                position: relative;
                top: 3px;
            }
            thead {
                background-color: #E5E5E5;
                .datatable-header-cell {
                    text-align: center;
                    padding: 4px;
                    font-size: 14px;
                    border: 1px #ccc solid;
                    text-transform: uppercase;
                    font-family: Calibri;
                    white-space: nowrap;

                    &--action {
                        border-color: #ccc;
                    }
                }
            }
            tbody {
                tr {
                    border-top: 1px #ccc solid;
                    border-bottom: 1px #ccc solid;
                    background-color: #fff;
                    &.highlighted {
                        background-color: lighten(#17568C, 60%);
                        td {
                            border: 1px #17568C solid;
                        }
                    }
                }
                td {
                    border-left: 1px #ccc solid;
                    border-right: 1px #ccc solid;
                    padding: 4px;
                    font-size: 14px;
                    white-space: nowrap;
                    input[type="text"], select {
                        width: 100%;
                        padding: 4px 8px;
                        font-size: 14px;
                    }
                }
            }
        }

        .row-update {
            color: #e28000;
            &:hover {
                color: darken(#e28000, 5%);
                cursor: pointer;
            }
        }

        .row-delete {
            color: #e01818;
            &:hover {
                color: darken(#e01818, 5%);
                cursor: pointer;
            }
        }
    }

    .datatable-row {
        &.bg-red {
            background-color: #ffc6c6 !important;

            td {
                border: 1px darken(#ffc6c6, 60%) solid !important;
                color: darken(#ffc6c6, 60%) !important;
            }
        }
    }

    .datatable-scroll {
        position: relative;
        flex: 1;
        overflow: auto;
        background-color: #F8FAFC;
        &--flex-column {
            display: flex;
            flex-direction: column;
        }
    }

    .datatable-cell {
        &--cursor-pointer {
            .datatable-cell {
                &:hover {
                    cursor: pointer;
                }
            }
        }
        &--hover-highlight {
            .hoverable {
                &:hover {
                    background-color: darken(#fff, 12%);
                }
            }
        }
    }

    .datatable {
        &--scroll-hidden {
            .datatable-scroll {
                overflow: hidden;
            }
        }
        &--spaced {
            .datatable-scroll {
                margin: 5px 5px 0px 5px;
            }
            .datatable-footer {
                margin: 0px 5px 5px 5px;
            }

            &-no-right {
                .datatable-scroll,
                .datatable-footer {
                    margin-right: 0px;
                }
            }
        }
    }

    .datatable-pagination {
        background-color: #f2f2f2;
        border-top: 1px #ccc solid;
        padding: 6px;
        display: flex;
        align-items: center;

        .pagination-input {
            margin-left: 5px;
            margin-right: 5px;
            width: 60px;
            font-size: 14px;
            border-radius: 0px;
            padding: 3px 6px;
            border: 1px darken(#57606f, 10%) solid;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex: 1;
            .pagination-button {
                background: darken(#57606f, 15%);
                border: 1px darken(#57606f, 10%) solid;
                color: #fff;
                padding: 3px 6px;
                margin: 0px 3px;
                font-size: 14px;
                width: 30px;
                transition: .3s;
                &:hover {
                    cursor: pointer;
                    background-color: darken(#57606f, 20%);
                }
            }
            .pagination-number {
                display: flex;
                align-items: center;
                span {
                    margin-left: 5px;
                    margin-right: 5px;
                    font-size: 14px;
                }
            }
        }

        .items-per-page-input {
            display: flex;
            align-items: center;
            input {
                width: 40px;
                height: 30px;
                font-size: 14px;
                padding: 3px 6px;
                border-radius: 0px;
                border: 1px #25426f solid;
                text-align: center;
            }
        }

        .show-all {
            margin-right: 10px;
            span {
                color: #17568C;
                text-decoration: underline;
                cursor: pointer;

                &:hover {
                    color: lighten(#17568C, 10%);
                }
            }
        }
    }

    .datatable {
        .no-data-available {
            &--inline {
                td {
                    color: red;
                    padding: 10px !important;
                    background-color: #fff;
                    border: 1px #ccc solid;
                    text-align: center;
                }
            }
        }
        .sorting-enabled {
            display: flex;
            justify-content: center;
            .sort-button {
                display: flex;
                align-items: center;
                color: #8e8c8c;
                &--active {
                    color: #0ec3f9;
                    &:hover {
                        color: darken(#0ec3f9, 10%) !important;
                    }
                }
                &:hover {
                    cursor: pointer;
                    color: darken(#fff, 5%);
                }
            }
        }
    }
</style>
