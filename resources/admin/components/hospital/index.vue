<template>
    <div>
        <el-row style="margin-bottom: 20px;">
            <el-tag type="warning" style="margin-bottom: 5px;">挂号时间</el-tag>
            <el-date-picker
                    size="small"
                    v-model="dateRangeVal"
                    type="daterange"
                    format="yyyy - MM - dd "
                    value-format="yyyy-MM-dd"
                    range-separator="至"
                    start-placeholder="开始日期"
                    end-placeholder="结束日期">
            </el-date-picker>
        </el-row>
        <el-row :gutter="20">
            <el-col :span="6">
                <el-table :data="departmentList" border :highlight-current-row="true" v-loading="departmentTableLoading">
                    <el-table-column prop="sectionName" label="科室列表">
                        <template slot-scope="scope">
                            <label style="cursor: pointer;" @click="getDoctorList(scope.row)">
                                {{scope.row.sectionName}}
                            </label>
                        </template>
                    </el-table-column>
                </el-table>
            </el-col>
            <el-col :span="6">
                <el-table :data="doctorList" border :highlight-current-row="true" @selection-change="handleSelectionChange" v-loading="doctorTableLoading">
                    <el-table-column type="selection" width="55"></el-table-column>
                    <el-table-column prop="doctorName" :label="doctorTableHeader">
                        <template slot-scope="scope">
                            <label style="cursor: pointer;" @click="getTreatmentTime(scope.row)">
                                {{scope.row.doctorName}} - {{scope.row.outPara1}}
                            </label>
                        </template>
                    </el-table-column>
                </el-table>
            </el-col>
            <el-col :span="6" v-loading="treatmentLoading">
                <el-tag type="success" v-if="JSON.stringify(currentDoctor) !== '{}'">
                    {{currentDoctor.doctorName}} - {{currentDoctor.outPara1}}
                </el-tag>
                <el-tag v-if="treatmentFee">挂号费：{{treatmentFee}}元</el-tag>
                <div v-if="treatmentFlag">
                    <div style="margin-top: 10px">
                        <el-tag type="warning" style="margin-bottom: 5px;">上午</el-tag>
                        <el-table :data="treatmentTimeMorningList" border :highlight-current-row="true" v-loading="">
                            <el-table-column label="就诊时间">
                                <template slot-scope="scope">
                                    <label>
                                        {{scope.row.startTime}} - {{scope.row.endTime}}
                                    </label>
                                </template>
                            </el-table-column>
                            <el-table-column label="挂号剩余数">
                                <template slot-scope="scope">
                                    <label>
                                        <el-tag size="medium">{{ scope.row.regLeaveCount }}</el-tag>
                                    </label>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                    <div style="margin-top: 10px;">
                        <el-tag type="warning" style="margin-bottom: 5px;">下午</el-tag>
                        <el-table :data="treatmentTimeAfternoonList" border :highlight-current-row="true" v-loading="">
                            <el-table-column label="就诊时间">
                                <template slot-scope="scope">
                                    <label>
                                        {{scope.row.startTime}} - {{scope.row.endTime}}
                                    </label>
                                </template>
                            </el-table-column>
                            <el-table-column label="挂号剩余数">
                                <template slot-scope="scope">
                                    <label>
                                        <el-tag size="medium">{{ scope.row.regLeaveCount }}</el-tag>
                                    </label>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </div>
                <div v-else>
                    <div style="margin: 20px">{{treatmentMsg}}</div>
                </div>
            </el-col>
            <el-col :span="6">
                <div>
                    <el-tag type="warning" style="margin-bottom: 15px;">挂号费用设置</el-tag>
                    <el-input-number :min="0" v-model="setFee" size="small"></el-input-number>
                </div>
                <div>
                    <el-button type="success" @click="startSearch">开始查询</el-button>
                    <el-button type="danger" @click="stopSearch">停止查询</el-button>
                </div>
            </el-col>
        </el-row>
    </div>
