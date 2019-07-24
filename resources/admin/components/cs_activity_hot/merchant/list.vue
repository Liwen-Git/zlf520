<template>
    <page title="活动商户管理" v-loading="isLoading">
        <el-col>
            <el-form v-model="query" inline>
                <el-form-item prop="cs_merchant_id" label="商户ID" >
                    <el-input v-model="query.cs_merchant_id"  placeholder="商户ID" clearable></el-input>
                </el-form-item>
                <el-form-item prop="merchant_name" label="商户名称" >
                    <el-input v-model="query.merchant_name"  placeholder="商户名称" clearable></el-input>
                </el-form-item>
                <el-form-item prop="oper_name" label="运营中心名称" >
                    <el-input v-model="query.oper_name"  placeholder="运营中心名称" clearable></el-input>
                </el-form-item>
                <el-form-item label="商户活动状态" prop="hot_status">
                    <el-select v-model="query.hot_status"  multiple placeholder="请选择" class="w-150">
                        <el-option label="上架" value="1"/>
                        <el-option label="下架" value="0"/>
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary"  @click="search"><i class="el-icon-search">搜 索</i></el-button>
                </el-form-item>
                <el-form-item>
                    <el-button type="success" @click="downloadExcel">导出Excel</el-button>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="add">添加商户</el-button>
                </el-form-item>
            </el-form>
        </el-col>

        <el-table :data="list" stripe v-loading="dataLoading">
            <el-table-column prop="hot_add_time" label="加入活动时间"/>
            <el-table-column prop="id" label="商户ID"/>
            <el-table-column prop="name" label="商户名称"/>
            <el-table-column prop="province" label="城市">
                <template slot-scope="scope">
                    {{scope.row.province}} {{scope.row.city}}
                </template>
            </el-table-column>
            <el-table-column prop="oper.name" label="运营中心名称">
                <template slot-scope="scope">
                    {{scope.row.oper ? scope.row.oper.name : ''}}
                    <el-button type="text"
                               v-if="!query.oper_id && scope.row.oper"
                               @click="checkThisOper(scope)"
                    >只看他的</el-button>
                </template>
            </el-table-column>

            <el-table-column prop="hot_status" label="活动状态">
                <template slot-scope="scope">
                    <span v-if="parseInt(scope.row.hot_status) === 1" class="c-green">上架</span>
                    <div v-else-if="parseInt(scope.row.hot_status) === 0"  slot="reference" class="c-danger"><p>下架</p></div>
                    <span v-else>未知 ({{scope.row.hot_status}})</span>
                </template>
            </el-table-column>
            <el-table-column prop="hot_goods_count" label="活动商品数"/>
            <el-table-column label="操作" width="250px">
                <template slot-scope="scope">
                    <list-item-options
                            :scope="scope"
                            :query="query"
                            :isFirst="isFirstPage && scope.$index == 0"
                            :isLast="isLastPage && scope.$index == list.length - 1"
                            @change="itemChanged"
                            @refresh="getList"/>
                </template>
            </el-table-column>
        </el-table>
        <el-pagination
                class="fr m-t-20"
                layout="total, prev, pager, next"
                :current-page.sync="query.page"
                @current-change="getList"
                :page-size="query.pageSize"
                :total="total"/>
        <el-dialog
                title="选择商户"
                :visible.sync="addDialog"
                center
                width="20%"
                :close-on-click-modal="false"
                :close-on-press-escape="false"
        >
            <el-form :model="addForm" ref="addForm">
                <el-form-item>
                    <template>
                        <el-select
                                size="medium"
                                :style="{display: 'block'}"
                                v-model="addForm.cs_merchant_id"
                                filterable
                                remote
                                reserve-keyword
                                placeholder="输入商户ID或名称"
                                :remote-method="remoteMethod"
                                :loading="loading">

                            <el-option
                                    v-for="item in options4"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                    :disabled="item.disabled">
                            </el-option>
                        </el-select>
                    </template>
                </el-form-item>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button @click="cancel">取 消</el-button>
                <el-button type="primary" @click="commit">确定加入</el-button>
            </span>
        </el-dialog>
    </page>
</template>

