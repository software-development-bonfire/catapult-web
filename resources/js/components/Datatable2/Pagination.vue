<template>
    <ul class="v-datatable-light-pagination" :class="css.paginaton">
        <li
            v-if="moveFirstPage"
            :class="[css.paginationItem, css.moveFirstPage]"
        >
            <button
                :disabled="isActionDisabled('firstPage')"
                :class="css.pageBtn"
                @click="changePage(1)"
            >
                &lt;&lt;
            </button>
        </li>
        <li
            v-if="movePreviousPage"
            :class="[css.paginationItem, css.movePreviousPage]"
        >
            <button
                :disabled="isActionDisabled('previousPage')"
                :class="css.pageBtn"
                @click="changePage(currPage - 1)"
            >
                &lt;
            </button>
        </li>
        <li class="page-number">
            <input type="text" class="form-control" :value="currPage">
            / {{ lastPage }}
        </li>
        <li
            v-if="moveNextPage"
            :class="[css.paginationItem, css.moveNextPage]"
        >
            <button
                :disabled="isActionDisabled('nextPage')"
                :class="css.pageBtn"
                @click="changePage(currPage + 1)"
            >
                &gt;
            </button>
        </li>
        <li
            v-if="moveLastPage"
            :class="[css.paginationItem, css.moveLastPage]"
        >
            <button
                :disabled="isActionDisabled('lastPage')"
                :class="css.pageBtn"
                @click="changePage(lastPage)"
            >
                &gt;&gt;
            </button>
        </li>
    </ul>
</template>

<script>
    export default {
        name: 'DataTablePagination',
        props: {
            totalItems: {
                type: Number,
                required: true
            },
            itemsPerPage: {
                type: Number,
                default: 10
            },
            page: {
                type: Number,
                default: 1
            },
            moveLastPage: {
                type: Boolean,
                default: true
            },
            moveFirstPage: {
                type: Boolean,
                default: true
            },
            moveNextPage: {
                type: Boolean,
                default: true
            },
            movePreviousPage: {
                type: Boolean,
                default: true
            },
            css: {
                type: Object,
                default: () => ({
                    paginationItem: 'pagination-item',
                    moveFirstPage: 'move-first-page',
                    movePreviousPage: 'move-previous-page',
                    moveNextPage: 'move-next-page',
                    moveLastPage: 'move-last-page',
                    pageNumber: 'page-number',
                    pageBtn: 'page-btn'
                })
            }
        },
        data: function () {
            return {
                perPage: this.itemsPerPage,
                currPage: this.page
            }
        },
        computed: {
            qntPages: function () {
                const nrPages = this.lastPage
                if (nrPages > 4) {
                    if (this.currPage <= 3) {
                        return Array.apply(null, { length: 5 }).map((_, index) => index + 1)
                    } else if (this.currPage + 2 >= nrPages) {
                        return Array.apply(null, { length: nrPages }).map((_, index) => index + 1).slice(nrPages - 5, nrPages)
                    } else {
                        return Array.apply(null, { length: nrPages }).map((_, index) => index + 1).slice(this.currPage - 3, this.currPage + 2)
                    }
                } else {
                    return Array.apply(null, { length: nrPages }).map((_, index) => index + 1)
                }
            },
            lastPage: function () {
                // totalItems = 16 from parent
                // perPage = 10 from user input

                // 16 / 10 = 1.6 
                // Math.ceil(1.6) = 2 is the last page
                return Math.ceil(this.totalItems / this.perPage)
            }
        },
        watch: {
            page: function (newPage) {
                this.currPage = newPage
            },
            itemsPerPage: function (newItemsPerPage) {
                this.perPage = newItemsPerPage
                this.checkCurrentPageExist()
            }
        },
        methods: {
            pageClass: function (currentPage) {
                return this.currPage === currentPage ? `${this.css.paginationItem} selected` : this.css.paginationItem
            },
            changePage: function (pageToMove) {
                // Gets pageToMove
                // lastPage = 2

                // currPage = 1 + pageToMove => I'll move to next (+1)
                // 2 <= 2 = true (dont move further to last page)
                // 2 >= 1 = true (dont move previous from first page)
                // 2 != 2 = false (for uniqueness)

                // if true => move the page
                // if false => dont do anything
                if (pageToMove <= this.lastPage && pageToMove >= 1 && pageToMove !== this.currPage) {
                    this.$emit('on-update', pageToMove)
                }
            },
            isActionDisabled: function (action) {
                // this makes the buttons disabled when it reaches the first, previous, last and next page
                switch (action) {
                    case 'firstPage':
                        return this.currPage === 1
                    case 'previousPage':
                        return this.currPage === 1
                    case 'lastPage':
                        return this.currPage === this.lastPage || !this.totalItems || this.currPage * this.itemsPerPage >= this.totalItems
                    case 'nextPage':
                        return this.currPage === this.lastPage || !this.totalItems || this.currPage * this.itemsPerPage >= this.totalItems
                }
            },
            checkCurrentPageExist: function () {
                if (this.qntPages.indexOf(this.currPage) === -1) {
                    const nextPage = this.qntPages.length ? this.qntPages.length : 0
                    this.$emit('update-current-page', nextPage)
                }
            }
        }
    }
</script>

<style lang="scss">
    .page-number {
        display: flex;
        align-items: center;
        text-align: center;
        input {
            width: 50px;
        }
    }
</style>
