const { watch, src, dest, series } = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');
const cleanCSS = require('gulp-clean-css')

function minifyjs() {
	return src('src/*.js')
		.pipe(uglify())
		.pipe(rename({ extname: '.min.js' }))
		.pipe(dest('assets/'));
}

function minifycss() {
	return src('src/*.css')
		.pipe(cleanCSS({ compatibility: 'ie8' }))
		.pipe(rename({ extname: '.min.css' }))
		.pipe(dest('assets/'));
}

exports.watch = function () {
	watch('src/*.js', minifyjs);
	watch('src/*.css', minifycss);
}

exports.default = series(minifyjs, minifycss)