<script>
    import api from '../../../../assets/js/api'

    import ListItemOptions from './list-item-options'

    export default {
        name: "cs-goods-list",
        data(){
            return {
                isAdd: false,
                isLoading: false,
                dataLoading: false,
                query: {
                    goods_name:'',
                    id:'',
                    merchant_name:'',
                    oper_name:'',
                    cs_merchant_id:'',
                    oper_id:'',
                    status:'',
                    hot_status:'',
                    auditStatus:'',
                    cs_platform_cat_id_level1:'',
                    cs_platform_cat_id_level2:'',
                    page: 1,
                    pageSize: 15,
                },
                list: [],
                total: 0,
                cs_platform_cat_id_level1:[],
                cs_platform_cat_id_level2:[],
                addDialog: false,
                addForm:{
                    cs_merchant_id:'',

                },
                options4: [],
                loading: false,

            }
        },
        computed: {
            isFirstPage(){
                return this.query.page == 1;
            },
            isLastPage(){
                return this.query.page * this.query.pageSize >= this.total;
            }
        },
        methods: {
            getLevel1() {
                api.get('/cs/sub_cat', {parent_id:0}).then(data => {

                    this.cs_platform_cat_id_level1 = data;
                })
            },
            getLevel2() {
                if (this.query.cs_platform_cat_id_level1 == 0) {
                    return true;

                }
                api.get('/cs/activity_hot/sub_cat', {parent_id:this.query.cs_platform_cat_id_level1}).then(data => {
                    this.query.cs_platform_cat_id_level2 = ''
                    this.cs_platform_cat_id_level2 = data;
                })
            },
            getList(){
                this.dataLoading = true;
                api.get('/cs/activity_hot/cs_merchants', this.query).then(data => {
                    this.list = data.list;
                    this.total = data.total;
                    this.dataLoading = false;
                })
            },
            itemChanged(index, data){
                this.getList();
            },
            add(){
                this.addDialog=true;
            },
            cancel(){
                this.options4 = [];
                this.addForm.cs_merchant_id = '';
                this.addDialog= false;
            },
            commit(){
                api.post('/cs/activity_hot/cs_merchant/addHotMerchants', this.addForm ).then(data=>{
                    this.addDialog=false;
                    this.$message.success('添加成功' )
                    this.options4 = [];
                    this.getList();
                });
                console.log('addForm',this.addForm);
            },
            remoteMethod(query) {
                if (query !== '') {
                    this.loading = true;
                    let params = {};
                    params.cs_merchant_keywords = query;
                    api.post('/cs/activity_hot/cs_merchant/search', params ).then(data=>{
                        this.loading=false;
                        this.options4 = data;
                    });
                } else {
                    this.options4 = [];
                }

            },
            doAdd(data){
                this.isLoading = true;
                api.post('/cs/goods/add', data).then(() => {
                    this.isAdd = false;
                    this.$refs.addForm.resetForm();
                    this.getList();
                }).finally(() => {
                    this.isLoading = false;
                })
            },
            previewImage(event){
                event.stopPropagation()
                //预览商品图片
                const viewer = event.currentTarget.$viewer
                viewer.show()
                return
            },
            checkThis(scope) {

                this.query.cs_merchant_id = scope.row.cs_merchant_id
                this.query.merchant_name = scope.row.cs_merchant.name
                this.getList();
            },
            checkThisOper(scope) {

                this.query.oper_id = scope.row.oper_id
                this.query.oper_name = scope.row.oper.name
                this.getList();
            },
            search() {
                this.query.oper_id = '';
                this.getList();
            },
            downloadExcel() {
                let message = '确定要导出当前筛选的商户列表么？';

                this.$confirm(message).then(() => {
                    let data = this.query;
                    let params = [];
                    Object.keys(data).forEach((key) => {
                        let value =  data[key];
                        if (typeof value === 'undefined' || value == null) {
                            value = '';
                        }
                        params.push([key, encodeURIComponent(value)].join('='))
                    }) ;
                    let uri = params.join('&');

                    location.href = `/api/admin/cs/activity_hot/cs_merchant/download?${uri}`;
                })
            },

        },
        created(){
            if(this.$route.query.merchantName!==undefined){
                this.query.merchant_name = this.$route.query.merchantName;
            }
            this.getList();
        },
        components: {
            ListItemOptions,
        }
    }
</script>

<style scoped>

</style>