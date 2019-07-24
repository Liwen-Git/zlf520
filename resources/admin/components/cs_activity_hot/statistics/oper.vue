<template>
    <el-col>
        <el-form v-model="form" inline size="small">
            <el-form-item prop="dateRange" label="时间">
                <el-date-picker
                        v-model="form.dateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="yyyy-MM-dd"
                        size="small"
                >
                </el-date-picker>
            </el-form-item>
            <el-form-item prop="operNameOrId" label="运营中心">
                <el-input v-model="form.operNameOrId" clearable placeholder="请输入运营中心名称或ID"/>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="search">搜 索</el-button>
                <el-button type="success" @click="exportExcel">导 出</el-button>
            </el-form-item>
        </el-form>
        <el-col>
            <div style="width: 200px; display: inline-block;">活动总销售量：{{sale_num_sum}}</div>
            <span>活动总销售额（元）：{{sale_total_sum}}</span>
        </el-col>
        <el-table :data="list" v-loading="tableLoading" stripe @sort-change="sortChange">
            <el-table-column prop="date" label="时间"></el-table-column>
            <el-table-column label="运营中心">
                <template slot-scope="scope">
                    <span>[{{scope.row.oper.id}}]{{scope.row.oper.name}}</span>
                </template>
            </el-table-column>
            <el-table-column prop="goods_num" label="活动商品数"></el-table-column>
            <el-table-column prop="sale_num" label="活动销量" sortable="custom"></el-table-column>
            <el-table-column prop="sale_total" label="活动销售额" sortable="custom"></el-table-column>
        </el-table>
        <el-pagination
                class="fr m-t-20"
                layout="total, prev, pager, next"
                :current-page.sync="form.page"
                @current-change="getList"
                :page-size="form.pageSize"
                :total="total"
        ></el-pagination>
    </el-col>
</template>

<script>
    import api from '../../../../assets/js/api'

    export default {
        props: {

        },
        data() {
            return {
                form: {
                    dateRange: [],
                    operNameOrId: '',

                    page: 1,
                    pageSize: 15,
                },
                total: 0,
                sale_num_sum: 0,
                sale_total_sum: 0,

                list: [],
                tableLoading: false,
            }
        },
        methods: {
            sortChange (column) {
                this.form.orderColumn = column.prop;
                this.form.orderType = column.order;
                this.getList();
            },
            getList() {
                if (this.form.dateRange) {
                    this.form.startDate = this.form.dateRange[0] || '';
                    this.form.endDate = this.form.dateRange[1] || '';
                }

                this.tableLoading = true;
                api.get('cs/act_hot/getStaOperList', this.form).then(data => {
                    this.list = data.list;
                    this.total = data.total;
                    this.sale_num_sum = data.sale_num_sum;
                    this.sale_total_sum = data.sale_total_sum;
                    this.tableLoading = false;
                })
            },
            search() {
                this.form.page = 1;
                this.getList();
            },
            exportExcel() {
                if (this.form.dateRange) {
                    this.form.startDate = this.form.dateRange[0] || '';
                    this.form.endDate = this.form.dateRange[1] || '';
                }
                let data = this.form;
                let params = [];
                Object.keys(data).forEach((key) => {
                    let value =  data[key];
                    if (typeof value === 'undefined' || value == null) {
                        value = '';
                    }
                    params.push([key, encodeURIComponent(value)].join('='))
                }) ;
                let uri = params.join('&');

                location.href = `/api/admin/cs/act_hot/exportStaOper?${uri}`;
            },
        },
        created() {
            this.getList();
        }
    }
</script>

<style scoped>
    .tips {
        overflow: hidden;
        text-overflow:ellipsis;
        white-space: nowrap;
    }
</style>