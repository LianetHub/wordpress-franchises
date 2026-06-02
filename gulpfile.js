import gulp from "gulp";

import { path } from "./gulp/config/path.js";
import { plugins } from "./gulp/config/plugins.js";

global.app = {
    isBuild: process.argv.includes("--build"),
    isDev: !process.argv.includes("--build"),
    path: path,
    gulp: gulp,
    plugins: plugins,
};


import { copy } from "./gulp/tasks/copy.js";
import { reset } from "./gulp/tasks/reset.js";
import { server } from "./gulp/tasks/server.js";
import { deploy } from "./gulp/tasks/ftp.js";
import { scss, copyCssLibs } from "./gulp/tasks/scss.js";
import { normalize } from "./gulp/tasks/scss.js";
import { js, copyJsLibs, jsChunks } from "./gulp/tasks/js.js";
import { images, favicon } from "./gulp/tasks/images.js";
import {
    otf2ttf,
    ttfToWoff,
    copyWoff,
    fontsStyle,
} from "./gulp/tasks/fonts.js";
import { zip } from "./gulp/tasks/zip.js";
import { json } from "./gulp/tasks/json.js";

function watcher() {
    gulp.watch(path.watch.files, copy);
    gulp.watch(path.watch.scss, gulp.series(scss, deploy));
    gulp.watch(path.watch.normalize, normalize);
    gulp.watch(path.watch.js, gulp.series(js, deploy));
    gulp.watch(path.watch.jsChunks, jsChunks);
    gulp.watch(path.watch.json, json);
    gulp.watch(path.watch.images, images);
    gulp.watch(path.watch.fonts, fonts);
    gulp.watch(path.watch.php, gulp.series(deploy)).on("change", app.plugins.browsersync.reload);
}

const fonts = gulp.series(otf2ttf, ttfToWoff, copyWoff, fontsStyle);

const mainTasks = gulp.series(
    fonts,
    gulp.parallel(copy, normalize, scss, copyCssLibs, favicon, js, copyJsLibs, jsChunks, json, images)
);

const dev = gulp.series(reset, mainTasks, gulp.parallel(watcher, server));
const build = gulp.series(reset, mainTasks);
const deployZIP = gulp.series(reset, mainTasks, zip);

export { dev };
export { build };
export { deployZIP };

gulp.task("default", dev);
