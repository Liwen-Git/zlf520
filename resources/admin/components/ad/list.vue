<template>
    <page :title="title" v-loading="isLoading" :breadcrumbs="breadcrumbs">
        <p v-if="positionId">
            <router-link :to="{ name:'AddAd',query: { positionId:  positionId, positionName: title }}" >
                <el-button class="add-ad" type="primary">增加广告</el-button>
            </router-link>
        </p>
        <el-table :data="list" stripe>
            <el-table-column prop="id" label="位置"/>
            <el-table-column prop="name" label="广告名称"/>
            <el-table-column prop="desc" label="广告说明"/>
            <el-table-column prop="image" label="图片">
                <template slot-scope="scope">
                    <img src=""/>
                </template>
            </el-table-column>
            <el-table-column prop="link_type" label="跳转类型"/>
            <el-table-column prop="status" label="状态">
                <template slot-scope="scope">
                    <span v-if="scope.row.status == 1" class="c-green">已开启</span>
                    <span v-else-if="scope.row.status == 2" class="c-danger">已关闭</span>
                </template>
            </el-table-column>
            <el-table-column label="操作">
                <template slot-scope="scope">
                    <ad-item-options
                            :scope="scope"
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
    </page>
</template>

<script>
    import api from '../../../assets/js/api'
    import adItemOptions from './ad-item-options'
    export default {
        name: "ad-list",
        data(){
            return {
                title: '广告管理',
                breadcrumbs: {},
                showDetail: false,
                isLoading: false,
                status:'test',
                positionId:0,
                query: {
                    page: 1,
                    pageSize: 15,
                    mobile: '',
                    id: '',
                    name: '',
                    startDate: '',
                    endDate: '',
                    status: '',
                    identityStatus: '',
                },
                list: [],
                total: 0,
            }
        },
        computed: {

        },
        methods: {
            search(){
                this.query.page = 1;
                this.getList();
            },
            getList(){
                /*api.get('/ads', this.query).then(data => {
                    this.list = data.data;
                    this.total = data.total;
                    console.log('data',data);
                })*/

            },
            itemChanged(index, data){
                this.list.splice(index, 1, data)
                this.getList();
            },
            changeStatus(data){

            },
        },
        created(){
            this.getList();
            this.positionId = this.$route.query.positionId;
            if(this.positionId){
                this.title = this.$route.query.positionName;
                this.breadcrumbs = {'广告管理': () => {
                        router.replace({path: '/refresh', query: {name: this.$route.name}})
                }}
            }
            console.log('positionId',this.positionId);
            // console.log(this.$route.query);
        },
        components: {
            adItemOptions
        }
    }
</script>

<style scoped>
.add-ad {
    float: right;
}
</style>