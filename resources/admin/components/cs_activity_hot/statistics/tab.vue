<template>
    <page title="活动统计" :breadcrumbs="{年货节活动: '/activity_hot/list'}">
        <el-tabs v-model="activeName" type="card" @tab-click="handleClick">
            <el-tab-pane label="商品" name="goods">
                <goods ref="goods"></goods>
            </el-tab-pane>
            <el-tab-pane label="商户" name="merchant">
                <merchant ref="merchant"></merchant>
            </el-tab-pane>
            <el-tab-pane label="运营中心" name="oper">
                <oper ref="oper"></oper>
            </el-tab-pane>
        </el-tabs>
    </page>
</template>

<script>
    import Goods from './goods'
    import Merchant from './merchant'
    import Oper from './oper'

    export default {
        data() {
            return {
                activeName: 'goods',
            }
        },
        methods: {
            handleClick() {
                if (this.activeName == 'goods') {
                    this.$refs.goods.getList();
                } else if (this.activeName == 'merchant') {
                    this.$refs.merchant.getList();
                } else {
                    this.$refs.oper.getList();
                }
            }
        },
        components: {
            Goods,
            Merchant,
            Oper
        }
    }
</script>

<style scoped>

</style>
