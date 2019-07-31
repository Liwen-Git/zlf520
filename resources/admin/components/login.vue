<style lang="less" scoped>
    .login-container {
        width: 100%;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        background-repeat: no-repeat;
        background-size: cover;
        overflow: hidden;
    }
    .login-panel {
        position: absolute;
        top: 50%;
        left: 75%;
        margin: -230px 0 0 -100px;
        width: 360px;
        height: 400px;
        .login-logo {
            text-align: center;
            height: 40px;
            line-height: 40px;
            cursor: pointer;
            margin-bottom: 24px;
            img {
                width: 40px;
                margin-right: 8px;
            }
            span {
                vertical-align: text-bottom;
                font-size: 25px;
                text-transform: uppercase;
                display: inline-block;
                color: #fff;
            }
        }

    }
    .login-form {
        width: 310px;
        /*height: 280px;*/
        padding: 25px 25px 25px;
        box-shadow: 0 0 100px rgba(0,0,0,.08);
        /*background-color: #fff;*/
        border-radius: 4px;
        z-index: 3;

        .verify-img {
            right: 0;
            height: 38px;
            margin: 1px;
            position: absolute;
        }
    }
    .form-fade-enter-active, .form-fade-leave-active {
        transition: all 1s;
    }
    .form-fade-enter, .form-fade-leave-active {
        transform: translate3d(0, -50px, 0);
        opacity: 0;
    }
</style>
<template>
    <div class="login-container">
        <transition name="form-fade" mode="in-out">
            <div class="login-panel">
                <div class="login-logo">
                    <span>for zlf's exclusive use</span>
                </div>
                <div class="login-form" v-show="showLogin" v-loading="autoLoginLoading" element-loading-text="自动登录中...">
                    <el-form :model="form" :rules="formRules" ref="form"
                             @keyup.native.enter="doLogin"
                             label-position="left"
                             label-width="0px">
                        <el-form-item prop="username">
                            <el-input type="text" v-model="form.username" auto-complete="off" placeholder="帐号"/>
                        </el-form-item>
                        <el-form-item prop="password">
                            <el-input type="password" v-model="form.password" auto-complete="off" placeholder="密码"/>
                        </el-form-item>
                        <el-form-item prop="verifyCode">
                            <el-input type="text" v-model="form.verifyCode" auto-complete="off" class="w-150"
                                      placeholder="验证码"/>
                            <img class="verify-img" :src="captchaSrc" @click="refreshVerify()" width="150"/>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" style="width:100%;" v-loading="loading" :disabled="loading"
                                       @click.native.prevent="doLogin">登录
                            </el-button>
                        </el-form-item>
                    </el-form>
                </div>
            </div>
        </transition>
        <div>
            <div id="liwen">
                <svg height="320" width="320" class="like" id="dianji" onclick="document.getElementById('liwen').classList.toggle('liked')">
                    <path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90" fill="white"> <!-- 80 by 70 --></path>
                </svg>

                <!-- DECORATIONS (quite a lot of them) -->
                <div class="dot dot-1"></div>
                <div class="dot dot-2"></div>
                <div class="dot dot-3"></div>
                <div class="dot dot-4"></div>
                <div class="dot dot-5"></div>
                <div class="dot dot-6"></div>
                <div class="dot dot-7"></div>
                <div class="dot dot-8"></div>

                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-1"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-2"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-3"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-4"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-5"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-6"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-7"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="40" width="40" viewBox="0 0 320 320" class="h h-8"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>

                <svg height="110" width="110" viewBox="0 0 320 320" class="fly fly-1"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
                <svg height="110" width="110" viewBox="0 0 320 320" class="fly fly-2"><path class="path" d="M 160 145 c 15 -90 170 -20 0 90 m 0 -90 c -15 -90 -170 -20 0 90"></path></svg>
            </div>
        </div>
    </div>
</template>

<script>
    import api from '../../assets/js/api'

    export default {
        data(){
            return {
                form: {
                    username: '',
                    password: '',
                    verifyCode: ''
                },
                formRules: {
                    username: [
                        {required: true, message: '请输入帐号', trigger: 'blur'}
                    ],
                    password: [
                        {required: true, message: '请输入密码', trigger: 'blur'}
                    ],
                    verifyCode: [
                        {required: true, message: '请输入验证码', trigger: 'blur'},
                        { min: 4, max: 6, message: '请输入4-6位验证码', trigger: 'blur' }
                    ]
                },
                captchaUrl: captcha_url,
                captchaSrc: captcha_url + '?v=' + Math.random(),
                loading: false,
                autoLoginLoading: false,
                showLogin: false,
            }
        },
        methods: {
            refreshVerify(){
                this.captchaSrc = ''
                setTimeout(() => {
                    this.captchaSrc = this.captchaUrl + '?v=' + moment().unix()
                }, 300)
            },
            relocation() {
                if (this.$route.query && this.$route.query._from) {
                    this.$router.push(this.$route.query._from);
                }else {
                    this.$router.push('welcome');
                }
            },
            doLogin(){
                let _self = this;
                this.$refs.form.validate(valid => {
                    if(valid){
                        _self.loading = true;
                        api.post('/login', this.form).then(data => {
                            _self.relocation();
                        }).catch(() => {
                            _self.refreshVerify();
                        }).finally(() => {
                            _self.loading = false;
                        })
                    }
                })

            },
        },
        created: function () {

        },
        mounted () {
            const that = this;
            that.showLogin = true;
        },
        beforeDestroy () {

        }
    }
</script>

<style scoped>
    @import "../../assets/css/login_style.css";
</style>
