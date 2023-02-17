/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import axios from 'axios'
import HasPermission from '../js/mixins/HasPermission';
import Vue from 'vue';
import Vuex from 'vuex';
import VueInternationalization from 'vue-i18n';
import Locale from './vue-i18n-locales.generated';
import VueInputMask from "vue-inputmask";
import moment from 'moment'; 
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';

Vue.use(VueInternationalization);
Vue.use(Vuex);
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
    },
    getters: {
        userPermissions: (state) => {
            return state.userPermissions
        },
        permissionList: (state) => {
            return state.permissionList
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
    i18n
});
