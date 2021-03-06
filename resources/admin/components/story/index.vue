<template>
    <el-row>
        <el-col :span="24">
            <el-card shadow="hover">
                <el-form ref="form" :model="search" size="small" inline>
                    <el-form-item label="日记主人">
                        <el-select v-model="search.wx_user_id" clearable placeholder="选择日记主人">
                            <el-option :value="1" label="李子语录"></el-option>
                            <el-option :value="2" label="FeVer物语"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="日期">
                        <el-date-picker
                                v-model="search.date"
                                type="date"
                                placeholder="选择日期"
                                format="yyyy 年 MM 月 dd 日"
                                value-format="yyyy-MM-dd">
                        </el-date-picker>
                    </el-form-item>

                    <el-button size="small" type="primary" @click="searchList">查询</el-button>
                    <el-button size="small" type="success" @click="addStory" style="float: right">添加</el-button>
                </el-form>
                <el-table :data="list" v-loading="tableLoading" stripe>
                    <el-table-column type="expand">
                        <template slot-scope="scope">
                            <span style="white-space: pre-line;">{{scope.row.content}}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="id" label="ID" width="60"></el-table-column>
                    <el-table-column prop="date" label="日期" width="120"></el-table-column>
                    <el-table-column prop="wx_user_id" label="日记主人" width="100">
                        <template slot-scope="scope">
                            <span v-if="scope.row.wx_user_id === 1">李子语录</span>
                            <span v-else-if="scope.row.wx_user_id === 2">FeVer物语</span>
                            <span v-else>其他</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="content" label="日记内容">
                        <template slot-scope="scope">
                            <span style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">{{scope.row.content}}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="images" label="图片">
                        <template slot-scope="scope">
                            <el-image
                                    v-if="scope.row.images"
                                    style="width: 100px; height: 100px"
                                    :src="scope.row.images.split(',')[0]"
                                    :preview-src-list="scope.row.images.split(',')">
                            </el-image>
                        </template>
                    </el-table-column>
                    <el-table-column prop="created_at" label="创建时间"></el-table-column>
                    <el-table-column label="操作" width="100">
                        <template slot-scope="scope">
                            <el-button type="primary" icon="el-icon-edit" circle size="mini" @click="editStory(scope.row)"></el-button>
                            <el-button type="danger" icon="el-icon-delete" circle size="mini" @click="deleteStory(scope.row)"></el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-pagination
                        layout="total, prev, pager, next"
                        :total="total"
                        :current-page.sync="search.page"
                        :page-size="search.pageSize"
                        @current-change="getList"></el-pagination>
            </el-card>

            <el-dialog :visible.sync="showDialog" :title="dialogTitle" :close-on-click-modal="false" width="40%">
                <el-form ref="dialogForm" :model="storyData" :rules="storyRules" size="small" label-width="100px">
                    <el-form-item label="日记主人" prop="wx_user_id">
                        <el-select v-model="storyData.wx_user_id" clearable placeholder="选择日记主人">
                            <el-option :value="1" label="李子语录"></el-option>
                            <el-option :value="2" label="FeVer物语"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="日期" prop="date">
                        <el-date-picker
                                v-model="storyData.date"
                                type="date"
                                placeholder="选择日期"
                                format="yyyy 年 MM 月 dd 日"
                                value-format="yyyy-MM-dd">
                        </el-date-picker>
                    </el-form-item>
                    <el-form-item label="内容" prop="content">
                        <el-input
                                type="textarea"
                                :rows="5"
                                placeholder="请输入内容"
                                class="w-295"
                                v-model="storyData.content">
                        </el-input>
                    </el-form-item>
                    <el-form-item label="图片" prop="images">
                        <image-upload v-model="storyData.images" :action="imageAction" :multiple="true"></image-upload>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="onSubmit" :loading="btnLoading">确 定</el-button>
                        <el-button @click="closeDialog">取 消</el-button>
                    </el-form-item>
                </el-form>
            </el-dialog>
        </el-col>
    </el-row>
</template>

<script>
    export default {
        name: "story-list",
        data() {
            return {
                tableLoading: false,
                btnLoading: false,
                list: [],
                search: {
                    wx_user_id: '',
                    date: '',
                    page: 1,
                    pageSize: 10,
                },
                total: 0,

                showDialog: false,
                dialogTitle: '',
                storyData: {
                    date: '',
                    wx_user_id: '',
                    content: '',
                    images: '',
                },
                storyRules: {
                    wx_user_id: [
                        {required: true, message: '请选择日记主人', trigger: 'blur'}
                    ],
                    date: [
                        {required: true, message: '日期不能为空', trigger: 'blur'}
                    ]
                },

                editId: 0,

                imageAction: '/api/local/upload?directory=diary&type=1'
            }
        },
        methods: {
            getList() {
                this.tableLoading = true;
                api.get('/story/list', this.search).then(res => {
                    this.list = res.list;
                    this.total = res.total;
                    this.tableLoading = false;
                }).catch(() => {
                    this.tableLoading = false;
                })
            },
            searchList() {
                this.search.page = 1;
                this.getList();
            },
            addStory() {
                this.dialogTitle = '新增日记';
                this.storyData = {
                    date: '',
                    wx_user_id: '',
                    content: '',
                    images: '',
                };
                this.editId = 0;
                this.showDialog = true;
            },
            onSubmit() {
                this.$refs.dialogForm.validate(valid => {
                    if(valid) {
                        this.btnLoading = true;
                        if (this.editId) {
                            this.storyData.id = this.editId;
                            api.post('/story/edit', this.storyData).then(() => {
                                this.$message.success('日记编辑成功!');
                                this.getList();
                                this.closeDialog();
                                this.btnLoading = false;
                            }).catch(() => {
                                this.btnLoading = false;
                            })
                        } else {
                            api.post('/story/add', this.storyData).then(() => {
                                this.$message.success('日记添加成功!');
                                this.getList();
                                this.closeDialog();
                                this.btnLoading = false;
                            }).catch(() => {
                                this.btnLoading = false;
                            })
                        }
                    }
                })
            },
            closeDialog() {
                this.showDialog = false;
                this.$refs.dialogForm.resetFields();
            },
            editStory(row) {
                this.dialogTitle = '编辑日记';
                this.editId = row.id;
                this.storyData = {
                    date: row.date,
                    wx_user_id: row.wx_user_id,
                    content: row.content,
                    images: row.images,
                };
                this.showDialog = true;
            },
            deleteStory(row) {
                this.$confirm('此操作将永久删除该文件, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    api.post('/story/delete', {id: row.id}).then(() => {
                        this.getList();
                        this.$message({
                            type: 'success',
                            message: '删除成功!'
                        });
                    }).catch(() => {
                        this.$message.error('删除失败!');
                    });
                }).catch(() => {
                    this.$message({
                        type: 'info',
                        message: '已取消删除!'
                    });
                });
            }
        },
        created() {
            this.getList();
        }
    }
</script>

<style scoped>

</style>