<template>
    <div>
        <router-view></router-view>
    </div>
</template>

<script>
    import {mapState} from 'vuex'

    export default {
        data() {
            return {

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