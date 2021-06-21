/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import axios from 'axios'
import Vue from 'vue';
import VueInternationalization from 'vue-i18n';
import Locale from './vue-i18n-locales.generated';
import VueInputMask from "vue-inputmask";

Vue.use(VueInternationalization);

const lang = document.documentElement.lang.substr(0, 2);

const i18n = new VueInternationalization({
    locale: lang,
    messages: Locale
});

Vue.use(VueInputMask.default);

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
Vue.component('field-mapping-setup-list', require('./views/FieldMappingSetup/List.vue').default);
Vue.component('field-mapping-setup-detail', require('./views/FieldMappingSetup/Detail.vue').default);
Vue.component('sync-interval-setting', require('./views/SyncIntervalSetting/List.vue').default);
Vue.component('user-account', require('./views/UserAccount/List.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
    i18n
});