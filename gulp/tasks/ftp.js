import ftp from "vinyl-ftp";
import util from "gulp-util";
import dotenv from "dotenv";

dotenv.config();

export const deploy = () => {
	const conn = ftp.create({
		host: process.env.FTP_HOST,
		user: process.env.FTP_USER,
		password: process.env.FTP_PASSWORD,
		parallel: 10,
		log: util.log,
	});

	const globs = [`./assets/**`, `./**/*.php`, `!./node_modules/**`, `!./src/**`, `!./gulp/**`, `!./package*.json`, `!./.env*`, `!./.git/**`];

	return app.gulp
		.src(globs, { base: "./", buffer: false })
		.pipe(conn.newer(process.env.FTP_DESTINATION))
		.pipe(conn.dest(process.env.FTP_DESTINATION))
		.pipe(app.plugins.browsersync.stream());
};
