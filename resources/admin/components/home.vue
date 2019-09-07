<template>
    <div>home</div>
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