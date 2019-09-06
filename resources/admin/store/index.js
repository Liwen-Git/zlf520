import Vue from 'vue';
import Vuex from 'vuex';
Vue.use(Vuex);

/**
 * 去掉菜单中的url前缀
 * @param menus
 * @param prefix
 * @returns {*}
 */
let trimMenuUrlPrefix = function (menus, prefix = '/admin') {
    menus.forEach((menu) => {
        if (menu.menu_url && menu.menu_url.indexOf(prefix) === 0) {
            menu.menu_url = menu.menu_url.substr(prefix.length);
        }
        if (menu.sub && menu.sub.length > 0) {
            trimMenuUrlPrefix(menu.sub);
        }
    });
    return menus;
};

/**
 * 解决页面刷新 vuex数据丢失问题
 * @type {string}
 */
const STATE_KEY = 'state';
// 状态本地存储插件
const stateLocalStoragePlugin = function (store) {
    // 当 store 初始化后调用
    let state = Lockr.get(STATE_KEY);
    if (state) {
        store.commit('setGlobalLoading', state.globalLoading);
        store.commit('setUser', state.user);
        store.commit('setMenus', state.menus);
        store.commit('setRules', state.rules);
    }

    store.subscribe((mutation, state) => {
        // 每次 mutation 之后调用
        // mutation 的格式为 { type, payload }
        Lockr.set(STATE_KEY, state);
    })
};

/**
 * 实例化vuex
 */
export default new Vuex.Store({
    strict: process.env.NODE_ENV !== 'production',
    state: {
        globalLoading: false,
        user: null,
    },
    mutations: {
        setGlobalLoading(state, loading) {
            //这里的state对应着上面这个state
            state.globalLoading = loading;
        },
        setUser(state, user) {
            state.user = user;
        },
    },
    /** 官方推荐 异步操作放在 actions 中 */
    actions: {
        openGlobalLoading(context) {
            //这里的context和我们使用的this.$store拥有相同的对象和方法
            context.commit('setGlobalLoading', true);
        },
        closeGlobalLoading(context) {
            context.commit('setGlobalLoading', false);
        },
        // 登录的时候存储 用户、菜单、权限
        storeUserAndMenus(context, {user, menus, rules, forbiddenRules}) {
            menus = trimMenuUrlPrefix(menus);
            Lockr.set('user', user);
            context.commit('setUser', user);
            context.commit('setMenus', menus);
            context.commit('setRules', rules);
        },
        // 退出登录
        clearUserAndMenus(context) {
            Lockr.rm('user');
            context.commit('setUser', null);
            context.commit('setMenus', []);
            context.commit('setRules', []);
        }
    },
    plugins: [
        stateLocalStoragePlugin,
    ]
});