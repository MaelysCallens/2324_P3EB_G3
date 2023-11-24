module.exports = function(grunt) {
  const sass = require('sass');

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    babel: {
      options: {
        sourceMap: false
      },
      dist: {
        files: {
          'js/minified/color.min.js': 'js/dist/color.js',
          'js/minified/dxpr-theme-breadcrumbs.min.js': 'js/dist/dxpr-theme-breadcrumbs.js',
          'js/minified/dxpr-theme-full-screen-search.min.js': 'js/dist/dxpr-theme-full-screen-search.js',
          'js/minified/dxpr-theme-header.min.js': 'js/dist/dxpr-theme-header.js',
          'js/minified/dxpr-theme-multilevel-mobile-nav.min.js': 'js/dist/dxpr-theme-multilevel-mobile-nav.js',
          'js/minified/dxpr-theme-settings.admin.min.js': 'js/dist/dxpr-theme-settings.admin.js'
        }
      }
    },
    terser: {
      options: {
        ecma: 2015
      },
      main: {
        files: {
          'js/minified/classie.min.js': ['vendor/classie.js'],
          'js/minified/color.min.js': ['js/minified/color.min.js'],
          'js/minified/dxpr-theme-breadcrumbs.min.js': ['js/minified/dxpr-theme-breadcrumbs.min.js'],
          'js/minified/dxpr-theme-full-screen-search.min.js': ['js/minified/dxpr-theme-full-screen-search.min.js'],
          'js/minified/dxpr-theme-header.min.js': ['js/minified/dxpr-theme-header.min.js'],
          'js/minified/dxpr-theme-multilevel-mobile-nav.min.js': ['js/minified/dxpr-theme-multilevel-mobile-nav.min.js'],
          'js/minified/dxpr-theme-settings.admin.min.js': ['js/minified/dxpr-theme-settings.admin.min.js']
        },
      }
    },
    sass: {
      options: {
        implementation: sass,
        sourceMap: false,
        outputStyle:'compressed'
      },
      dist: {
        files: [{
          expand: true,
          cwd: 'scss/',
          src: '**/*.scss',
          dest: 'css/',
          ext: '.css',
          extDot: 'last'
        }]
      }
    },
    postcss: {
        options: {
            processors: require('autoprefixer'),
        },
        dist: {
            src: 'css/*.css',
        },
    },
    watch: {
      css: {
        files: ['scss/*.scss', 'scss/**/*.scss'],
        tasks: ['sass', 'postcss']
      },
      js: {
        files: ['js/dist/*.js'],
        tasks: ['babel', 'terser']
      }
    }
  });

  grunt.loadNpmTasks('grunt-babel');
  grunt.loadNpmTasks('grunt-terser');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-postcss');
  grunt.registerTask('default',['watch']);
}