</template>

<script>
    export default {
        name: "futian-hospital",
        data() {
            return {
                dateRangeVal: [],

                departmentTableLoading: false,
                departmentList: [],
                currentDepartment: {},

                doctorTableLoading: false,
                doctorTableHeader: '医生列表',
                doctorList: [],

                treatmentLoading: false,
                currentDoctor: {},
                treatmentFlag: true,
                treatmentMsg: '',
                treatmentTimeMorningList: [],
                treatmentTimeAfternoonList: [],
                treatmentFee: 0,

                doctorIds: [],
                setFee: 0,
            }
        },
        methods: {
            getDepartmentList() {
                this.departmentTableLoading = true;
                api.get('/futian/hospital/department_list').then(res => {
                    this.departmentList = res;
                    this.departmentTableLoading = false;
                }).catch(() => {
                    this.departmentTableLoading = false;
                })
            },
            getDoctorList(dept) {
                this.doctorTableLoading = true;
                api.get('/futian/hospital/doctor', {dept_id: dept.sectionCode}).then(res => {
                    this.currentDepartment = dept;
                    this.doctorTableHeader = dept.sectionName + ' - 医生列表';

                    this.doctorList = res;
                    this.doctorTableLoading = false;
                }).catch(() => {
                    this.doctorTableLoading = false;
                })
            },
            getTreatmentTime(row) {
                let params = {
                    dept_id: row.deptId,
                    doctor_id: row.doctorId,
                    start_date: this.dateRangeVal[0],
                    end_date: this.dateRangeVal[1]
                };
                this.treatmentLoading = true;
                api.get('/futian/hospital/treatment_time', params).then(res => {
                    this.currentDoctor = row;
                    if (res.success === true) {
                        this.treatmentFlag = true;
                        if (res.obj[0]) {
                            let zero = res.obj[0];
                            if (zero.outPara2 === '01' || zero.outPara3 === '上午') {
                                this.treatmentTimeMorningList = zero.Mu012;
                                if (!res.obj[1]) {
                                    this.treatmentTimeAfternoonList = [];
                                }
                            } else {
                                this.treatmentTimeAfternoonList = zero.Mu012;
                                if (!res.obj[1]) {
                                    this.treatmentTimeMorningList = [];
                                }
                            }
                            this.treatmentFee = zero.treatFee;
                        }
                        if (res.obj[1]) {
                            let one = res.obj[1];
                            if (one.outPara2 === '02' || one.outPara3 === '下午') {
                                this.treatmentTimeAfternoonList = one.Mu012;
                            } else {
                                this.treatmentTimeMorningList = one.Mu012;
                            }
                        }
                    } else {
                        this.treatmentFlag = false;
                        this.treatmentMsg = res.msg;
                        this.treatmentFee = 0;
                    }
                    this.treatmentLoading = false;
                }).catch(() => {
                    this.treatmentLoading = false;
                })
            },
            handleSelectionChange(val) {
                let arr = [];
                val.forEach(function (item) {
                    arr.push(item.doctorId);
                });
                this.doctorIds = arr;
            },
            startSearch() {
                let params = {
                    dept_id: this.currentDepartment.sectionCode,
                    doctor_ids: this.doctorIds,
                    start_date: this.dateRangeVal[0],
                    end_date: this.dateRangeVal[1],
                    fee: this.setFee
                };
                this.$message.success('开始自动查询');
                api.post('/futian/hospital/reg', params).then(() => {
                    this.$message.success('自动查询结束');
                })
            },
            stopSearch() {
                api.post('/futian/hospital/stop/reg').then(() => {
                    this.$message.success('成功停止');
                })
            }
        },
        created() {
            let microTime = new Date().getTime() + 24*60*60*1000;
            let defaultDate = moment(microTime).format('YYYY-MM-DD');
            this.dateRangeVal = [defaultDate, defaultDate];

            this.getDepartmentList();
        }
    }
</script>

<style scoped>

</style>
