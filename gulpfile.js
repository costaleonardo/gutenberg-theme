require('dotenv').config();

const env = process.env.NODE_ENV || 'development';
const { series, parallel, src, dest, watch } = require('gulp');
const { rollup } = require('rollup');
const { terser } = require('rollup-plugin-terser');
const sass = require('gulp-dart-sass');
const postcss = require('gulp-postcss');
const concat = require('gulp-concat');
const autoprefixer = require('autoprefixer');
const babel = require('rollup-plugin-babel');
const bs = require('browser-sync').create();
const { nodeResolve } = require('@rollup/plugin-node-resolve');
const commonjs = require('@rollup/plugin-commonjs');
const multi = require('@rollup/plugin-multi-entry');
const replace = require('@rollup/plugin-replace');

function css() {
	return src(['./src/app.scss', './blocks/**/*.scss'])
		.pipe(concat('app.min.scss'))
		.pipe(
			sass({
				outputStyle: 'compressed',
				includePaths: ['./src'],
			}).on('error', sass.logError)
		)
		.pipe(postcss([autoprefixer()]))
		.pipe(dest('./dist'))
		.pipe(bs.stream());
}

async function blocks_js() {
	const bundle = await rollup({
		external: ['jquery', 'vue', 'axios'],
		input: ['./blocks/**/*.js'],
		plugins: [
			multi(),
			nodeResolve({ browser: true }),
			commonjs({ include: 'node_modules/**' }),
			replace({
				'process.env.NODE_ENV': JSON.stringify(env),
				preventAssignment: true,
			}),
			babel({ exclude: 'node_modules/**' }),
			terser(),
		],
	});

	return bundle.write({
		globals: {
			jquery: '$',
			vue: 'Vue',
			axios: 'axios',
		},
		file: './dist/shblocks.min.js',
		name: 'SitehubBlocks',
		format: 'iife',
		sourcemap: true,
	});
}

async function modules_js() {
	const bundle = await rollup({
		external: ['jquery', 'vue', 'axios'],
		input: ['./src/js/**/*.js'],
		plugins: [
			multi(),
			nodeResolve({ browser: true }),
			commonjs({ include: 'node_modules/**' }),
			replace({
				'process.env.NODE_ENV': JSON.stringify(env),
				preventAssignment: true,
			}),
			babel({ exclude: 'node_modules/**' }),
			terser(),
		],
	});

	return bundle.write({
		globals: {
			jquery: '$',
			vue: 'Vue',
			axios: 'axios',
		},
		file: './dist/shmodules.min.js',
		name: 'SitehubModules',
		format: 'iife',
		sourcemap: true,
	});
}

async function custom_field_types_js() {
	const bundle = await rollup({
		external: ['jquery', 'vue', 'axios'],
		input: ['./custom-field-types/**/*.js'],
		plugins: [multi(), nodeResolve(), commonjs({ include: 'node_modules/**' }), babel({ exclude: 'node_modules/**' }), terser()],
	});

	return bundle.write({
		globals: {
			jquery: '$',
			vue: 'Vue',
			axios: 'axios',
		},
		file: './dist/custom-field-types.min.js',
		name: 'PixelsmithCustomFieldTypes',
		format: 'iife',
		sourcemap: true,
	});
}

async function js_entrypoint() {
	const bundle = await rollup({
		external: ['jquery', 'vue', 'axios'],
		input: ['./src/app.js'],
		plugins: [
			nodeResolve(),
			commonjs({ include: 'node_modules/**' }),
			replace({
				'process.env.NODE_ENV': JSON.stringify(env),
				preventAssignment: true,
			}),
			babel({ exclude: 'node_modules/**' }),
		],
	});

	return bundle.write({
		globals: {
			jquery: '$',
			vue: 'Vue',
			axios: 'axios',
		},
		file: './dist/app.js',
		format: 'iife',
		sourcemap: true,
	});
}

function serve(cb) {
	bs.init({
		proxy: process.env.GULP_PROXY,
	});

	watch(['src/**/*.scss', 'blocks/**/*.scss'], css );
	watch(['blocks/**/*.js', 'src/**/*.js'], parallel(blocks_js, modules_js, js_entrypoint));
	watch(['custom-field-types/**/*.js'], parallel(custom_field_types_js));
	watch(['*.html', 'dist/**/*.js', '**/*.php']).on('change', bs.reload);

	cb();
}

exports.default = series( parallel ( css, parallel(blocks_js, modules_js, js_entrypoint)), serve);
exports.javascript = series(parallel(blocks_js, js_entrypoint));
exports.css = series( css );
 