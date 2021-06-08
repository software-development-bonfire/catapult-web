<template>
    <div
        class="no-data-available"
        :class="{
            'no-data-available--text': theme === 'text',
            'no-data-available--flexed': theme === 'flexed'
        }">
        <div class="no-data-available-image">
            <i class="fa fa-database fa-4x"></i>
            <i class="fa fa-warning fa-2x"></i>
        </div>
        <div class="no-data-available-text">
            No data available to show
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            theme: {
                type: String,
                default: 'default'
            }
        }
    }
</script>

<style lang="scss">
    .no-data-available {
        background-color: #f2f2f2;
        text-align: center;
        position: absolute;
        top: 0px;
        bottom: 0px;
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border: 1px #ccc solid;
        background-image: url('/images/papyrus.png');

        &--text {
            background-color: #fff !important;
            background-image: initial !important;

            .no-data-available-image {
                display: none;
            }
        }

        &--flexed {
            position: static !important;
            top: initial !important;
            bottom: initial !important;
            flex: 1;
        }

        &--with-headers {
            position: static !important;
            flex: 1;
        }

        &--bordered {
            border: 1px #ccc solid;
        }

        &-image {
            .fa-database {
                color: #17568C;
            }
            .fa-warning {
                color: #ffa100;
                position: relative;
                left: -10px;
                top: 16px;
            }
        }

        &-text {
            position: relative;
            top: 15px;
            font-size: 16px;
        }
    }
</style>