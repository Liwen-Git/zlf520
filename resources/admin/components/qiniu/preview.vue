<template>
    <el-row>
        <el-col :span="24">
            <el-card shadow="hover">
                <el-form ref="form" :model="formData" size="small" inline>
                    <el-form-item label="图片目录">
                        <el-input  v-model="formData.directory" clearable></el-input>
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
                                :picker-options="{disabledDate: (time) => {return time.getTime() < new Date(formData.start_time) - 8.64e7}}"
                                placeholder="选择结束时间"
                                format="yyyy 年 MM 月 dd 日"
                                value-format="yyyy-MM-dd">
                        </el-date-picker>
                    </el-form-item>

                    <el-button size="small" type="primary" @click="search">查询</el-button>
                </el-form>
                <el-table :data="list" v-loading="tableLoading" stripe>
                    <el-table-column prop="id" label="ID" width="60"></el-table-column>
                    <el-table-column prop="directory" label="目录" width="100"></el-table-column>
                    <el-table-column prop="url" label="Url">
                        <template slot-scope="scope">
                            {{scope.row.url}}
                            <el-button size="mini" circle class="clipboard_btn" :data-clipboard-text="scope.row.url" @click="copy" icon="el-icon-document-copy"></el-button>
                        </template>
                    </el-table-column>
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
                    <el-table-column label="操作" width="100">
                        <template slot-scope="scope">
                            <el-button type="danger" size="mini" @click="deleteImage(scope.row)">删除</el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-pagination
                        layout="total, prev, pager, next"
                        :total="total"
                        :current-page.sync="page"
                        :page-size="pageSize"
                        @current-change="getList"></el-pagination>
            </el-card>
        </el-col>
    </el-row>
</template>

<script>
    import Clipboard from 'clipboard';

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
                pageSize: 10,
                total: 0,

                httpHeader: document.location.protocol,
            }
        },
        methods: {
            getList() {
                this.tableLoading = true;
                this.formData.type = 1;
                this.formData.page = this.page;
                this.formData.page_size = this.pageSize;
                api.get('/qiniu/image/list', this.formData).then(data => {
                    this.list = data.list;
                    this.total = data.total;
                    this.tableLoading = false;
                })
            },
            search() {
                this.page = 1;
                this.getList();
            },
            deleteImage(row) {
                this.$confirm('此操作将永久删除该文件, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    api.post('/local/upload/delete', {id: row.id}).then(data => {
                        if (data && data.status) {
                            this.$message.success('删除成功');
                            this.getList();
                        } else {
                            this.$message.error('删除失败');
                        }
                    })
                });
            },
            copy() {
                let clipboard = new Clipboard('.clipboard_btn')
                clipboard.on('success', () => {
                    this.$message.success('复制成功');
                    // 释放内存
                    clipboard.destroy()
                });
            }
        },
        created() {
            this.getList();
            new Clipboard('clipboard_btn');
        }
    }
</script>

<style scoped>

</style>