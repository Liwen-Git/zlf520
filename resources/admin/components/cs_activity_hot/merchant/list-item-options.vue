<template>
    <!-- 商品列表项操作 -->
    <div>
        <el-button type="text" @click="changeStatus">{{scope.row.hot_status === 1 ? '下架' : '上架'}}</el-button>
        <el-button type="text" @click="check">活动商品管理</el-button>
    </div>

</template>

<script>
    import api from '../../../../assets/js/api'

    export default {
        name: "list-item-options",
        props: {
            scope: {type: Object, required: true},
            query: {type: Object, query: true},
            isFirst: {type: Boolean, default: false},
            isLast: {type: Boolean, default: false},
        },
        data(){
            return {
                isEdit: false,
                unAudit:false,
            }
        },
        computed: {

        },
        methods: {
            edit(){
                router.push({
                    path: '/goods/edit',
                    query: {id: this.scope.row.id}
                });
                return false;
                this.isEdit = true;
            },
            check() {
                router.push({
                    path: '/activity_hot/cs_goods',
                    query: {cs_merchant_id: this.scope.row.id}
                });
            },
            audit() {
                router.push({
                    path: '/cs_goods/audit',
                    query: {id: this.scope.row.id}
                });
            },
            goodsChange() {
                this.$emit('refresh')
            },
            //type: 1-审核通过  2-审核不通过  3-审核不通过并打回到商户池
            fastAudit(scope, type){
                if(type==2 ||type==1){
                    scope.row.type = type;
                    this.unAudit = true;
                }

            },
            doEdit(data){
                this.$emit('before-request')
                api.post('/goods/edit', data).then((data) => {
                    this.isEdit = false;
                    this.$emit('change', this.scope.$index, data)
                }).finally(() => {
                    this.$emit('after-request')
                })
            },
            changeStatus(){
                this.$emit('before-request')
                api.post('/cs/activity_hot/cs_merchant/changeHotStatus', {id: this.scope.row.id, hot_status: this.scope.row.hot_status}).then((data) => {
                    this.$message.success('操作成功' );
                    this.scope.row.hot_status = data.hot_status;
                }).finally(() => {
                    this.$emit('after-request')
                })
            },
            del(){
                let data = this.scope.row;
                this.$confirm(`确定要删除商品 ${data.goods_name} 吗? `, '温馨提示', {type: 'warning'}).then(() => {
                    this.$emit('before-request')
                    api.post('/goods/del', {id: data.id}).then(() => {
                        this.$emit('refresh')
                    }).finally(() => {
                        this.$emit('after-request')
                    })
                })
            },
            saveOrder(row, type) {
                api.post('/cs/activity_hot/changeSort', {id: row.id, type: type}).then(() => {
                    this.$emit('refresh');
                })
            },
        },
        components: {
        }
    }
</script>

<style scoped>

</style>