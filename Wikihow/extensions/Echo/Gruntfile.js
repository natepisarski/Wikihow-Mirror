/* eslint-env node */
module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'grunt-svgmin' );

	grunt.initConfig( {
		eslint: {
			options: {
				reportUnusedDisableDirectives: true
			},
			src: [
				'**/*.js',
				'!node_modules/**',
				'!vendor/**',
				'!tests/externals/**',
				'!docs/**'
			]
		},
		// Lint – Styling
		stylelint: {
			options: {
				syntax: 'less'
			},
			all: [
				'modules/**/*.css',
				'modules/**/*.less'
			]
		},
		// SVG Optimization
		svgmin: {
			options: {
				js2svg: {
					indent: '\t',
					pretty: true
				},
				multipass: true,
				plugins: [ {
					cleanupIDs: false
				}, {
					removeDesc: false
				}, {
					removeRasterImages: true
				}, {
					removeTitle: false
				}, {
					removeViewBox: false
				}, {
					removeXMLProcInst: false
				}, {
					sortAttrs: true
				} ]
			},
			all: {
				files: [ {
					expand: true,
					cwd: 'modules/icons',
					src: [
						'**/*.svg'
					],
					dest: 'modules/icons/',
					ext: '.svg'
				} ]
			}
		},
		banana: conf.MessagesDirs,
		watch: {
			files: [
				'.{stylelintrc,eslintrc}.json',
				'<%= eslint.all %>',
				'<%= stylelint.all %>'
			],
			tasks: 'test'
		},
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**',
				'!docs/**'
			]
		}
	} );

	grunt.registerTask( 'minify', 'svgmin' );
	grunt.registerTask( 'lint', [ 'eslint', 'stylelint', 'jsonlint', 'banana' ] );
	grunt.registerTask( 'test', 'lint' );
	grunt.registerTask( 'default', [ 'minify', 'test' ] );
};
