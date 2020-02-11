var gulp = require('gulp');
var sass = require('gulp-sass');
var cssnano = require('gulp-cssnano');
gulp.task('sass', function() {
   return gulp.src('scss/style.scss')
       .pipe(sass())
       .pipe(gulp.dest('public'));
});
gulp.task('nano', function() {
    return gulp.src('public/style.css')
        .pipe(cssnano())
        .pipe(gulp.dest('public'));
});
gulp.task('default', gulp.series('sass', 'nano'));