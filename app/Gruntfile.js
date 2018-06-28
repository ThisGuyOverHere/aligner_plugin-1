module.exports = function(grunt) {

    var es2015Preset = require('babel-preset-es2015');
    var reactPreset = require('babel-preset-react');

    grunt.initConfig( {
        watch: {
            components: {
                files: [
                    'app/src/*.js',
                    'app/src/**/*.js',
                    'app/src/**/**/*.js'
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
                dest:  'static/build/aligner.js'
            },
        },
    });

    // Define your tasks here
    grunt.registerTask('default', ['bundle:js']);

    grunt.registerTask('bundle:js', [
        'browserify:components'
    ]);

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-contrib-watch');

}
