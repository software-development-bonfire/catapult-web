/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import moment from 'moment';
import Vue from 'vue';
import VueInternationalization from 'vue-i18n';
import VueInputMask from "vue-inputmask";
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import Vuex from 'vuex';
import HasPermission from '../js/mixins/HasPermission';
import Util from '../js/mixins/Util';
import Locale from './vue-i18n-locales.generated';

Vue.use(VueInternationalization);
Vue.use(Vuex);
Vue.use(Loading);
Vue.component('v-select', vSelect);

const lang = document.documentElement.lang.substr(0, 2);

const i18n = new VueInternationalization({
    locale: lang,
    messages: Locale
});

Vue.use(VueInputMask.default);

Vue.filter('formatDate', function(value) {
    if (value) {
        return moment(String(value)).format('MM/DD/YYYY')
    }
});

const store = new Vuex.Store({
    state: {
        count: 0,
        userPermissions: Array,
        permissionList: Array,
        decimalPlaces: Array,
        subscription: Array
    },
    mutations: {
        SET_USER_PERMISSIONS: (state, value) => {
            state.userPermissions = value;
        },

        SET_PERMISSION_LIST: (state, value) => {
            state.permissionList = value;
        },
        SET_LOGIN_USER: (state, value) => {
            state.loginUser = value;
        },
    },
    getters: {
        userPermissions: (state) => {
            return state.userPermissions
        },
        permissionList: (state) => {
            return state.permissionList
        },        
        loginUser: (state) => {
            return state.loginUser
        },
    }
});

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i);
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));

Vue.component('top-navigation', require('./layouts/TopNavigation.vue').default);
Vue.component('side-navigation', require('./layouts/SideNavigation.vue').default);
Vue.component('footer-panel', require('./layouts/FooterPanel.vue').default);
Vue.component('core', require('./views/Core.vue').default);
Vue.component('login', require('./views/Login.vue').default);
Vue.component('dashboard', require('./views/Dashboard.vue').default);
Vue.component('configurations', require('./views/Configurations/List.vue').default);
Vue.component('syncing-setup', require('./views/SyncingSetup/List.vue').default);
Vue.component('field-mapping-preset-list', require('./views/FieldMappingPreset/List.vue').default);
Vue.component('field-mapping-preset-detail', require('./views/FieldMappingPreset/Detail.vue').default);
Vue.component('sync-interval-setting', require('./views/SyncIntervalSetting/List.vue').default);
Vue.component('user-account', require('./views/UserAccount/List.vue').default);
Vue.component('field-mapping-list', require('./views/FieldMapping/List.vue').default);
Vue.component('field-mapping-detail', require('./views/FieldMapping/Detail.vue').default);
Vue.component('logs', require('./views/Logs/List.vue').default);

Vue.mixin(HasPermission);
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
    store,
    i18n,
    mixins: [ Util ],
    components: {
        Loading
    },
    data() {
        return {
            isLoading: false,
            moduleResponse: {},
            tabUuid: null,
            headerTitle: ''
        }
    },
    mounted() {
        this.tabUuid = this.uuid();
    },
    methods: {
        processing(state, custom = {}) {
            this.isLoading = state;

            if (!_.isEmpty(custom)) {
                let loadingLabel = `
                    <div class="vld-header-title">
                        ` + custom.headerTitle + `
                        <div class="vld-loading-title">
                            ` + this.$t('label.please_wait') + `<span class="vld-loading-dots"></span>
                        </div>
                    </div>`;

                document.querySelector('.vld-background').insertAdjacentHTML('afterend', loadingLabel);
            }

            if (! state && document.querySelector('.vld-header-title') != null) {
                document.querySelector('.vld-header-title').remove();
            }
        },
        resizableWidth(type) {
            let viewWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);

            switch (type) {
                case 'w':
                    return viewWidth / 2;
                case 'min':
                    return viewWidth / 4;
                case 'max':
                    return viewWidth - (viewWidth / 4);
                default:
                    break;
            }
        },
    },
});
