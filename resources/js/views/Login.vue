<template>
    <div class="login">
        <div class="login-form-icon">
            <i class="fa fa-gears fa-2x"></i>
        </div>
        <div class="login-form shadow-sm">
            <div class="form-group" align="center">
                <div class="catapult-logo">
                    <span class="catapult-logo-letter--main">C</span>
                    <span class="catapult-logo-letter--sub">atapult</span>
                </div>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" placeholder="Username">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" placeholder="Password">
            </div>
            <div>
                <button class="button button-default button-login" @click="login">Login</button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        methods: {
            login() {
                window.location.href = 'dashboard';
            }
        }
    }
</script>

<style lang="scss" scoped>
    .login {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        .form-control {
            font-size: 16px;
        }
    }
    .login-form {
        width: 360px;
        border: 1px #ced6e0 solid;
        border-bottom: 6px #DE0900 solid;
        background-color: #fff;
        padding: 35px 25px 25px 25px;
        border-radius: 6px;
        position: relative;
    }
    .login-form-icon {
        background-color: #fff;
        border-radius: 100px;
        color: darken(#909090, 10%);
        border: 1px #ced6e0 solid;
        display: inline-block;
        height: 70px;
        width: 70px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        top: 32px;
        z-index: 1;
    }
    .button-login {
        background-color: #f1f2f6;
        border-color:#ced6e0;
        text-transform: uppercase;
        width: 100%;
        padding: 10px;
        font-size: 18px;
        &:hover {
            background-color: lighten(#f1f2f6, 2%);
        }
    }
    .catapult-logo {
        font-family: 'Montserrat-Bold';
        text-transform: uppercase;
        display: flex;
        justify-content: center;
        align-items: center;
        &-letter {
            &--main {
                color:#DE0900;
                font-size: 32px;
            }
            &--sub {
                font-size: 25px;
            }
        }
    }
</style>