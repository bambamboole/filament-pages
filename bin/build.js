import esbuild from 'esbuild'
import { copyFileSync, mkdirSync, watch as fsWatch } from 'node:fs'
import { dirname } from 'node:path'

const isDev = process.argv.includes('--dev')

const cssSrc = './resources/css/filament-pages.css'
const cssDist = './resources/dist/css/filament-pages.css'

function copyCSS() {
    mkdirSync(dirname(cssDist), { recursive: true })
    copyFileSync(cssSrc, cssDist)
    console.log(`CSS copied at ${new Date(Date.now()).toLocaleTimeString()}: ${cssDist}`)
}

async function compile(options) {
    const context = await esbuild.context(options)

    if (isDev) {
        await context.watch()
    } else {
        await context.rebuild()
        await context.dispose()
    }
}

const defaultOptions = {
    define: {
        'process.env.NODE_ENV': isDev ? `'development'` : `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    target: ['es2020'],
    minify: !isDev,
    plugins: [{
        name: 'watchPlugin',
        setup: function (build) {
            build.onStart(() => {
                console.log(`Build started at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
            })

            build.onEnd((result) => {
                if (result.errors.length > 0) {
                    console.log(`Build failed at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`, result.errors)
                } else {
                    console.log(`Build finished at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
                }
            })
        }
    }],
}

copyCSS()

if (isDev) {
    fsWatch(cssSrc, () => copyCSS())
}

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/index.js'],
    outfile: './resources/dist/js/filament-pages.js',
}).then(() => {
    console.log(`Build completed for filament-pages.js`)
})
