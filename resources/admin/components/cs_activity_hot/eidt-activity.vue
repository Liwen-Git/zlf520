<template>
    <page title="活动设置" :breadcrumbs="{'年货节活动': 'list'}">

        <el-form :model="form" size="small" v-if="isShow" label-width="165px" :rules="formRules" ref="form" @submit.native.prevent>

            <el-col>
                <el-form-item prop="title" label="活动标题">
                    <el-input type="input" :rows="5" v-model="form.title"/>
                </el-form-item>
                <el-form-item prop="icon" label="活动图标">
                    <image-upload v-model="form.icon" :limit="1"/>
                </el-form-item>
                <el-form-item prop="triangle_icon" label="活动三角形图标">
                    <image-upload v-model="form.triangle_icon" :limit="1"/>
                </el-form-item>
                <el-form-item prop="bottom_right_icon" label="右下角图标">
                    <image-upload v-model="form.bottom_right_icon" :limit="1"/>
                </el-form-item>
                <el-form-item prop="tag" label="活动标签">
                    <el-input type="input" :rows="5" v-model="form.tag"/>
                </el-form-item>
                <el-form-item prop="desc" label="活动介绍">
                    <el-input type="textarea" :rows="5" v-model="form.desc"/>
                </el-form-item>
                <el-form-item prop="start_ad" label="启动广告">
                    <image-upload v-model="form.start_ad" :limit="1"/>
                </el-form-item>
                <el-form-item prop="logo" label="活动logo">
                    <image-upload v-model="form.logo" :limit="1"/>
                </el-form-item>
                <el-form-item prop="pic_list" label="活动图片">
                    <image-upload v-model="form.pic_list" :width="750" :height="1334" :limit="3"/>
                    <div>尺寸750 px * 1334px，最多3张</div>
                </el-form-item>
                <el-form-item prop="banner" label="超市banner">
                    <image-upload v-model="form.banner" :limit="3"/>
                </el-form-item>
                <el-form-item prop="remark" label="活动备注">
                    <el-input type="textarea" :rows="5" v-model="form.remark"/>
                </el-form-item>
            </el-col>
            <el-col>
                <el-form-item>
                    <el-button @click="cancel">取消</el-button>
                    <el-button type="success" @click="save">保存</el-button>
                </el-form-item>
            </el-col>

        </el-form>
    </page>
</template>

<script>
    import api from '../../../assets/js/api'


    export default {
        name: "csActivity-hot-eidt-activity",
        data(){
            return {
                isShow: false,
                form: {},
                formRules: {
                    title: [
                        {required: true, message: '活动标题不能为空'},
                        {max: 60, message: '活动介绍不能超过60个字'}
                    ],
                    desc: [
                        {required: true, message: '活动介绍不能为空'},
                        {max: 1000, message: '活动介绍不能超过1000个字'}
                    ],
                    tag:[
                        {max: 6, message: '活动标签不能超过6个字'}
                    ],
                    remark:[
                        {max: 200, message: '备注不能超过200个字'}
                    ]

                },
            }
        },
        methods: {
            cancel(){
                router.push('list');
            },
            save(){
                this.$refs['form'].validate((valid) => {
                    if (valid) {
                        api.post('cs/activity_hot/saveActivity', this.form).then(data => {
                            this.$message({
                                message: '保存成功',
                                type: 'success',
                                onClose:function(){
                                    router.push('list');
                                }
                            });

                        })

                    } else {
                        return false;
                    }
                });
            },
            getData(){

                this.id = this.$route.query.id;
                api.get('cs/activity_hot/getActivity', {id: this.id}).then(data => {
                    this.form = data;
                }).finally(() => {
                    this.isShow = true;
                })
            }

        },
        created(){
            this.getData();

        },
        components: {
        },
        watch: {
            data(){
                this.getData();
            }
        }
    }
</script>

<style scoped>

</style>
