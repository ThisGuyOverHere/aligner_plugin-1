module.exports = function (grunt) {
	const es2015Preset = require('babel-preset-es2015');
	const reactPreset = require('babel-preset-react');
	const babelstage2 = require('babel-preset-stage-2');
	const fs = require('fs');
	const ini = require('ini');
	const sass = require('node-sass');

	/*var es2015Preset = require('babel-preset-es2015');*/
	/*var reactPreset = require('babel-preset-react');*/


	//get version of release
	const config = ini.parse(fs.readFileSync('config.ini', 'utf-8'))
	console.log(config);


	grunt.initConfig({
		env: {
			prod: {
				NODE_ENV: 'production'
			}
		},
		watch: {
			css: {
				files: [
					'app/src/**/*.scss',
					'app/assets/**/*.scss'
				],
				tasks: ['sass', 'autoprefixer'],
			}
		},
		'string-replace': {
			dist: {
				files: {
					'./app/src/Constants/Env.constants.js': './app/src/Constants/Env.constants.template.js'
				},
				options: {
					replacements: [{
						pattern: 'GA_FROM_INI###',
						replacement: config.GA_UA
					}]
				}
			}
		},
		browserify: {

			dev: {
				options: {
					transform: [
						['babelify', {presets: [es2015Preset, reactPreset, babelstage2]}]
					],
					browserifyOptions: {
						debug: true // source mapping
					},
					watch: true, // use watchify for incremental builds!
					//keepAlive : true, // watchify will exit unless task is kept alive
				},
				src: [
					'app/src/*.js',
					'app/src/**/*.js'
				],
				dest: `./static/build/js/main.${config.RELEASE_VERSION}.min.js`
			},

			dist: {
				options: {
					transform: [
						['babelify', {presets: [es2015Preset, reactPreset, babelstage2]}]
					],
				},
				src: [
					'app/src/*.js',
					'app/src/**/*.js'
				],
				dest: `./static/build/js/main.${config.RELEASE_VERSION}.min.js`
			},
		},
		uglify: {
			dist: {
				files: {
					[`./static/build/js/main.${config.RELEASE_VERSION}.min.js`]: [`./static/build/js/main.${config.RELEASE_VERSION}.js`]
				}
			}
		},
		autoprefixer: {
			options: {
				browsers: ['last 2 versions']
			},
			dist: {
				files: {
					[`./static/build/css/style.${config.RELEASE_VERSION}.css`]: [`./static/build/css/style.${config.RELEASE_VERSION}.css`]
				}
			}
		},
		copy: {
			images: {
				files: [
					// includes files within path and its sub-directories
					{expand: true, cwd: 'app/assets', src: 'images/**', dest: './static/build/'},
				],
			},
		},
		sass: {
			options: {
				sourceMap: false,
				implementation: sass,
				includePaths: ['app']
			},
			dist: {
				src: [
					'app/src/Main.scss'
				],
				dest: `./static/build/css/style.${config.RELEASE_VERSION}.css`
			},
		},
		clean: {
			options: {
				force: true
			},
			build: ['./static/build/']
		}
	});

	// Define your tasks here
	grunt.registerTask('default', ['clean:build', 'env', 'string-replace', 'copy', 'browserify:dist', 'sass', 'autoprefixer']);
	grunt.registerTask('dev', ['clean:build', 'copy', 'string-replace', 'browserify:dev', 'sass', 'autoprefixer', 'watch']);

	grunt.loadNpmTasks('grunt-browserify');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-env');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-string-replace');
};

