import { defineConfig, loadEnv } from "vite"
import liveReload from "vite-plugin-live-reload"
import alias from "@rollup/plugin-alias"
import autoprefixer from "autoprefixer"
// import * as dotenv from "dotenv";
// require("dotenv").config()
const fs = require("fs")
import { globSync } from "glob"
import path from "node:path";
import { fileURLToPath } from "node:url";
const themeRoot = "wp-content/themes/metro"
// const env = dotenv.config({ processEnv: {} }).parsed;

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
	const env = loadEnv(mode, process.cwd(), "");

	return {
		define: { global: "globalThis" },
		plugins: [
			liveReload(__dirname + themeRoot + "/**/*.{php,js,css,json,scss}"),
			alias(),
		],
		publicDir: themeRoot + '/assets/css/critical-static',
		css: {
			postcss: {
				plugins: [autoprefixer({})],
			},
		},
		resolve: {
			alias: [
				{
					find: "~css",
					replacement: path.resolve(__dirname, themeRoot, "assets/css"),
				},
				{
					find: "~fonts",
					replacement: path.resolve(__dirname, themeRoot, "assets/fonts"),
				},
				{
					find: "~js",
					replacement: path.resolve(__dirname, themeRoot, "assets/js"),
				},
				{
					find: "~assets",
					replacement: path.resolve(__dirname, themeRoot, "assets"),
				},
			],
		},

		base: mode === "production" ? themeRoot + "/dist" : "/",
		// base: process.env.SITE_ENV === "production" ? themeRoot + "/dist" : "/",
		// mode: process.env.SITE_ENV === "production" ?? "development",

		build: {
			outDir: themeRoot + "/dist",
			emptyOutDir: true,
			manifest: "manifest.json",
			target: "es2018",
			rollupOptions: {
				input: Object.fromEntries(
					globSync(
						[
							themeRoot + "/blocks/**/*.{css,js,scss}",
							themeRoot + "/assets/js/single*.js",
							themeRoot + "/assets/css/single*.scss",
							themeRoot + "/~partytown/partytown.js",
							themeRoot + "/assets/js/scripts.js",
							themeRoot + "/assets/js/accessibility.js",
							themeRoot + "/assets/js/template-with-sidebar.js",
							themeRoot + "/assets/js/blog.js",
							themeRoot + "/assets/js/helpers.js",
							themeRoot + "/assets/js/custom-admin.js",
							themeRoot + "/assets/js/print.js",
							themeRoot + "/assets/js/recaptcha.js",
							themeRoot + "/assets/js/search-form.js",
							themeRoot + "/assets/js/email-templates.js",
							themeRoot + "/assets/css/style.scss",
							themeRoot + "/assets/css/email-templates.scss",
							themeRoot + "/assets/css/search-form.scss",
							themeRoot + "/assets/css/style-editor.scss",
							themeRoot + "/assets/css/_constants.scss",
							themeRoot + "/assets/css/_constants_editor.scss",
							themeRoot + "/assets/css/blog.scss",
							themeRoot + "/assets/css/print.scss",
							themeRoot + "/assets/css/404.scss",
							themeRoot + "/assets/css/template-with-sidebar.scss",
						].flat()
					).map((file) => [
						// key in manifest.json (without themeRoot and without extension)
						path.relative(
							themeRoot,
							file.slice(0, file.length - path.extname(file).length)
						),
						// absolute path
						fileURLToPath(new URL(file, import.meta.url)),
					])
				),
				output: {
					// For JS files that are entry points
					entryFileNames: (chunkInfo) => {
						const name = path.basename(chunkInfo.name);
						// Result: assets/scripts-a1b2c3d4.js
						return `assets/${name}-[hash].js`;
					},

					// For CSS, images, fonts, and other assets
					assetFileNames: (assetInfo) => {
						const fullName = path.basename(assetInfo.names[0]);
						const ext = path.extname(fullName);
						const name = path.basename(fullName, ext);
						// Result: assets/style-e5f6g7h8.css
						return `assets/${name}-[hash]${ext}`;
					},

					// For dynamically imported JS chunks
					chunkFileNames: `assets/[name]-[hash].js`,
				},
				// glob
				// 	.sync(themeRoot + "/blocks/**/*.{css,js,scss}")
				// 	.concat(glob.sync(themeRoot + "/assets/js/single*.js"))
				// 	.concat(glob.sync(themeRoot + "/assets/css/single*.scss"))
				// 	.concat([
				// 		// themeRoot + "/assets/js/zoho-chat.js",
				// 		themeRoot + "/assets/js/blog.js",
				// 		themeRoot + "/assets/js/helpers.js",
				// 		themeRoot + "/assets/js/custom-admin.js",
				// 		themeRoot + "/assets/js/print.js",
				// 		themeRoot + "/assets/js/recaptcha.js",
				// 		themeRoot + "/assets/js/search-form.js",
				// 		themeRoot + "/assets/js/email-templates.js",
				// 		themeRoot + "/assets/css/style.scss",
				// 		themeRoot + "/assets/css/email-templates.scss",
				// 		themeRoot + "/assets/css/search-form.scss",
				// 		themeRoot + "/assets/css/style-editor.scss",
				// 		themeRoot + "/assets/css/_constants.scss",
				// 		themeRoot + "/assets/css/_constants_editor.scss",
				// 		themeRoot + "/assets/css/blog.scss",
				// 		themeRoot + "/assets/css/print.scss",
				// 		themeRoot + "/assets/css/404.scss",
				// 		themeRoot + "/assets/css/template-with-sidebar.scss",
				// 	]),
			},

			// minifying switch
			minify: true,
			write: true,
		},

		server: {
			cors: true,
			fs: { strict: false },

			strictPort: true,
			port: env.DOCKER_VITE_PORT,

			https: {
				key: fs.readFileSync("_docker/nginx/ssl/nginx-selfsigned.key"),
				cert: fs.readFileSync("_docker/nginx/ssl/nginx-selfsigned.crt"),
			},

			origin: "https://localhost:" + env.DOCKER_VITE_PORT,
		},
	};
});
