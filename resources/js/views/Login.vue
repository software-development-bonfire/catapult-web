<template>
    <div class="login">
        <div class="login-form-icon">
            <i class="fa fa-gears fa-2x"></i>
        </div>
        <div class="login-form shadow-sm">
            <form @submit.prevent="login()">
                <div class="form-group">
                    <div class="catapult-logo justify-content-center">
                        <span class="catapult-logo-letter--main">C</span>
                        <span class="catapult-logo-letter--sub">atapult</span>
                    </div>
                </div>
                <div align="center" v-if="errors.hasOwnProperty('username')">
                    <label class="text-danger error-message">
                        {{ errors.username[0] }}
                    </label>
                </div>
                <div class="form-group">
                    <input
                        type="text"
                        v-model="form.login.username"
                        class="form-control"
                        :placeholder="$t('label.username')"
                        :class="{ 'is-invalid': errors.hasOwnProperty('username') }">
                </div>
                <div class="form-group">
                    <input
                        type="password"
                        v-model="form.login.password"
                        class="form-control"
                        :placeholder="$t('label.password')"
                        :class="{ 'is-invalid': errors.hasOwnProperty('username') }">
                </div>
                <div>
                    <button class="button button-default button-login" type="submit">{{ $t('label.login') }}</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    export default {
        created() {
            
        },
        data() {
            return {
                form: {
                    login: {
                        username: '',
                        password: '',
                    }
                },
                errors: {},
            }
        },
        methods: {
            login() {
                axios.post('/login', this.form.login)
                .then(response => {
                    if (response.data.some(element => element === 110101)) {
                        window.location.href = 'dashboard';
                    } else if (response.data.some(element => element === 110301)) {
                        window.location.href = 'user-account';
                    } else {
                        window.location.href = 'logs';
                    }
                    this.errors = {}
                }).catch(error => {
                    this.errors = error.response.data.errors
                })
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
</style>