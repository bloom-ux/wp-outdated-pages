const Encore = require( '@symfony/webpack-encore' );
const { runtimeConfig } = require('@symfony/webpack-encore/lib/context');
const path = require( 'path' );

const directoryName = path.dirname( __filename ).split('/').pop();

Encore
	.setOutputPath( 'assets/dist' )
	.setPublicPath( `/wp-content/plugins/${directoryName}/assets/dist` )
	.setManifestKeyPrefix( '' )
	.enableSingleRuntimeChunk( )
	.enableSourceMaps( ! Encore.isProduction() )
	.enableVersioning( Encore.isProduction() )
	.enableVueLoader( () => {}, { runtimeCompilerBuild: false })
	.cleanupOutputBeforeBuild( )
	.enableSassLoader( )
	.enablePostCssLoader( )
	.addStyleEntry( 'backend-styles', './assets/src/sass/backend.scss' )
	.addStyleEntry( 'frontend-styles', './assets/src/sass/frontend.scss' )
	.addEntry( 'backend-scripts', './assets/src/js/backend.js' )
	.addEntry( 'frontend-scripts', './assets/src/js/frontend.js' )
;

/**
 * Webpack configuration object.
 *
 * Edit for advanced configs.
 */
let config = Encore.getWebpackConfig();

module.exports = config;
