<template>
    <div class="dialog-box" ref="dialogBox" v-show="visible" tabindex="0" @keyup.esc.stop.prevent="$emit('update:visible', false)">
        <div class="dialog-box-container">
            <div class="dialog-box-type">
                <template v-if="status === 'delete'">
                    <i class="fa fa-trash fa-4x dialog-box-type--delete"></i>
                </template>
                <template v-else-if="status === 'success'">
                    <i class="fa fa-check-circle fa-4x dialog-box-type--success"></i>
                </template>
                <template v-else-if="status === 'error'">
                    <i class="fa fa-exclamation-circle fa-4x dialog-box-type--error"></i>
                </template>
                <template v-else-if="status === 'warning' || status === 'warning-confirm'">
                    <i class="fa fa-exclamation-triangle fa-4x dialog-box-type--warning"></i>
                </template>
                <template v-else-if="status === 'info'">
                    <i class="fa fa-exclamation-circle fa-4x dialog-box-type--info"></i>
                </template>
                <template v-else-if="status === 'confirm'">
                    <i class="fa fa-question-circle fa-4x dialog-box-type--confirm"></i>
                </template>
                <template v-else>
                    <slot name="icon">
                        <i class="fa fa-question-circle fa-4x"></i>
                    </slot>
                </template>
            </div>
            <div class="dialog-box-message">
                <div class="dialog-box-message__heading-text">
                    <template v-if="status === 'delete'">
                        Are you sure?
                    </template>
                    <template v-else-if="status === 'success'">
                        Success
                    </template>
                    <template v-else-if="status === 'error'">
                        Error
                    </template>
                    <template v-else-if="status === 'warning' || status === 'warning-confirm'">
                        Warning
                    </template>
                    <template v-else-if="status === 'info'">
                        Info
                    </template>
                    <template v-else-if="status === 'confirm'">
                        Confirm
                    </template>
                    <template v-else>
                        <slot name="heading-text">
                            Are you sure?
                        </slot>
                    </template>
                </div>
                <div class="dialog-box-message__sub-text">
                    <slot name="message">
                        Add a new message
                    </slot>
                    <slot name="link">
                    </slot>
                </div>
            </div>
            <div class="dialog-box-footer">
                <template v-if="status === 'delete'">
                    <button class="button button--danger" ref="dialogBoxOk" @click="$emit('ok')">Ok</button>
                    <button class="button button--default" @click="$emit('cancel')">Cancel</button>
                </template>
                <template v-else-if="status === 'confirm' || status === 'warning-confirm'">
                    <button class="button button--light" ref="dialogBoxOk" @click="$emit('ok')">Ok</button>
                    <button class="button button--default" @click="$emit('cancel')">Cancel</button>
                </template>
                <template v-else-if="status === 'confirm-yes-no' || status === 'warning-confirm-yes-no'">
                    <button class="button button--light" ref="dialogBoxOk" @click="$emit('ok')">Yes</button>
                    <button class="button button--default" @click="$emit('cancel')">No</button>
                </template>
                <template v-else>
                    <slot name="buttons">
                        <button class="button button--light" ref="dialogBoxOk" @click="$emit('ok')">Ok</button>
                    </slot>
                </template>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .dialog-box {
        position: fixed;
        background-color: rgba(0, 0, 0, .6);
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 11000;

        &-container {
            padding: 25px 15px 15px 15px;
            border-radius: 4px;
            box-shadow:
                0px 11px 15px -7px rgba(0, 0, 0, 0.2),
                0px 24px 38px 3px rgba(0, 0, 0, 0.14),
                0px 9px 46px 8px rgba(0,0,0,.12);
            background-color: #fff;
            width: 300px;
        }

        &-type {
            text-align: center;

            &--delete,
            &--error {
                color: #e3342f;
            }

            &--success {
                color: #4C7A34;
            }

            &--warning {
                color: #FEC63D;
            }

            &--info {
                color: #00b6ff;
            }

            &--confirm {
                color: #0676e5;
            }
        }

        &-message {
            text-align: center;

            &__heading-text {
                text-align: center;
                font-weight: bold;
                font-size: 18px;
                text-transform: uppercase;
                margin-top: 10px;
            }

            &__sub-text {
                text-align: center;
                font-size: 14px;
            }
        }

        &-footer {
            margin-top: 15px;
            text-align: right;
        }
    }
</style>

<script>
    export default {
        props: ['status', 'visible'],
        watch: {
            'visible': function(value) {
                if (value) {
                    this.$nextTick(() => {
                        if (this.$refs.dialogBoxOk !== undefined) {
                            this.$refs.dialogBoxOk.focus();
                        }
                    });
                }
            },
        }
    }
</script>
