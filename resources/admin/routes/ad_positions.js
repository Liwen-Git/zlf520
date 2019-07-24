import Home from '../components/home'
import AdPositionList from '../components/ad-position/list'

/**
 * ad_positions 模块
 */
export default [
    {
        path: '/',
        component: Home,
        children: [
            {path: '/ad_positions', component: AdPositionList, name: 'AdPositionList'},
        ]
    },
];