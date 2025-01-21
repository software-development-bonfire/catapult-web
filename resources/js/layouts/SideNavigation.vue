<template>
    <div class="sidebar-navigation">
        <div class="list-group list-group-root list-group-flush well">
            <a
                v-if="this.$hasPermissionTo('view.dashboard')"
                href="/dashboard"
                class="list-group-item"
                :class="page === 'dashboard' ? 'active' : ''">
                <i class="side-link-icon fa fa-home fa-lg"></i>
                <span class="side-link-label">{{ $t('label.dashboard') }}</span>
            </a>
            <a
                href="/item-availability"
                class="list-group-item"
                :class="page === 'item-availability' ? 'active' : ''">
                <i class="side-link-icon fa fa-book fa-lg"></i>
                <span class="side-link-label">{{ $t('label.item_availability') }}</span>
            </a>
            <a
                v-if="this.$hasPermissionTo('view.configurations')"
                href="/configurations"
                class="list-group-item"
                :class="page === 'configurations' ? 'active' : ''">
                <i class="side-link-icon fa fa-gear fa-lg"></i>
                <span class="side-link-label">{{ $t('label.configurations') }}</span>
            </a>
            <a
                v-if="this.$hasPermissionTo('view.field-mapping')"
                href="/field-mapping"
                class="list-group-item"
                :class="page === 'field-mapping' ? 'active' : ''">
                <i class="side-link-icon fa fa-dot-circle-o fa-lg"></i>
                <span class="side-link-label">{{ $t('label.field_mapping') }}</span>
            </a>
            <a
                v-if="this.$hasPermissionTo('view.kitchen-device-printer')"
                href="/kitchen-device-printer"
                class="list-group-item"
                :class="page === 'kitchen-device-printer' ? 'active' : ''">
                <i class="side-link-icon fa fa-podcast fa-lg"></i>
                <span class="side-link-label">{{ $t('label.kitchen_device_printer') }}</span>
            </a>
            <a
                v-if="this.$hasPermissionTo('view.sync_interval_setting')"
                href="/sync-interval-setting"
                class="list-group-item"
                :class="page === 'sync-interval-setting' ? 'active' : ''">
                <i class="side-link-icon fa fa-refresh fa-lg"></i>
                <span class="side-link-label">{{ $t('label.sync_interval_setting') }}</span>
            </a>
            <a
                v-if="this.$hasPermissionTo('view.logs')"
                href="/logs"
                class="list-group-item"
                :class="page === 'logs' ? 'active' : ''">
                <i class="side-link-icon fa fa-list-alt fa-lg"></i>
                <span class="side-link-label">{{ $t('label.logs') }}</span>
            </a>
            <a
                v-if="this.$hasPermissionTo('view.user_account')"
                href="/user-account"
                class="list-group-item"
                :class="page === 'user-account' ? 'active' : ''">
                <i class="side-link-icon fa fa-user fa-lg"></i>
                <span class="side-link-label">{{ $t('label.user_account') }}</span>
            </a>
            <a
                v-if="this.$hasPermissionTo('view.admin_settings')"
                href="#admin-settings" class="list-group-item collapsed" aria-expanded="true" data-toggle="collapse">
                <i class="side-link-icon fa fa-shield fa-lg"></i>
                <span class="side-link-label">{{ $t('label.admin_settings') }}</span>
            </a>
            <div class="list-group collapse show" aria-expanded="true" id="admin-settings">
                <a
                v-if="this.$hasPermissionTo('view.syncing_setup')"
                    href="/syncing-setup"
                    class="list-group-item"
                    :class="page === 'syncing-setup' ? 'active' : ''">
                    {{ $t('label.syncing_setup') }}
                </a>
                <a
                v-if="this.$hasPermissionTo('view.field_mapping_setup')"
                    href="/field-mapping-preset"
                    class="list-group-item"
                    :class="page === 'field-mapping-preset' ? 'active' : ''">
                    {{ $t('label.field_mapping_preset') }}
                </a>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            page: {
                type: String
            }
        }
    }
</script>

<style lang="scss" scoped>
    .sidebar-navigation {
        border-right: 1px #ced6e0 solid;
        background: #fff;
        overflow: auto;
        width: 18%;

        .side-link {
            &-icon {
                color: #444444;
                margin-right: 5px;
            }
            &-label {
                color: #444444;
            }
        }

        .list-group-item {
            cursor: pointer;
            color: #444444;
            text-decoration: none;
            &:hover {
                background-color: #f5f5f5;
            }
            &.active {
                cursor: default;
                color: #444444;
                border-color: darken(#EBEBEB, 10%);
                background-color: #EBEBEB;
            }
        }

        .list-group.list-group-root {
            padding: 0;
            overflow: hidden;
        }

        .list-group.list-group-root .list-group {
            margin-bottom: 0;
        }

        .list-group.list-group-root .list-group-item {
            border-radius: 0;
            border-width: 1px 0 0 0;
        }

        .list-group.list-group-root > .list-group-item:first-child {
            border-top-width: 0;
        }

        .list-group.list-group-root > .list-group > .list-group-item {
            padding-left: 55px;
        }

        .list-group.list-group-root > .list-group > .list-group > .list-group-item {
            padding-left: 80px;
        }

        .list-group-item .fa {
            margin-right: 5px;
        }
    }
</style>
