module.exports = {
  purge: [],
  theme: {
    extend: {
    	minHeight: theme => ({
    		"(screen-16)": "calc(100vh - 4rem)"
    	}),
		maxHeight: theme => ({
			"(screen-22)": "calc(100vh - 5.5rem)"
		}),
		inset: theme => ({
			"22": "5.5rem"
		})
    },
  },
  variants: {},
  plugins: [],
}
