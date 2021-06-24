<script>
    export default {
        methods: {
            $hasPermissionTo(permissions) {
                let that = this;

                let userPermissions = that.$store.getters.userPermissions;

                let permissionList = that.$store.getters.permissionList;

                if (typeof(permissions) === 'string') {
                    permissions = permissions.split('|');
                } else if (typeof(permissions) === 'object') {
                    permissions = Object.values(permissions);
                }

                let rootMasterAccount = that.$root.$children.filter(element => ('master' in element.$attrs))[0];
                let rootSuperAdmin = that.$root.$children.filter(element => ('superadmin' in element.$attrs))[0];

                if (rootSuperAdmin.$attrs.superadmin) return true;

                const superadmin = permissions.includes('superadmin') && this.$parent.$attrs.superadmin;
                const generalAdministrator = userPermissions.includes(permissionList['general.administrator']);
                const masterAccount = permissions.includes('master') && rootMasterAccount.$attrs.master;

                if (
                    superadmin
                    || (! permissions.includes('superadmin') && generalAdministrator)
                    || (! permissions.includes('superadmin') && masterAccount)
                ) {
                    return true;
                }

                return this.isPermissible(permissions, userPermissions, permissions.length > 1 ? 'or' : null);
            },

            getPermissionCode(permission) {
                let permissionList = this.$store.getters.permissionList;

                if (permissionList.hasOwnProperty(permission)) {
                    return permissionList[permission];
                }
                return null;
            },

            isPermissible(permissions, userPermissions, operator) {
                let permissible = false;

                for (let index = 0; index != permissions.length; index++) {
                    let permission = permissions[index];

                    if (permission.includes('&')) {
                        permissible = this.isPermissible(permission.split('&'), userPermissions, 'and');
                    } else {
                        let code = this.getPermissionCode(permission);

                        permissible = userPermissions.includes(code);

                        if (
                            permissions.length > 1
                            && (permissible && operator === 'or')
                                || (! permissible && operator === 'and')
                        ) {
                            break;
                        }
                    }
                }

                return permissible;
            }
        },
    };
</script>
