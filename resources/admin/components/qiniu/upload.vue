<template>
    <el-row>
        <el-col :span="24">
            <el-card>
                <el-row>
                    <el-col :span="5" style="margin-bottom: 15px;">
                        <el-switch
                                style="margin-bottom: 15px;"
                                v-model="switchVal"
                                active-text="选择器"
                                inactive-text="输入框">
                        </el-switch>
                        <el-select v-if="switchVal" v-model="directory1" size="small" clearable>
                            <el-option v-for="val in dirs" :key="val" :value="val" :label="val"></el-option>
                        </el-select>
                        <el-input v-if="!switchVal" size="small" v-model="directory2" style="width: 193px;"></el-input>
                    </el-col>
                </el-row>
                <image-upload :action="action" :multiple="true"></image-upload>
            </el-card>
        </el-col>
    </el-row>
</template>

<script>
    export default {
        name: "qiniu-upload",
        data() {
            return {
                dirs: ['blog', 'video', 'life', 'work', 'audio', 'other'],
                directory1: 'blog',
                directory2: '',
                switchVal: true,
            }
        },
        computed: {
            action() {
                let dir = this.switchVal ? this.directory1 : this.directory2;
                let api = '/api/qiniu/upload/image';

                return `${api}?directory=${dir}`;
            }
        }
    }
</script>

<style scoped>

</style>