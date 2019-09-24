<template>
    <el-row class="public-height-hundred">
        <el-col :span="18" :offset="3" class="public-height-hundred">
            <el-container class="public-height-hundred">
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
                            <template slot="title">我的工作台</template>
                            <el-menu-item index="2-1">选项1</el-menu-item>
                            <el-menu-item index="2-2">选项2</el-menu-item>
                            <el-menu-item index="2-3">选项3</el-menu-item>
                            <el-submenu index="2-4">
                                <template slot="title">选项4</template>
                                <el-menu-item index="2-4-1">选项1</el-menu-item>
                                <el-menu-item index="2-4-2">选项2</el-menu-item>
                                <el-menu-item index="2-4-3">选项3</el-menu-item>
                            </el-submenu>
                        </el-submenu>
                    </el-menu>
                </el-header>
                <el-main>
                    <el-card shadow="hover">
                        <router-view></router-view>
                    </el-card>
                </el-main>
            </el-container>
        </el-col>
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
                api.post('/logout').then(() => {

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
        }
    }
</script>

<style scoped>
    .public-height-hundred {
        height: 100%;
    }
</style>