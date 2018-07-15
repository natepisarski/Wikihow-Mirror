module.exports = function (grunt) {
	"use strict";
	// Project configuration.
	var assets = grunt.file.readJSON('assets.json');

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		jshint: {
			files: {
				src: [
					'Gruntfile.js',
					'assets/js/*.js'
				]
			},
			options: {
				globals: {
					'window': true, '_': true, '$': true, 'WH': true, 
					'ZeroClipboard': true, 'Handlebars': true, 'JQuery': true, 
					'module': true, 'alert': true, 'console': true, 
					'MutationObserver': true, 'localStorage': true
				},
				indent: 2,
				nomen: true,
				strict: true,
				undef: true,
				regexp: true,
				esnext: false,
				moz: true,
				boss: true,
				node: false,
				validthis: true,
				unused: true
			}
		},

		uglify: {
			options: {
				banner: "/*\n<%= pkg.name %>\nversion: <%= pkg.version %>\ncompiled: <%= grunt.template.today('yyyy-mm-dd') %>\n*/",
				mangle: false,
				sourceMap: true,
				wrap: false
			},
			my_target: {
				files: {
					'assets/compiled/<%= pkg.name %>.min.js': assets.js
				}
			}
		},

		copy: {
			main: {
				files: [
					{
						expand: true,
						src: ['node_modules/font-awesome/fonts/*'],
						dest: 'assets/fonts',
						filter: 'isFile',
						flatten: true
					},
					{
						expand: true,
						src: ['node_modules/zeroclipboard/dist/ZeroClipboard.swf'],
						dest: 'assets/compiled/',
						filter: 'isFile',
						flatten: true
					}
				]
			}
		},

		lesslint: {
			src: ['assets/less/*.less'],
			options: {
				csslint: {
					'ids': false,
					'adjoining-classes': false,
					'box-model': false,
					'important': false,
					'font-sizes': false,
					'floats': false,
					'unique-headings': false
				}
			}
		},

		less: {
			options: {
				title: "Grunt building less files",
				message: "Less task complete"
			},

			development: {
				options: {
					sourceMap: false,
					compress: false
				},
				files: [
					{
						expand: true,
						cwd: 'assets/less',
						src: ['assets/*.less'],
						dest: 'assets/css/',
						ext: '.css'
					}
				]
			}
		},

		watch: {
			styles: {
				files: ['assets/less/*.less', 'assets/js/*.js'], // which files to watch
				tasks: ['jshint', 'lesslint', 'less'],
				options: {
					nospawn: true
				}
			}
		},

		cssmin: {
			options: {
				shorthandCompacting: false,
				sourceMap: true,
				roundingPrecision: -1
			},
			target: {
				files: {
					'compiled/<%= pkg.name %>.min.css': assets.css
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-lesslint');
	grunt.loadNpmTasks('grunt-contrib-copy');

	// Default task(s).
	grunt.registerTask('build', ['jshint', 'uglify', 'lesslint', 'copy', 'less', 'cssmin']);
	grunt.registerTask('default', ['less', 'watch']);
	// Load the task
	grunt.loadNpmTasks('grunt-notify');
	// This is required if you use any options.
	grunt.task.run('notify_hooks');
};
