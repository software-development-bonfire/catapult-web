<template>
    <div class="module-container">
    </div>
</template>

<style lang="scss" scoped>
    .top-navigation {
        border-bottom: 1px #ced6e0 solid;
        background-color: #fff;
        padding: 5px 15px;
        position: fixed;
        width: 100%;
        z-index: 1;
        .navbar-brand {
            padding: 0px;
        }
        .dropdown-item.active,
        .dropdown-item:active {
            background-color: #DE0900;
        }
        .user-menu {
            display: flex;
            align-items: center;
        }
        .navbar-nav .nav-link {
            color: #212529;
            &:hover {
                color: darken(#212529, 10%);
            }
        }
        .active-user {
            font-size: 16px;
            i {
                margin-left: 5px; 
            }
        }
    }
    .sidebar-navigation {
        position: fixed;
        width: 18%;
        margin-top: 62px;
        border: 1px #ced6e0 solid;
        bottom: 0;
        top: 0;
        background: #fff;
        overflow: auto;
    }
    .main-content {
        margin-top: 62px;
        margin-left: 18%;
        margin-bottom: 43px;
        border: 1px red solid;
        position: fixed;
        top: 0;
        width: 82%;
        bottom: 0;
        overflow: auto;
    }
    .footer-panel {
        position: fixed;
        padding: 10px;
        margin-left: 18%;
        bottom: 0;
        background-color: #fff;
        width: 82%;
        text-align: right;
    }
</style>

<script>
    export default {}
</script>
