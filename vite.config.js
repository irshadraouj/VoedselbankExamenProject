import { defineConfig } from "vite";
import path from "path";
import fs from "fs";
import { ViteImageOptimizer } from 'vite-plugin-image-optimizer';
import { faviconsPlugin } from "@darkobits/vite-plugin-favicons";
import tailwindcss from "@tailwindcss/vite";
import outputPlugin from "./framework/Frontend/Scripts/OutputPlugin";


const resourceDir = path.resolve(__dirname, "resources");

const getFiles = (ext, exclude = []) =>
  fs
    .readdirSync(resourceDir)
    .filter(
      (fileName) =>
        fileName.endsWith(ext) &&
        !exclude.includes(fileName) && // Exclude specific filenames
        fs.statSync(path.join(resourceDir, fileName)).isFile() // Ensure it's a file
    )
    .map((fileName) => path.resolve(resourceDir, fileName));

// Collect input files (JS and SCSS)
const JSInputFiles = getFiles(".js");
const SCSSInputFiles = getFiles(".scss");
const CSSInputFiles = getFiles(".css");
const inputFiles = [...JSInputFiles, ...SCSSInputFiles, ...CSSInputFiles];


export default defineConfig(({ mode }) => {

  // Define input files
  const URL = process.env.DDEV_PRIMARY_URL;
  const HOST = process.env.DDEV_HOSTNAME;

  
  console.log(`
Running in ${mode} mode
Primary URL: ${URL}
Hostname: ${HOST}\n`
  );

  return {
    css: {
      devSourcemap: true,
      preprocessorOptions: {
        scss: {
          silenceDeprecations: ["legacy-js-api"],
        },
      },
    },
    resolve: {
      alias: {
        "/js": path.resolve(__dirname, "resources/js"),
        "/scss": path.resolve(__dirname, "resources/scss"),
        "/images": path.resolve(__dirname, "resources/images"),
        "/fonts": path.resolve(__dirname, "resources/fonts"),
        "@components": path.resolve(__dirname, "resources/components"),
        "@functions": path.resolve(__dirname, "resources/_functions"),
        "/resources": path.resolve(__dirname, "resources"),
      },
    },
    optimizeDeps: {
      include: ["resources/**/*.pug"],
    },
    plugins: [
      faviconsPlugin({
        lang: 'nl-NL',
        inject: false,
        start_url: '/',
        appDescription: '',
        cache: true,
        icons: {
          favicons: { source: "resources/images/meta/favicon.svg" },
          android: { source: "resources/images/meta/favicon.svg" },
          appleStartup: { source: "resources/images/meta/favicon.svg" },
        }
      }),
      tailwindcss(),
      ViteImageOptimizer(),
      outputPlugin
    ],
    build: {
      manifest: "assets/manifest.json",
      assetsInlineLimit: 0,
      // Set output directory based on PROD_MODE
      outDir: path.resolve(__dirname, "website/public_html/"),
      emptyOutDir: false,
      rollupOptions: {
        input: inputFiles,
        output: {
          chunkFileNames: "assets/js/[name]-[hash].[ext]",
          entryFileNames: "assets/js/[name]-[hash].js",
          // chunkFileNames: "assets/js/[name].js",
          // entryFileNames: "assets/js/[name].js",
          assetFileNames: (assetInfo) => {
            if (/\.(gif|jpe?g|png|svg|webp)$/.test(assetInfo.name ?? "")) {
              // return "assets/images/[name][extname]";
              return "assets/images/[name][extname]";
            }

            if (/\.css$/.test(assetInfo.name ?? "")) {
              // return "assets/css/[name][extname]";
              return "assets/css/[name]-[hash][extname]";
            }
            if (/\.ico$/.test(assetInfo.name ?? "")) {
              // return "assets/css/[name][extname]";
              return "[name][extname]";
            }
            if (/\.(woff|woff2|ttf|eot)$/.test(assetInfo.name ?? "")) {
              // return "assets/fonts/[name][extname]";
              return "assets/fonts/[name]-[hash][extname]";
            }
            // default value
            // ref: https://rollupjs.org/guide/en/#outputassetfilenames
            // return "assets/[name][extname]";
            return "assets/[name]-[hash][extname]";
          },
        },
      },
    },
    server: URL ? {
          // respond to all network requests:
          host: "0.0.0.0",
          port: 5173,
          strictPort: true,
          // Defines the origin of the generated asset URLs during development
          origin: `${URL.replace(/:\d+$/, "")}:5173`,
          // Configure CORS for the dev server (security)
          cors: {
            origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
          },
    } : {},
  };
});
