import Vue from 'vue'
import Router from 'vue-router'

import NotFound from './pages/NotFound.vue'
import Home from './pages/Home.vue'

Vue.use(Router)

export default new Router({
  	mode: 'history',
  	base: '/admin/',
  	routes: [
	    {
	      	path: '/',
	      	name: 'home',
	      	component: Home
	    },
	    {
	      	path: '/about',
	      	name: 'about',
	      	component: require('./pages/About').default
	    },
	    {
	      	path: '*',
	      	name: 'not-found',
	      	component: NotFound
	    }
  	]
})