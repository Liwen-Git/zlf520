let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.setPublicPath('public');

mix.js('./resources/admin/app.js', 'public/js/admin.js');

mix.styles([
    'node_modules/element-ui/lib/theme-chalk/index.css', // element-ui 的css样式
    'node_modules/nprogress/nprogress.css', // 进度条的css样式
    'resources/assets/css/base.css', // 通用样式
    'resources/assets/css/global.css', // 通用样式
], 'public/css/all.css');

mix.copy('node_modules/element-ui/lib/theme-chalk/fonts', 'public/css/fonts/');

mix.extract([
    'axios',
    'lockr',
    'lodash',
    'vue',
    'vue-router',
    'element-ui',
]);

mix.options({
    postCss: [
        require('autoprefixer'),
    ],
})

mix.version();
