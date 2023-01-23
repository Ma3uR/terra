// Load plugins
const gulp = require("gulp");
const sass = require("gulp-sass");
const postcss = require("gulp-postcss");
const autoprefixer = require("autoprefixer");


let style = 'scss',
    js = 'js';

// CSS task
function css() {
    return gulp
        .src(style + '/*.scss')
        .pipe(sass().on('error',sass.logError))
        .pipe(postcss([
            autoprefixer()
        ]))
        .pipe(gulp.dest(style + "../../../../../../../../../../public"))
}

function scripts() {
    return (
        gulp
            .src([js + '/*.js'])
            .pipe(gulp.dest(js + '../../../../../../../../../../public'))
    );
}

// Watch files
function watchFiles() {
    gulp.watch(style + '/**/*.scss', css);
    gulp.watch(js + '/script.js', scripts);
}

const build = gulp.parallel(css, watchFiles);

exports.css = css;
exports.js = scripts;
exports.watch = watchFiles;
exports.default = build;


