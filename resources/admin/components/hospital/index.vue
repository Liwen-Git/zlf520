<template>
    <div>
        <el-row style="margin-bottom: 20px;">
            挂号时间：
            <el-date-picker
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
                <el-table :data="doctorList" border :highlight-current-row="true" v-loading="doctorTableLoading">
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
