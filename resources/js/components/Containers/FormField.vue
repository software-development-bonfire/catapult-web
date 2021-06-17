<template>
    <div>
        <slot></slot>
        <div
            class="error-message"
            :class="setAlignment"
            v-show="validate">
            {{ error }}
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .error-message {
        font-size: 14px;
        color: #ce1a1a;
        font-style: italic;
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
            error: {
                type: String,
                default: ''
            },
        },
        computed: {
            setAlignment() {
                switch (this.alignment) {
                    case 'left':
                        return 'text-left';
                        break;
                    case 'center':
                        return 'text-center';
                        break;
                    case 'right':
                        return 'text-right';
                        break;
                    default:
                        return 'text-left';
                        break;
                }
            }
        }
    }
</script>
