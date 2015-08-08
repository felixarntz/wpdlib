'use strict';
module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),
		banner: '/*!\n' +
				' * <%= pkg.name %> version <%= pkg.version %>\n' +
				' * \n' +
				' * <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
				' */',
		fileheader: '/**\n' +
					' * @package WPDLib\n' +
					' * @version <%= pkg.version %>\n' +
					' * @author <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
					' */',

		clean: {
			fields: [
				'assets/fields.css',
				'assets/fields.min.css',
				'assets/fields.min.js'
			],
			translation: [
				'languages/wpdlib.pot'
			]
		},

		jshint: {
			options: {
				boss: true,
				curly: true,
				eqeqeq: true,
				immed: true,
				noarg: true,
				quotmark: "single",
				undef: true,
				unused: true,
				browser: true,
				globals: {
					jQuery: false,
					console: false,
					wp: false,
					_wpdlib_data: false,
					ajaxurl: false
				}
			},
			fields: {
				src: [
					'assets/fields.js'
				]
			}
		},

		uglify: {
			options: {
				preserveComments: 'some',
				report: 'min'
			},
			fields: {
				src: 'assets/fields.js',
				dest: 'assets/fields.min.js'
			}
		},

		recess: {
			options: {
				compile: true,
				compress: false,
				noIDS: true,
				noJSPrefix: true,
				noOverqualifying: false,
				noUnderscores: true,
				noUniversalSelectors: false,
				strictPropertyOrder: true,
				zeroUnits: true
			},
			fields: {
				files: {
					'assets/fields.css': 'assets/fields.less'
				}
			}
		},

		autoprefixer: {
			options: {
				browsers: [
					'Android 2.3',
					'Android >= 4',
					'Chrome >= 20',
					'Firefox >= 24',
					'Explorer >= 8',
					'iOS >= 6',
					'Opera >= 12',
					'Safari >= 6'
				]
			},
			fields: {
				src: 'assets/fields.css'
			}
    	},

		cssmin: {
			options: {
				compatibility: 'ie8',
				keepSpecialComments: '*',
				noAdvanced: true
			},
			fields: {
				files: {
					'assets/fields.min.css': 'assets/fields.css'
				}
			}
		},

		usebanner: {
			options: {
				position: 'top',
				banner: '<%= banner %>'
			},
			fields: {
				src: [
					'assets/fields.min.css',
					'assets/fields.min.js'
				]
			}
		},

		replace: {
			version: {
				src: [
					'inc/**/*.php'
				],
				overwrite: true,
				replacements: [{
					from: /\/\*\*\s+\*\s@package\s[^*]+\s+\*\s@version\s[^*]+\s+\*\s@author\s[^*]+\s\*\//,
					to: '<%= fileheader %>'
				}]
			}
		},

		makepot: {
			translation: {
				options: {
					mainFile: 'index.php',
					domainPath: '/languages',
					exclude: [ 'vendor/.*' ],
					potComments: 'Copyright (c) 2014-<%= grunt.template.today("yyyy") %> <%= pkg.author.name %>',
					potFilename: 'wpdlib.pot',
					potHeaders: {
						'language-team': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'last-translator': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'project-id-version': '<%= pkg.name %> <%= pkg.version %>',
						'report-msgid-bugs-to': '<%= pkg.homepage %>',
						'x-generator': 'grunt-wp-i18n 0.5.3',
						'x-poedit-basepath': '.',
						'x-poedit-language': 'English',
						'x-poedit-country': 'UNITED STATES',
						'x-poedit-sourcecharset': 'uft-8',
						'x-poedit-keywordslist': '__;_e;_x:1,2c;_ex:1,2c;_n:1,2; _nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_attr__; esc_html__;esc_attr_e; esc_html_e;esc_attr_x:1,2c; esc_html_x:1,2c;',
						'x-poedit-bookmars': '',
						'x-poedit-searchpath-0': '.',
						'x-textdomain-support': 'yes'
					}
				}
			}
		}

 	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-recess');
	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-banner');
	grunt.loadNpmTasks('grunt-text-replace');
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask('fields', [
		'clean:fields',
		'jshint:fields',
		'uglify:fields',
		'recess:fields',
		'autoprefixer:fields',
		'cssmin:fields',
	]);

	grunt.registerTask('translation', [
		'clean:translation',
		'makepot:translation'
	]);

	grunt.registerTask('default', [
		'fields'
	]);

	grunt.registerTask('build', [
		'fields',
		'translation',
		'usebanner',
		'replace:version'
	]);
};
