<template>
    <div class="core">
        <slot></slot>
    </div>
</template>

<style lang="scss" scoped>
    .core {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
</style>

<script>
    export default {
        props: {
            userPermissions: {
                type: Array,
            },
            permissionList: {
                type: Object,
            },
        },
        
        created() {
            this.$store.commit('SET_USER_PERMISSIONS', this.userPermissions);
            this.$store.commit('SET_PERMISSION_LIST', this.permissionList);
        }
    }
</script>
