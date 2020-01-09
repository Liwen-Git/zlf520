import Login from '../components/login.vue'
import refresh from '../components/refresh.vue'
import Home from '../components/home.vue'
import ErrorPage from '../components/404.vue'
import welcome from '../components/welcome.vue'

// 七牛对象存储
import QiniuUpload from '../components/qiniu/upload'
import QiniuPreview from '../components/qiniu/preview'

// 小程序后台配置
import BookkeepingType from '../components/miniprogram/bookkeeping/type'
import StoryList from '../components/story/index'

/**
 *
 */
const routes = [

    {path: '/login', component: Login, name: 'Login'},

    // 七牛对象存储
    {
        path: '/',
        component: Home,
        children: [
            {path: 'qiniu/upload', component: QiniuUpload, name: 'qiniu-upload'},
            {path: 'qiniu/preview', component: QiniuPreview, name: 'qiniu-preview'},
        ]
    },

    // 小程序后台
    {
        path: '/',
        component: Home,
        children: [
            {path: 'mini/bookkeeping/type', component: BookkeepingType, name: 'bookkeeping-type'},
            {path: 'story/list', component: StoryList, name: 'story-list'},
        ]
    },

    {
        path: '/',
        component: Home,
        children: [
            // demo组件示例
            {path: 'welcome', component: welcome, name: 'welcome'},
            // 刷新组件
            {path: 'refresh', component: refresh, name: 'refresh'},
            // 拦截所有无效的页面到错误页面
            {path: '*', component: ErrorPage, name: 'ErrorPage'},
        ]
    },

    // 拦截所有无效的页面到错误页面
    { path: '*' , component: ErrorPage, name: 'GlobalErrorPage'}

];

export default routes
