/**
 * 引入 lodash工具 js原生库
 */
import _ from 'lodash';
window._ = _;

/**
 * 引入 JavaScript 日期处理类库
 */
import moment from 'moment';
window.moment = moment;

/**
 * 引入 cookie插件
 */
import Cookies from 'js-cookie';
window.Cookies = Cookies;

/**
 * 引入 lockr并全局挂载; [本地存储localStorage的最小API]
 */
import Lockr from 'lockr';
window.Lockr = Lockr;
// 设置Lockr前缀
Lockr.prefix = 'admin_';

/**
 * 引入 axios; 一个基于 promise 的 HTTP 库 [这个axios在assets/js/api.js中封装了一个api，这里的axios没用到]
 */
import axios from 'axios';
window.axios = axios;
// 修改axios全局默认配置
axios.defaults.timeout = 1000 * 15;
axios.defaults.headers.authKey = Lockr.get('authKey');
axios.defaults.headers.sessionId = Lockr.get('sessionId');
axios.defaults.headers['Content-Type'] = 'application/json';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * 如果当前页面在iframe中，就把父页面的url替换成子页面的，跳转到子页面。
 */
if (window.top !== window) {
    window.top.location = window.location;
}
