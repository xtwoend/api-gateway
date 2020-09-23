<template>
<div>
	<app-header></app-header>
	<div class="w-full max-w-screen-xl mx-auto px-6">
		<div class="lg:flex -mx-6">
			<nav id="nav" class="docs-index hidden w-full lg:block lg:w-1/4 xl:w-1/5 pt-6 px-6 lg:border-r z-10 lg:max-h-(screen-22) top-22 lg:sticky">
				<ul>
					<li>
						<h2>Overview</h2> 
						<ul>
							<li>
								<router-link :to="{ name: 'home' }">Home</router-link>
							</li>
							<li>
								<router-link :to="{ name: 'about' }">About me</router-link>
							</li>
						</ul>
					</li>
				</ul>
			</nav>
			<router-view></router-view>
		</div>
	</div>
</div>
</template>

<script>
import Header from './Header'
export default {
	name: 'App',
	components: {
		'app-header': Header
	},
	data () {
	    return {

	    }
	}
}
</script>

<style lang="css" scoped>
</style>