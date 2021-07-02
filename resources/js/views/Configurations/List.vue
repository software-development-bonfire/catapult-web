<template>
    <div class="module-container">
        <ul class="nav nav-tabs nav-tabs--black" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'licensing' ? 'active' : ''" @click="activePane = 'licensing'" id="licensing-tab" href="#licensing" role="tab" aria-controls="licensing" aria-selected="true">
                    {{ $t('label.licensing') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'remote' ? 'active' : ''" @click="activePane = 'remote'" id="remote-setup-tab" href="#remote-setup" role="tab" aria-controls="remote-setup" aria-selected="false">
                    {{ $t('label.remote_setup') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'catapult' ? 'active' : ''" @click="activePane = 'catapult'" id="contact-tab" href="#catapult-db-setup" role="tab" aria-controls="catapult-db-setup" aria-selected="false">
                    {{ $t('label.catapult_db_setup') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="activePane === 'api' ? 'active' : ''" @click="activePane = 'api'" id="contact-tab" href="#api-setup" role="tab" aria-controls="api-setup" aria-selected="false">
                    {{ $t('label.api_setup') }}
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <licensing v-show="activePane === 'licensing'"></licensing>
            <remote-setup v-show="activePane === 'remote'"></remote-setup>
            <catapult-db-setup v-show="activePane === 'catapult'"></catapult-db-setup>
            <api-setup v-show="activePane === 'api'"></api-setup>
        </div>
    </div>
</template>

<script>
    import Licensing from './Tabs/Licensing.vue';
    import RemoteSetup from './Tabs/RemoteSetup.vue';
    import CatapultDBSetup from './Tabs/CatapultDBSetup.vue';
    import APISetup from './Tabs/APISetup.vue';

    export default {
        components: {
            'licensing': Licensing,
            'remote-setup': RemoteSetup,
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
