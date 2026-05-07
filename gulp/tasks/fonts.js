import fs from "fs";
import fonter from "gulp-fonter";
import ttf2woff2 from "gulp-ttf2woff2";

export const otf2ttf = () => {
    return app.gulp
        .src(`${app.path.srcFolder}/fonts/*.otf`, {})
        .pipe(
            app.plugins.plumber(
                app.plugins.notify.onError({
                    title: "FONTS",
                    message: "Error: <%= error.message %>",
                })
            )
        )
        .pipe(
            fonter({
                formats: ["ttf"],
            })
        )
        .pipe(app.gulp.dest(`${app.path.srcFolder}/fonts/`));
};

export const ttfToWoff = () => {
    return app.gulp
        .src(`${app.path.srcFolder}/fonts/*.ttf`, {})
        .pipe(
            app.plugins.plumber(
                app.plugins.notify.onError({
                    title: "FONTS",
                    message: "Error: <%= error.message %>",
                })
            )
        )
        .pipe(
            fonter({
                formats: ["woff"],
            })
        )
        .pipe(app.gulp.dest(`${app.path.build.fonts}`))
        .pipe(app.gulp.src(`${app.path.srcFolder}/fonts/*.ttf`))
        .pipe(ttf2woff2())
        .pipe(app.gulp.dest(`${app.path.build.fonts}`));
};
export const copyWoff = () => {
    return app.gulp
        .src(`${app.path.srcFolder}/fonts/*.woff`)
        .pipe(app.gulp.dest(`${app.path.build.fonts}`))
        .pipe(app.gulp.src(`${app.path.srcFolder}/fonts/*.woff2`))
        .pipe(app.gulp.dest(`${app.path.build.fonts}`));
};

export const fontsStyle = () => {
    let fontsFile = `${app.path.srcFolder}/scss/fonts.scss`;

    fs.readdir(app.path.build.fonts, function (err, fontsFiles) {
        if (fontsFiles) {
            if (fs.existsSync(fontsFile)) {
                fs.unlinkSync(fontsFile);
                console.log("Файл scss/fonts.scss актуализирован");
            } else {
                console.log("Файл scss/fonts.scss создан");
            }

            fs.writeFile(fontsFile, "", cb);
            let newFileOnly;

            const version = Date.now();

            for (let i = 0; i < fontsFiles.length; i++) {
                let fontFileNameWithExtension = fontsFiles[i].split(".")[0];
                let fontFileName = fontFileNameWithExtension;

                const isVariableFont = fontFileName.toLowerCase().includes("variablefont_");

                if (isVariableFont) {
                    fontFileName = fontFileName.replace(/-VariableFont_wght/i, "");
                }

                if (newFileOnly !== fontFileName) {
                    let fontName = fontFileName.split("-")[0] ? fontFileName.split("-")[0] : fontFileName;
                    let fontWeight = fontFileName.split("-")[1] || '';
                    let fontStyle = 'normal';

                    if (!isVariableFont) {
                        if (fontWeight.toLowerCase().includes("italic")) {
                            fontStyle = "italic";
                            fontWeight = fontWeight.replace(/italic/i, "").trim();
                        }

                        switch (fontWeight.toLowerCase()) {
                            case "thin": fontWeight = 100; break;
                            case "extralight": fontWeight = 200; break;
                            case "light": fontWeight = 300; break;
                            case "book": fontWeight = 450; break;
                            case "medium": fontWeight = 500; break;
                            case "semibold":
                            case "demi": fontWeight = 600; break;
                            case "bold": fontWeight = 700; break;
                            case "extrabold":
                            case "heavy": fontWeight = 800; break;
                            case "black": fontWeight = 900; break;
                            default: fontWeight = 400; break;
                        }
                    }

                    if (isVariableFont) {
                        fs.appendFile(fontsFile,
                            `@font-face {
                                font-family: '${fontName}';
                                src: url("../fonts/${fontFileNameWithExtension}.woff2?v=${version}") format("woff2 supports variations"),
                                    url("../fonts/${fontFileNameWithExtension}.woff2?v=${version}") format("woff2-variations"),
                                    url("../fonts/${fontFileNameWithExtension}.woff?v=${version}") format("woff");
                                font-weight: 100 900;
                                font-stretch: 75% 125%;
                                font-style: normal;
                                font-display: swap;
                            }\r\n`, cb);
                    } else {
                        fs.appendFile(fontsFile,
                            `@font-face {
                                font-family: '${fontName}';
                                font-display: swap;
                                src: url("../fonts/${fontFileNameWithExtension}.woff2?v=${version}") format("woff2"), url("../fonts/${fontFileNameWithExtension}.woff?v=${version}") format("woff");
                                font-weight: ${fontWeight};
                                font-style: ${fontStyle};
                            }\r\n`, cb);
                    }

                    newFileOnly = fontFileName;
                }
            }
        }
    });

    return app.gulp.src(`${app.path.srcFolder}`);
    function cb() { }
};
