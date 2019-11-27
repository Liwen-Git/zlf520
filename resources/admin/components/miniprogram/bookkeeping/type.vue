<template>
    <el-card>

    </el-card>
</template>

<script>
    export default {
        name: "bookkeeping-type",
        data() {
            return {

            }
        },
        methods: {
            getList() {
                api.miniGet('bookkeeping/bill_type/list').then(data => {

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