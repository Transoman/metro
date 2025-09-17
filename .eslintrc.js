module.exports = {
	env: {
		browser: true,
		es2021: true,
		node: true,
	},
	extends: "eslint:recommended",
	overrides: [],
	parserOptions: {
		ecmaVersion: "latest",
		sourceType: "module",
	},
	ignorePatterns: ["node_modules/", "vendor/", "wp-content/plugins"],
	rules: {
		indent: ["error", "tab"],
		"linebreak-style": ["error", "unix"],
		quotes: ["error", "double"],
		semi: ["error", "never"],
		"space-before-function-paren": ["error", "always"],
		"no-multi-spaces": ["error", {}],
		"space-in-parens": ["error", "never"],
		"comma-spacing": ["error", { before: false, after: true }],
		curly: ["error", "all"],
		"object-curly-newline": ["error", { multiline: true }],
		"object-curly-spacing": ["error", "always"],
	},
}
