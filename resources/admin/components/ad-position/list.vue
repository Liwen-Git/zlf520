<template>
    <page title="广告位管理" v-loading="isLoading">
        <el-table :data="list" stripe>
            <el-table-column prop="name" label="广告位"/>
            <el-table-column prop="id" label="位置"/>
            <el-table-column prop="ads_count" label="广告数量"/>
            <el-table-column prop="desc" label="说明"/>
            <el-table-column prop="status" label="状态">
                <template slot-scope="scope">
                    <span v-if="scope.row.status == 1" class="c-green">已开启</span>
                    <span v-else-if="scope.row.status == 2" class="c-danger">已关闭</span>
                </template>
            </el-table-column>
            <el-table-column label="操作">
                <template slot-scope="scope">
                    <el-button v-if="scope.row.status == 1" type="text" @click="changeStatus(scope.row)">关闭</el-button>
                    <el-button v-if="scope.row.status == 2" type="text" @click="changeStatus(scope.row)">开启</el-button>
                    <router-link :to="{ name:'AdList',query: { positionId:  scope.row.id, positionName: scope.row.name }}">
                        <el-button type="text">管理</el-button>
                    </router-link>
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

    export default {
        name: "ad-list",
        data(){
            return {
                showDetail: false,
                isLoading: false,
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
                api.get('/ad/positions', this.query).then(data => {
                    this.list = data.list;
                    this.total = data.total;
                })
            },
            itemChanged(index, data){
                this.list.splice(index, 1, data.list)
                this.getList();
            },
            manage(){
                this.$router.push({
                    path: 'ads',
                    query:{
                        adPId:1
                    },
                });
            },
            changeStatus(row){
                let opt = row.status === 1 ? '关闭' : '开启';
                this.$confirm(`确认要${opt}吗?`).then( () => {
                    let status = row.status === 1 ? 2 : 1;
                    api.post('/ad/position/changeStatus', {id: row.id, status: status}).then(() => {
                        this.getList();
                    }).finally(() => {
                    })
                })
            }
        },
        created(){
            this.getList();
        },
        components: {
        }
    }
</script>

<style scoped>

</style>