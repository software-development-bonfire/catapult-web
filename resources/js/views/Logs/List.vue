<template>
    <div class="module-container">
        <ul class="nav nav-tabs nav-tabs--black" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'error-logs' ? 'active' : ''" @click="activePane = 'error-logs'" id="error-logs-tab" role="tab" aria-controls="error-logs" aria-selected="true">
                    {{ $t('label.error_logs') }}
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <error-logs v-show="activePane === 'error-logs'"></error-logs>
        </div>
    </div>
</template>

<script>
    import ErrorLogs from './Tabs/ErrorLogs.vue';

    export default {
        components: {
            ErrorLogs
        },
        data() {
            return {
                activePane: 'error-logs'
            }
        }
    }
</script>

<style lang="scss" scoped>
    .tab-content {
        display: flex;
        flex-direction: column;
        flex: 1;
        overflow: auto;
        > .active {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: auto;
        }
    } 
</style>
