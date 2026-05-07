import ftp from 'vinyl-ftp';
import util from 'gulp-util';
import dotenv from 'dotenv';

dotenv.config();

const config = {
    host: process.env.FTP_HOST,
    user: process.env.FTP_USER,
    password: process.env.FTP_PASSWORD,
    parallel: 10,
    log: util.log
};

const getConn = () => ftp.create(config);

export const deployCSS = () => {
    const conn = getConn();
    return app.gulp.src(`${app.path.build.css}**/*.css`, { base: './', buffer: false })
        .pipe(conn.newer(process.env.FTP_DESTINATION))
        .pipe(conn.dest(process.env.FTP_DESTINATION))
        .pipe(app.plugins.browsersync.stream());
};

export const deployJS = () => {
    const conn = getConn();
    return app.gulp.src(`${app.path.build.js}**/*.js`, { base: './', buffer: false })
        .pipe(conn.newer(process.env.FTP_DESTINATION))
        .pipe(conn.dest(process.env.FTP_DESTINATION))
        .pipe(app.plugins.browsersync.stream());
};

export const deployPHP = (filePath) => {
    const conn = getConn();
    return app.gulp.src(filePath, { base: './', buffer: false })
        .pipe(conn.dest(process.env.FTP_DESTINATION))
        .pipe(app.plugins.browsersync.reload({ stream: true }));
};