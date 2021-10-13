<template>
    <div class="module-container">
        <ul class="nav nav-tabs nav-tabs--black" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'licensing' ? 'active' : ''" @click="activePane = 'licensing'" id="licensing-tab" href="#licensing" role="tab" aria-controls="licensing" aria-selected="true">
                    {{ $t('label.licensing') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'file_storage' ? 'active' : ''" @click="activePane = 'file_storage'" id="file-storage-setup-tab" href="#file-storage-setup" role="tab" aria-controls="file-storage-setup" aria-selected="false">
                    {{ $t('label.file_storage_setup') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'catapult' ? 'active' : ''" @click="activePane = 'catapult'" id="catapult-db-setup" href="#catapult-db-setup" role="tab" aria-controls="catapult-db-setup" aria-selected="false">
                    {{ $t('label.catapult_db_setup') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'api' ? 'active' : ''" @click="activePane = 'api'" id="api-setup" href="#api-setup" role="tab" aria-controls="api-setup" aria-selected="false">
                    {{ $t('label.api_setup') }}
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <licensing v-show="activePane === 'licensing'"></licensing>
            <file-storage-setup v-show="activePane === 'file_storage'"></file-storage-setup>
            <catapult-db-setup v-show="activePane === 'catapult'"></catapult-db-setup>
            <api-setup v-show="activePane === 'api'"></api-setup>
        </div>
    </div>
</template>

<script>
    import Licensing from './Tabs/Licensing.vue';
    import FileStorageSetup from './Tabs/FileStorageSetup.vue';
    import CatapultDBSetup from './Tabs/CatapultDBSetup.vue';
    import APISetup from './Tabs/APISetup.vue';

    export default {
        components: {
            'licensing': Licensing,
            'file-storage-setup': FileStorageSetup,
            'catapult-db-setup': CatapultDBSetup,
            'api-setup': APISetup,
        },
        mounted() {
            let urlData = QueryString.parse(window.location.search.substr(1));

            if (urlData.redirect) {
                this.activePane = urlData.redirect;
            }
        },
        data() {
            return {
                activePane: 'licensing'
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
