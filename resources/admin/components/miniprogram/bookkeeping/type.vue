<template>
    <el-card>
        <el-tree :data="tree" node-key="id" default-expand-all :expand-on-click-node="false">
            <span class="custom-tree-node" slot-scope="{ node, data }">
                <span>{{ data.name }}</span>
                <span>
                    <el-button v-if="!data.pid" type="text" size="mini">新增</el-button>
                    <el-button v-if="data.id !== '0'" type="text" size="mini">编辑</el-button>
                    <el-button v-if="data.id !== '0'" type="text" size="mini" @click="deleteType(data)">删除</el-button>
                </span>
            </span>
        </el-tree>
    </el-card>
</template>

<script>
    export default {
        name: "bookkeeping-type",
        data() {
            return {
                tree: [
                    {
                        id: '0',
                        pid: null,
                        name: '全部',
                        children: null,
                    }
                ],
            }
        },
        methods: {
            getList() {
                api.miniGet('bookkeeping/bill_type/list').then(data => {
                    this.tree[0].children = JSON.parse(JSON.stringify(this.listToTree(data)));
                })
            },
            listToTree(list) {
                let copyList = list.slice(0);
                let tree = [];
                for (let i = 0;i < copyList.length;i++) {
                    // 找出每一项的父节点，并将其作为父节点的children
                    for (let j = 0;j < copyList.length;j++) {
                        if (copyList[i].pid === copyList[j].id) {
                            if (copyList[j].children === undefined) {
                                copyList[j].children = []
                            }
                            copyList[j].children.push(copyList[i])
                        }
                    }
                    // 把根节点提取出来，pid为0的就是根节点
                    if (copyList[i].pid === 0) {
                        tree.push(copyList[i])
                    }
                }
                return tree
            },
            deleteType(data) {
                this.$confirm('此操作将永久删除该分类, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning',
                }).then(() => {
                    if (data.children !== undefined && data.children.length > 0) {
                        this.$message.error('该分类存在子类');
                        return false;
                    }
                    api.miniPost('bookkeeping/bill_type/delete', {id: data.id}).then(() => {
                        this.$message.success('删除成功');
                    })
                })
            }
        },
        created() {
            this.getList();
        }
    }
</script>

<style scoped>
    .custom-tree-node {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 14px;
        padding-right: 8px;
    }
</style>