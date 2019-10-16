<template>
    <el-row style="min-height: 100%">
        <el-col :span="18" :offset="3" style="padding-bottom: 20px;">
            <el-container>
                <el-header>
                    <el-menu
                            :default-active="activeMenu"
                            router
                            mode="horizontal"
                            background-color="#545c64"
                            text-color="#fff"
                            active-text-color="#ffd04b">
                        <el-menu-item index="/welcome"><i class="el-icon-s-home"></i>主页</el-menu-item>
                        <el-submenu index="2">
                            <template slot="title">对象存储</template>
                            <el-menu-item index="/qiniu/upload">上传</el-menu-item>
                            <el-menu-item index="/qiniu/preview">预览</el-menu-item>
                        </el-submenu>
                        <el-submenu :index="user.name" style="float:right;">
                            <template slot="title">{{user.name}}</template>
                            <el-menu-item @click="logout">退出</el-menu-item>
                        </el-submenu>
                    </el-menu>
                </el-header>
                <el-main>
                    <router-view></router-view>
                </el-main>
            </el-container>
        </el-col>
        <el-footer class="home-footer">
            <span>© 2018-2019 Powered By</span>
            <a href="https://github.com/Liwen-Git" target="_blank">liwen</a> |
            <a href="http://www.beian.miit.gov.cn/" target="_blank">粤ICP备19082463号</a>
        </el-footer>
    </el-row>
</template>

<script>
    import {mapState} from 'vuex'

    export default {
        data() {
            return {
                activeMenu: '/welcome',
            }
        },
        computed: {
            ...mapState([
                'user',
            ])
        },
        methods: {
            logout() {
                this.$confirm('确认退出吗?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消'
                }).then(() => {
                    api.post('/logout').then(() => {
                        this.$message.success('退出成功')
                    });
                    this.$store.dispatch('clearUser');
                    this.$router.replace('/login');
                }).catch(() => {

                })
            }
        },
        created() {
            // 登录验证
            if (!this.user) {
                this.$message.warning('您尚未登录');
                this.$router.replace('/login');
                return;
            }
            if (this.$route.path == '/') {
                this.$router.replace('/welcome');
            }
            // 刷新 高亮菜单显示
            this.activeMenu = this.$route.path;
        }
    }
</script>

<style scoped>
    .home-footer {
        height: 20px !important;
        bottom: 0;
        width: 100%;
        text-align: center;
        margin: 5px 0;
        font-size: 14px;
        position: absolute;
    }
</style>