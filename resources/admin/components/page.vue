<template>
    <el-container>
        <el-header class="page-header">
            <el-breadcrumb separator-class="el-icon-arrow-right">
                <el-breadcrumb-item
                        v-for="(value, key) in breadcrumbs"
                        :key="key"
                        @click.native="typeof value === 'function' ? value() : toPath(value)"
                >
                    <a>{{key}}</a>
                </el-breadcrumb-item>
                <el-breadcrumb-item>{{title}}</el-breadcrumb-item>
            </el-breadcrumb>
        </el-header>
        <el-main>
            <!-- slot插槽，<slot></slot>将会被替换为<page></page>之间的代码 -->
            <slot></slot>
        </el-main>
    </el-container>
</template>

<script>
    export default {
        name: "page",
        props: {
            title: {
                type: String,
                required: true,
            },
            breadcrumbs: {
                type: Object,
                defaults: () => {},
            }
        },
        methods: {
            toPath(path) {
                this.$router.push(path);
            }
        }
    }
</script>

<style scoped>
    .page-header {
        height: 20px !important;
        padding-top: 15px;
    }
</style>