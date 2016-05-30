/* ---- THE FOLLOWING CONFIG SHOULD BE EDITED ---- */

var pkg = require( './package.json' );

var config = {
	pluginSlug: pkg.name,
	pluginName: 'WPDLib',
	pluginURI: pkg.homepage,
	author: pkg.author.name,
	authorURI: pkg.author.url,
	authorEmail: pkg.author.email,
	description: pkg.description,
	version: pkg.version,
	license: pkg.license.name,
	licenseURI: pkg.license.url,
	translateURI: 'https://github.com/felixarntz/' + pkg.name + '/'
};

/* ---- DO NOT EDIT BELOW THIS LINE ---- */

// header for minified assets
var assetheader =	'/*!\n' +
					' * ' + config.pluginName + ' (' + config.pluginURI + ')\n' +
					' * By ' + config.author + ' (' + config.authorURI + ')\n' +
					' * Licensed under ' + config.license + ' (' + config.licenseURI + ')\n' +
					' */\n';


/* ---- REQUIRED DEPENDENCIES ---- */

var gulp = require( 'gulp' );

var sass = require( 'gulp-sass' );
var csscomb = require( 'gulp-csscomb' );
var minifyCSS = require( 'gulp-minify-css' );
var jshint = require( 'gulp-jshint' );
var concat = require( 'gulp-concat' );
var uglify = require( 'gulp-uglify' );
var gutil = require( 'gulp-util' );
var rename = require( 'gulp-rename' );
var replace = require( 'gulp-replace' );
var sort = require( 'gulp-sort' );
var banner = require( 'gulp-banner' );
var wpPot = require( 'gulp-wp-pot' );
var composer = require( 'gulp-composer' );
var bower = require( 'bower' );

var paths = {
	php: {
		files: [ './*.php', './inc/**/*.php' ]
	},
	sass: {
		files: [ './assets/src/sass/**/*.scss' ],
		src: './assets/src/sass/',
		dst: './assets/dist/css/'
	},
	js: {
		files: [ './assets/src/js/**/*.js' ],
		src: './assets/src/js/',
		dst: './assets/dist/js/'
	}
};

/* ---- MAIN TASKS ---- */

// general task (compile Sass and JavaScript and refresh POT file)
gulp.task( 'default', [ 'sass', 'js' ]);

// watch Sass and JavaScript files
gulp.task( 'watch', function() {
	gulp.watch( paths.sass.files, [ 'sass' ]);
	gulp.watch( paths.js.files, [ 'js' ]);
});

// build the plugin
gulp.task( 'build', [ 'author-replace' ], function() {
	gulp.start( 'default' );
});

/* ---- SUB TASKS ---- */

// compile Sass
gulp.task( 'sass', function( done ) {
	gulp.src( paths.sass.files )
		.pipe( sass({
			errLogToConsole: true
		}) )
		.pipe( csscomb() )
		.pipe( banner( assetheader ) )
		.pipe( gulp.dest( paths.sass.dst ) )
		.pipe( minifyCSS({
			keepSpecialComments: 0
		}) )
		.pipe( banner( assetheader ) )
		.pipe( rename({
			extname: '.min.css'
		}) )
		.pipe( gulp.dest( paths.sass.dst ) )
		.on( 'end', done );
});

// compile JavaScript
gulp.task( 'js', function( done ) {
	gulp.src( paths.js.files )
		.pipe( jshint({
			lookup: true
		}) )
		.pipe( banner( assetheader ) )
		.pipe( gulp.dest( paths.js.dst ) )
		.pipe( uglify() )
		.pipe( banner( assetheader ) )
		.pipe( rename({
			extname: '.min.js'
		}) )
		.pipe( gulp.dest( paths.js.dst ) )
		.on( 'end', done );
});

// generate POT file
gulp.task( 'pot', function( done ) {
	gulp.src( paths.php.files, { base: './' })
		.pipe( sort() )
		.pipe( wpPot({
			domain: config.pluginSlug,
			destFile: './languages/' + config.pluginSlug + '.pot',
			headers: {
				'Project-Id-Version': config.pluginName + ' ' + config.version,
				'report-msgid-bugs-to': config.translateURI,
				'x-generator': 'gulp-wp-pot',
				'x-poedit-basepath': '.',
				'x-poedit-language': 'English',
				'x-poedit-country': 'UNITED STATES',
				'x-poedit-sourcecharset': 'uft-8',
				'x-poedit-keywordslist': '__;_e;_x:1,2c;_ex:1,2c;_n:1,2; _nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_attr__; esc_html__;esc_attr_e; esc_html_e;esc_attr_x:1,2c; esc_html_x:1,2c;',
				'x-poedit-bookmars': '',
				'x-poedit-searchpath-0': '.',
				'x-textdomain-support': 'yes'
			}
		}) )
		.pipe( gulp.dest( './' ) )
		.on( 'end', done );
});

// replace the author header in all PHP files
gulp.task( 'author-replace', function( done ) {
	gulp.src( paths.php.files, { base: './' })
		.pipe( replace( /\*\s@author\s[^\r\n]+/, '* @author ' + config.author + ' <' + config.authorEmail + '>' ) )
		.pipe( gulp.dest( './' ) )
		.on( 'end', done );
});

// install Bower components
gulp.task( 'bower-install', function() {
	return bower.commands.install()
		.on( 'log', function( data ) {
			gutil.log( 'bower', gutil.colors.cyan( data.id ), data.message );
		});
});
