import webpack from 'webpack-stream'
import named from 'vinyl-named';

export const js = () => {
    return app.gulp.src(app.path.src.js, { sourcemaps: app.isDev })
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "JS",
                message: "Error: <%= error.message %>"
            }))
        )
        .pipe(webpack({
            mode: 'production',
            output: {
                filename: 'app.min.js',
            }
        }))
        .pipe(app.gulp.dest(app.path.build.js))
        .pipe(app.gulp.src(app.path.src.js))
        .pipe(app.gulp.dest(app.path.build.js))
        .pipe(app.plugins.browsersync.stream());
}


export const jsChunks = () => {
    return app.gulp.src(app.path.src.jsChunks)
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "JS Chunks",
                message: "Error: <%= error.message %>"
            }))
        )
        .pipe(named())
        .pipe(webpack({
            mode: 'production',
            output: {
                filename: '[name].min.js',
            },
        }))
        .pipe(app.gulp.dest(app.path.build.jsChunks))
        .pipe(app.gulp.src(app.path.src.jsChunks))
        .pipe(app.gulp.dest(app.path.build.jsChunks))
        .pipe(app.plugins.browsersync.stream());
}

export const copyJsLibs = () => {
    return app.gulp.src(app.path.src.jsLibs)
        .pipe(app.gulp.dest(app.path.build.jsLibs))
}
