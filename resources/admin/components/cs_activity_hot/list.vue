<template>
    <page title="年货节活动管理" v-loading="isLoading">


        <el-table :data="list" stripe v-loading="dataLoading">
            <el-table-column prop="title" label="活动名称" width="180"/>
            <el-table-column prop="remark" label="活动备注" />
            <el-table-column prop="status" label="状态" width="180">
                <template slot-scope="scope">
                    <div v-if="parseInt(scope.row.status) === 1"  slot="reference" class="c-green"><p>已开启</p></div>
                    <div v-else-if="parseInt(scope.row.status) === 2" slot="reference" class="c-danger"><p>已关闭</p></div>
                    <span v-else>未知 ({{scope.row.status}})</span>
                </template>
            </el-table-column>
            <el-table-column  label="操作" >
                <template slot-scope="scope">
                    <div v-if="parseInt(scope.row.status) === 1"  slot="reference" >
                        <el-button type="text" @click="off(scope.row)">关闭</el-button>
                        <el-button type="text" @click="edit(scope)">设置</el-button>
                        <el-button type="text" @click="goMerchat">活动商户管理（超市）</el-button>
                        <el-button type="text" @click="goGoods">活动商品管理</el-button>
                        <el-button type="text" @click="goSta">活动统计</el-button>
                    </div>
                    <div v-else-if="parseInt(scope.row.status) === 2" slot="reference" >
                        <el-button type="text" @click="on(scope.row)">启用</el-button>
                        <el-button type="text" @click="edit(scope)">设置</el-button>
                    </div>

                </template>
            </el-table-column>

        </el-table>

        <el-dialog
                title="提示"
                :visible.sync="dialogVisible"
                width="30%">
            <span>{{dialogContent}}</span>
            <span slot="footer" class="dialog-footer">
                <el-button @click="dialogVisible = false">取 消</el-button>
                <el-button type="primary" @click="updateStatus">确 定</el-button>
            </span>
        </el-dialog>

    </page>
</template>

<script>
    import api from '../../../assets/js/api'


    export default {
        name: "csActivity-hot-list",
        data(){
            return {
                list: [],
                isLoading: false,
                dataLoading: true,
                dialogVisible: false,
                dialogContent:'',
                editItem:{},

            }
        },
        methods: {
            getList(){
                this.dataLoading = true;
                api.get('cs/activity_hot/activities').then(data => {
                    this.list = data.list;
                    this.dataLoading = false;
                })
            },
            edit(scope){
                router.push({
                    path: '/activity_hot/eidt_activity',
                    query: {id: scope.row.id},
                })
                return false;
            },
            goSta() {
                router.push('/activity_hot/statistics');
            },
            goMerchat(){
                router.push('/activity_hot/cs_merchants');
            },
            goGoods(){
                router.push('/activity_hot/cs_goods');
            },
            on(data){
                this.dialogVisible = true;
                this.dialogContent = '确定开启此活动吗？';
                this.editItem = data;
            },
            off(data){
                this.dialogVisible = true;
                this.dialogContent = '确定关闭此活动吗？';
                this.editItem = data;
            },
            updateStatus(){
                this.dialogVisible = false;
                let data = this.editItem;
                let status = 2;
                if(data.status == 2){
                    status = 1;
                }

                api.post('cs/activity_hot/updateStatus', {id: data.id, status: status}).then(data => {
                    this.getList();
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
