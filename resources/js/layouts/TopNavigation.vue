<template>
    <nav class="top-navigation navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="#">
            <div class="catapult-logo">
                <span class="catapult-logo-letter--main">C</span>
                <span class="catapult-logo-letter--sub">atapult</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#top-navbar" aria-controls="top-navbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="top-navbar">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active-user" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Admin <i class="fa fa-user fa-lg"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" @click="logout()" href="/">{{ $t('label.logout') }}</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</template>

<script>
    export default {
        methods: {
            logout() {
                axios.post('/logout')
            }
        }
    }
</script>

<style lang="scss" scoped>
    .top-navigation {
        border-bottom: 1px #ced6e0 solid;
        background-color: #fff;
        padding: 5px 15px;
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
            color: #444444;
            &:hover {
                color: darken(#444444, 10%);
            }
        }
        .active-user {
            font-size: 16px;
            i {
                margin-left: 5px; 
            }
        }
    }
</style>