const fs = require( 'fs' );
const path = require( 'path' );
const archiver = require( 'archiver' );

const pluginFolder = 'wpchill-kb';
const version = require( '../package.json' ).version;

const output = fs.createWriteStream(
	path.join( __dirname, `../${ pluginFolder }-${ version }.zip` ),
);
const archive = archiver( 'zip', {
	zlib: { level: 9 },
} );

output.on( 'close', function() {
	console.log( archive.pointer() + ' total bytes' );
	console.log(
		'Archive has been finalized and the output file descriptor has closed.',
	);
} );
archive.on( 'error', function( err ) {
	throw err;
} );

archive.pipe( output );

archive.directory( 'build/includes/', `${ pluginFolder }/includes` );
archive.directory( 'build/templates/', `${ pluginFolder }/templates` );
archive.directory( 'build/assets/', `${ pluginFolder }/assets` );
archive.file( 'build/wpchill-kb.php', {
	name: `${ pluginFolder }/wpchill-kb.php`,
} );
archive.file( 'build/README.md', { name: `${ pluginFolder }/README.md` } );

archive.finalize();
