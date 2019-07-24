<template>
    <page title="编辑商品" :breadcrumbs="{商品管理: '/goods'}">
        <el-col :span="16">
            <ad-form
                    v-if="goods"
                    :data="goods"
                    @cancel="cancel"
                    @save="doEdit"/>
        </el-col>
    </page>
</template>

<script>
    import api from '../../../assets/js/api'
    import AdForm from './ad-form'
    export default {
        name: "add",
        data(){
            return {
                id: null,
                goods: null,
            }
        },
        methods: {
            cancel(){
                router.push('/goods');
            },
            doEdit(data){
                api.post('/goods/edit', data).then((data) => {
                    router.push('/goods');
                }).finally(() => {

                })
            },
            getDetail(){
                api.get('/goods/detail', {id: this.id}).then(data => {
                    this.goods = data;
                });
            }
        },
        created(){
            this.id = this.$route.query.id;
            if(!this.id){
                this.$message.error('id不能为空');
                router.push('/goods');
                return false;
            }
            this.getDetail();
        },
        components: {
            AdForm
        }
    }
</script>

<style scoped>

</style>