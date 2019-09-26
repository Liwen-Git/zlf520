<template>
    <el-row>
        <el-col :span="24">
            <el-card>
                <el-form ref="form" :model="formData" size="small" inline>
                    <el-form-item label="图片目录">
                        <el-input  v-model="formData.directory"></el-input>
                    </el-form-item>
                    <el-form-item label="开始时间">
                        <el-date-picker
                                v-model="formData.start_time"
                                type="date"
                                placeholder="选择开始时间"
                                format="yyyy 年 MM 月 dd 日"
                                value-format="yyyy-MM-dd">
                        </el-date-picker>
                    </el-form-item>
                    <el-form-item label="结束时间">
                        <el-date-picker
                                v-model="formData.end_time"
                                type="date"
                                placeholder="选择结束时间"
                                format="yyyy 年 MM 月 dd 日"
                                value-format="yyyy-MM-dd">
                        </el-date-picker>
                    </el-form-item>

                    <el-button size="small" type="primary">查询</el-button>
                </el-form>
                <el-table :data="list" stripe>
                    <el-table-column prop="id" label="ID" width="60"></el-table-column>
                    <el-table-column prop="directory" label="目录" width="100"></el-table-column>
                    <el-table-column prop="url" label="Url"></el-table-column>
                    <el-table-column prop="created_at" label="上传时间"></el-table-column>
                    <el-table-column label="预览">
                        <template slot-scope="scope">
                            <el-image
                                    style="width: 100px; height: 100px"
                                    :src="scope.row.url"
                                    :preview-src-list="[scope.row.url]">
                            </el-image>
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>
        </el-col>
    </el-row>
</template>

<script>
    export default {
        name: "qiniu-preview",
        data() {
            return {
                tableLoading: false,
                list: [],
                formData: {
                    directory: '',
                    start_time: '',
                    end_time: '',
                },
                page: 1,
                pageSize: 15,
                total: 0,
            }
        },
        methods: {
            getList() {
                this.tableLoading = true;
                this.formData.page = this.page;
                this.formData.page_size = this.pageSize;
                api.get('/qiniu/image/list', this.formData).then(data => {
                    this.list = data.list;
                    this.total = data.total;
                    this.tableLoading = false;
                })
            }
        },
        created() {
            this.getList();
        }
    }
</script>

<style scoped>

</style>