module.exports = function (grunt) {
  "use strict";
  // Project configuration.
  var assets = grunt.file.readJSON('assets.json');

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    jshint: {
      files: {
        src: [
          'Gruntfile.js', 'assets/js/*.js'
        ]
      },
      options: {
        globals: {
          'window': true, '_': true, '$': true, 'WH': true,
          'ZeroClipboard': true, 'Handlebars': true, 'JQuery': true,
          'module': true, 'alert': true, 'console': true, 'document': true, 'faker': true,
          'KeyboardEvent': true, 'MouseEvent': true, 'CI': true,
          'MutationObserver': true, 'localStorage': true, 'QUnit': true, 'setTimeout': true,
          'expect': true, 'utils': true, 'Clipboard': true
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
          'assets/compiled/<%= pkg.name %>-assets.min.js': assets.js
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

        files: {
          "assets/css/cfApp.css": "assets/less/cfApp.less",
          "assets/css/mail.css": "assets/less/mail.less",
          "assets/css/articleEditor.css": "assets/less/articleEditor.less"
        }
      }
    },

    watch: {
      javascript: {
        files: ['assets/js/*.js'], // which files to watch
        tasks: ['jshint'],
        options: {
          nospawn: true
        }
      },
      less: {
        files: ['assets/less/*.less'],
        tasks: ['less'],
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
          'assets/compiled/<%= pkg.name %>-assets.min.css': assets.css
        }
      }
    },

    "file-creator": {
      "basic": {
        "assets/compiled/version.txt": function(fs, fd, done) {
          fs.writeSync(fd, new Date().getTime());
          done();
        }
      }
    },

    copy: {
      main: {
        files: [{
          expand: true,
          src: ['node_modules/font-awesome/fonts/*'],
          dest: 'assets/fonts',
          filter: 'isFile',
          flatten: true
        }]
      }
    },

    clean: {
      js: ["assets/compiled/*.js"],
      css: ["assets/compiled/*.css"],
      map: ["assets/compiled/*.map"],
      txt: ["assets/compiled/*.txt"],
    }
  });

  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-file-creator');
  grunt.loadNpmTasks('grunt-contrib-clean');

  // Default task(s).
  grunt.registerTask('build', ['jshint', 'uglify', 'copy', 'less', 'cssmin', 'file-creator']);
  grunt.registerTask('default', ['less', 'jshint', 'watch']);
  // Load the task
  grunt.loadNpmTasks('grunt-notify');
  // This is required if you use any options.
  grunt.task.run('notify_hooks');
};
