<template>
    <div class="module-container">
        <ul class="nav nav-tabs nav-tabs--black" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="licensing-tab" data-toggle="tab" href="#licensing" role="tab" aria-controls="licensing" aria-selected="true">
                    Licensing
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="remote-setup-tab" data-toggle="tab" href="#remote-setup" role="tab" aria-controls="remote-setup" aria-selected="false">
                    Remote Setup
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#catapult-db-setup" role="tab" aria-controls="catapult-db-setup" aria-selected="false">
                    Catapult DB Setup
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#api-setup" role="tab" aria-controls="api-setup" aria-selected="false">
                    API Setup
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <licensing></licensing>
            <remote-setup></remote-setup>
            <catapult-db-setup></catapult-db-setup>
            <api-setup></api-setup>
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
        data() {
            return {}
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
