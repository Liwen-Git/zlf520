/**
 * First, we will load all of this project's Javascript utilities and other
 * dependencies. Then, we will be ready to develop a robust and powerful
 * application frontend using useful Laravel and JavaScript libraries.
 */

import './bootstrap'

import 'babel-polyfill'
import NProgress from 'nprogress'

import Vue from 'vue'
window.Vue = Vue;

import routes from './routes/index'
import VueRouter from 'vue-router'
const router = new VueRouter({
    mode: 'history',
    base: '/admin',
    routes
});
router.beforeEach((to, from, next) => {
    NProgress.start();
    // 处理服务器重定向到指定页面时在浏览器返回页面为空的问题
    if(to.query._from){
        NProgress.done();
        next(to.query._from);
    }else {
        next()
    }
});
router.afterEach(() => {
    NProgress.done()
});
window.router = router;
Vue.use(VueRouter);

import ElementUI from 'element-ui'
Vue.use(ElementUI);

import store from './store/index'

import page from './components/page'
Vue.component('page', page);

window.baseApiUrl = '/api/admin/';
import api from '../assets/js/api'
window.api = api;

import App from './App.vue'
new Vue({
    el: '#app',
    template: '<App/>',
    router,
    store,
    components: {App}
}).$mount('#app');
