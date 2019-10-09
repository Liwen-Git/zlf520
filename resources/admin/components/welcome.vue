<template>
	<iframe :src="url" class="welcome-iframe"></iframe>
</template>

<script>
	export default {
		name: 'welcome',
		data() {
			return {
				url: '',
			}
		},
		mounted() {
			this.url = document.location.protocol + '//' + window.location.host + '/love/the_red_heart';
		}
	}
</script>

<style scoped>
	.welcome-iframe {
		border: none;
		width: 100%;
		height: 700px;
	}
</style>
