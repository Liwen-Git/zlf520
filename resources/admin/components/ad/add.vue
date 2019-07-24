<template>
    <page title="添加广告" v-loading="isLoading" :breadcrumbs="breadcrumbs">
        <el-col :span="16">
            <ad-form
                    ref="addForm"
                    @cancel="cancel"
                    @save="doAdd"/>
        </el-col>
    </page>
</template>

<script>
    import api from '../../../assets/js/api'
    import AdForm from './ad-form'
    export default {
        name: "add",
        data() {
            return {
                breadcrumbs: {},
                isLoading: false,
            }
        },
        methods: {
            cancel(){
                router.push('/goods');
            },
            doAdd(data){
                this.isLoading = true;
                api.post('/ad/add', data).then(() => {
                    this.$message.success('添加成功');
                    this.$refs.addForm.resetForm();
                    router.push('/goods');
                }).finally(() => {
                    this.isLoading = false;
                })
            },
        },
        created(){
            this.breadcrumbs = {
                广告管理: '/ads',
            }
            let positionId = this.$route.query.positionId
            let positionName = this.$route.query.positionName
            this.breadcrumbs[positionName] = () => {
                router.push({
                    path: '/ads',
                    query: {positionId, positionName}
                })
            }
        },
        components: {
            AdForm
        }
    }
</script>

<style scoped>

</style>