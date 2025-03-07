const fs = require( 'fs-extra' );
const path = require( 'path' );

const srcDir = path.join( __dirname, '..' );
const buildDir = path.join( __dirname, '../build' );

// Define directories and files to copy
const dirsToCopy = [ 'includes', 'assets', 'templates' ];
const filesToCopy = [ 'README.md', 'wpchill-kb.php', 'readme.txt' ];

// Ensure build directory exists
fs.ensureDirSync( buildDir );

// Copy directories
dirsToCopy.forEach( ( dir ) => {
	fs.copySync( path.join( srcDir, dir ), path.join( buildDir, dir ), {
		filter: ( src ) => ! src.includes( 'node_modules' ),
	} );
} );

// Copy files
filesToCopy.forEach( ( file ) => {
	fs.copySync( path.join( srcDir, file ), path.join( buildDir, file ) );
} );

console.log( 'Files copied to build directory' );
