<template>
	<article id="content" class="w-full lg:w-3/4 xl:w-4/5 pt-10 px-6 lg:px-12 min-h-(screen-16) markdown">
        <table class="table-fixed">
          <thead>
            <tr>
              <th class="w-1/2 px-4 py-2">Name</th>
              <th class="w-1/4 px-4 py-2">Author</th>
              <th class="w-1/4 px-4 py-2">Views</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="border px-4 py-2">Intro to CSS</td>
              <td class="border px-4 py-2">Adam</td>
              <td class="border px-4 py-2">858</td>
            </tr>
            <tr class="bg-gray-100">
              <td class="border px-4 py-2">A Long and Winding Tour of the History of UI Frameworks and Tools and the Impact on Design</td>
              <td class="border px-4 py-2">Adam</td>
              <td class="border px-4 py-2">112</td>
            </tr>
            <tr>
              <td class="border px-4 py-2">Intro to JavaScript</td>
              <td class="border px-4 py-2">Chris</td>
              <td class="border px-4 py-2">1,280</td>
            </tr>
          </tbody>
        </table>
    </article>
</template>

<script>
export default {

	name: 'Home',

	data () {
	    return {

	    }
	}
}
</script>

<style lang="css" scoped>
</style>