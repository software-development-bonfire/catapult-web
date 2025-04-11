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
            version: {
                type: String,
            },
            loginUser: {
                type: String,
            },
            branchCode: {
                type: String,
            }
        },
        
        created() {
            this.$store.commit('SET_USER_PERMISSIONS', this.userPermissions);
            this.$store.commit('SET_PERMISSION_LIST', this.permissionList);
            this.$store.commit('SET_LOGIN_USER', this.loginUser);
            this.$store.commit('SET_BRANCH_CODE', this.branchCode)
        }
    }
</script>
