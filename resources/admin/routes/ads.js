import Home from '../components/home'
import AdList from '../components/ad/list'
import AddAd from '../components/ad/add'

/**
 * ads 模块
 */
export default [
    {
        path: '/',
        component: Home,
        children: [
            {path: '/ads', component: AdList, name: 'AdList'},
            {path:'/ad/add',component:AddAd, name:'AddAd'},
        ]
    },
];