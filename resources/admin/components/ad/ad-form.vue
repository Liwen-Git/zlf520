<template>
    <el-row>
        <el-col :span="22">
            <el-form :model="form" label-width="120px" :rules="formRules" ref="form" @submit.native.prevent>
                <el-form-item prop="name" label="广告名称">
                    <el-input v-model="form.name"/>
                </el-form-item>
                <el-form-item prop="desc" label="广告说明">
                    <el-input v-model="form.desc"/>
                </el-form-item>
                <el-form-item prop="image" label="广告图片">
                    <image-upload :width="190" :height="190" v-model="form.image" :limit="1"/>
                    <div>图片尺寸: 750 px * 370 px，大小不超过2MB</div>
                </el-form-item>
                <el-form-item prop="link_type" label="跳转类型">
                    <el-radio-group v-model="form.link_type">
                        <el-radio :label="1">无跳转</el-radio>
                        <el-radio :label="2">原生跳转</el-radio>
                        <el-radio :label="3">H5跳转</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item prop="status" label="状态">
                    <el-radio-group v-model="form.status">
                        <el-radio :label="1">正常</el-radio>
                        <el-radio :label="2">禁用</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item>
                    <el-button @click="cancel">取消</el-button>
                    <el-button type="primary" @click="save">保存</el-button>
                </el-form-item>
            </el-form>
        </el-col>
    </el-row>

</template>
<script>
    let defaultForm = {
        name: '',
        market_price: 0,
        price: 0,
        start_date: '',
        end_date: '',
        pic_list: [],
        thumb_url: '',
        desc: '',
        buy_info: '',
        status: 1,
        link_type:'',
    };
    export default {
        name: 'goods-form',
        props: {
            data: Object,
        },
        computed:{

        },
        data(){
            var validatePrice = (rule, value, callback) => {
                if (value <= 0 || value>=1000000){
                    callback(new Error('销售价必须在0到1000000元之间'));
                }else {
                    callback();
                }
            };
            var validateMarketPrice = (rule, value, callback) => {
                if (value <= 0 || value>=1000000) {
                    callback(new Error('市场价必须在0到1000000元之间'));
                }else {
                    callback();
                }
            };
            return {
                form: deepCopy(defaultForm),
                formRules: {
                    name: [
                        {required: true, message: '商品名称不能为空'},
                        {max: 30, message: '商品名称不能超过30个字'}
                    ],
                    market_price: [
                        {required: true, message: '市场价不能为空'},
                        {validator: validateMarketPrice, trigger: 'blur'}
                    ],
                    price: [
                        {required: true, message: '销售价不能为空'},
                        {validator: validatePrice, trigger: 'blur'}
                    ],
                    thumb_url: [
                        {required: true, message: '缩略图不能为空'}
                    ],
                    pic_list: [
                        {required: true, message: '详情图不能为空'}
                    ],
                    stock: [
                        {required: true, message: '库存不能为空'},
                        {type: 'number',min:0, max: 100000, message: '库存不能超过100000'}
                    ],
                },
            }
        },
        methods: {
            initForm(){
                if(this.data){
                    this.form = deepCopy(this.data);
                }else {
                    this.form = deepCopy(defaultForm)
                }
            },
            resetForm(){
                this.form.start_date = '';
                this.form.end_date = '';
                this.$refs.form.resetFields();
                console.log(this.form)
            },
            cancel(){
                console.log(this.form)
                this.$emit('cancel');
            },
            save(){
                this.$refs.form.validate(valid => {
                    if(valid){
                        let data = deepCopy(this.form);
                        if(this.data && this.data.id){
                            data.id = this.data.id;
                        }
                        if(!data.start_date || !data.end_date){
                            this.$message.error('时间不能为空');
                            return;
                        }
                        if(data.start_date > data.end_date){
                            this.$message.error('有效期开始时间不能大于结束时间');
                            return;
                        }
                        this.$emit('save', data);
                    }
                })

            }
        },
        created(){
            this.initForm();
        },
        watch: {
            data(){
                this.initForm();
            }
        },
        components: {
        }
    }
</script>
<style scoped>

</style>
