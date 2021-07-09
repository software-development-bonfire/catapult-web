<template>
    <div class="module-container">
        <ul class="nav nav-tabs nav-tabs--black" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'system-logs' ? 'active' : ''" @click="activePane = 'system-logs'" id="system-logs-tab" role="tab" aria-controls="system-logs" aria-selected="true">
                    {{ $t('label.system_logs') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'error-logs' ? 'active' : ''" @click="activePane = 'error-logs'" id="error-logs-tab" role="tab" aria-controls="error-logs" aria-selected="true">
                    {{ $t('label.error_logs') }}
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <system-logs v-show="activePane === 'system-logs'"></system-logs>
            <error-logs v-show="activePane === 'error-logs'"></error-logs>
        </div>
    </div>
</template>

<script>
    import ErrorLogs from './Tabs/ErrorLogs.vue';
    import SystemLogs from './Tabs/SystemLogs.vue';

    export default {
        components: {
            ErrorLogs,
            SystemLogs
        },
        data() {
            return {
                activePane: 'system-logs'
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
