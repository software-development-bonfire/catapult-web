<template>
    <td class="datatable-cell" :align="alignment" :valign="valign">
        <div class="datatable-cell-content">
            <slot></slot>
            <div class="error-message" v-show="validate">{{ error }}</div>
        </div>
    </td>
</template>

<style lang="scss" scoped>
    .datatable-cell {
        padding: 4px;
        border-color: #ccc;

        &--row-number {
            min-width: 25px;
            text-align: center;
        }

        &--action {
            min-width: 30px;
            text-align: center;
        }

        .error-message {
            margin-top: 3px;
            font-size: 12px;
            color: #ce1a1a;
            text-align: left;
            font-style: italic;
        }
    }

    .validation-inline {
        .datatable-cell-content {
            display: flex;
            align-items: center;
            .error-message {
                margin-left: 5px;
            }
        }
    }
</style>

<script>
    export default {
        props: {
            validate: {
                type: Boolean,
                default: true
            },
            name: {
                type: String
            },
            alignment: {
                type: String,
                default: 'left'
            },
            valign: {
                type: String,
                default: 'top'
            },
            error: {
                type: String,
                default: ''
            }
        },
        methods: {
        }
    }
</script>
