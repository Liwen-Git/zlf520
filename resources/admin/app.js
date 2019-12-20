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

// 路由相关
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

// vuex相关
import store from './store/index'
window.store = store;

// 全局引用page
import page from './components/page'
Vue.component('page', page);
// 全局引用image-upload
import ImageUpload from '../assets/components/image-upload'
Vue.component('ImageUpload', ImageUpload);

// 请求api
window.baseApiUrl = '/api/';
if (process.env.NODE_ENV === 'production') {
    window.miniApiUrl = 'http://127.0.0.1:8000/';
} else {
    window.miniApiUrl = 'http://127.0.0.1:8000/';
}
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
