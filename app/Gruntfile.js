module.exports = function(grunt) {

    var es2015Preset = require('babel-preset-es2015');
    var reactPreset = require('babel-preset-react');

    grunt.initConfig( {
        watch: {
            components: {
                files: [
                    'src/*.js',
                    'src/**/*.js'
                ],
                tasks: ['browserify:components'],
                options: {
                    interrupt: true,
                    livereload : false
                }
            },
        },
        browserify: {
            components: {
                options: {
                    transform: [
                        [ 'babelify', { presets: [ es2015Preset, reactPreset ] } ]
                    ],
                    browserifyOptions: {
                        paths: [ __dirname + '/node_modules' ]
                    }
                },
                src: [
                    'src/*.js',
                    'src/**/*.js',
                    'src/**/**/*.js'
                ],
                dest:  '../static/build/js/main.js'
            },
        },
        sass: {
            dist: {
                options: {
                    sourceMap: false,
                    includePaths: ['src','assets']
                },
                src: [
                    'src/Main.scss'
                ],
                dest: '../static/build/css/style.css'
            },
        }
    });

    // Define your tasks here
    grunt.registerTask('default', ['bundle:js','sass']);

    grunt.registerTask('bundle:js', [
        'browserify:components'
    ]);

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-sass');

}
