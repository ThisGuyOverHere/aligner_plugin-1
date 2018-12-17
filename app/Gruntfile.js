module.exports = function (grunt) {
    const es2015Preset = require('babel-preset-es2015');
    const reactPreset = require('babel-preset-react');
    const babelstage2 = require('babel-preset-stage-2');
    const sass = require('node-sass');

    /*var es2015Preset = require('babel-preset-es2015');*/
    /*var reactPreset = require('babel-preset-react');*/

    grunt.initConfig({
        env: {
            prod: {
                NODE_ENV: 'production'
            }
        },
        watch: {
            css: {
                files: [
                    'src/**/*.scss',
                    'assets/**/*.scss'
                ],
                tasks: ['sass', 'autoprefixer'],
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
                    'src/*.js',
                    'src/**/*.js'
                ],
                dest: '../static/build/js/main.min.js'
            },

            dist: {
                options: {
                    transform: [
                        ['babelify', {presets: [es2015Preset, reactPreset, babelstage2]}]
                    ],
                },
                src: [
                    'src/*.js',
                    'src/**/*.js'
                ],
                dest: '../static/build/js/main.js'
            },
        },
        uglify: {
            dist: {
                files: {
                    '../static/build/js/main.min.js': ['../static/build/js/main.js']
                }
            }
        },
        autoprefixer: {
            options: {
                browsers: ['last 2 versions']
            },
            dist: {
                files: {
                    '../static/build/css/style.css': '../static/build/css/style.css'
                }
            }
        },
        copy: {
            images: {
                files: [
                    // includes files within path and its sub-directories
                    {expand: true, cwd: 'assets', src: 'images/**', dest: '../static/build/'},
                ],
            },
        },
        sass: {
            options: {
                sourceMap: false,
                implementation: sass,
                includePaths: ['src', 'assets']
            },
            dist: {
                src: [
                    'src/Main.scss'
                ],
                dest: '../static/build/css/style.css'
            },
        }
    });

    // Define your tasks here
    grunt.registerTask('default', ['env', 'copy', 'browserify:dist', 'uglify:dist', 'sass', 'autoprefixer']);
    grunt.registerTask('dev', ['copy', 'browserify:dev', 'sass', 'autoprefixer', 'watch']);

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-env');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-autoprefixer');
    grunt.loadNpmTasks('grunt-sass');

};

