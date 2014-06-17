<?php 

namespace UAS;

// Loads the class
require 'vendor/UASparser_0.93/UASparser.php';

// Creates a new UASparser object and set cache dir (this php script must right write to cache dir)
$parser = new Parser();
$parser->SetCacheDir( './cache' );

// Directory Config
$in		= './in';
$out	= './out';


if ( $dh = opendir( $in ) )
{
    
    while ( FALSE !== ( $file = readdir($dh) ) )
	{
        if ( substr( $file, -4 ) == '.csv' )
		{
			# read in the UA Strings
			$fh = fopen( $in . '/' . $file, 'r' );
			$data = array();
			while ( FALSE !== ( $row = fgetcsv( $fh, 10000, ',', '"' ) ) )
			{
				array_push( $data, $row );
			}
			fclose( $fh );
			
			if ( count( $data ) )
			{
				# grab the header row form the original data & remove the first item (the UA string)
				$headers = array_shift( $data );
				
				# get more info
				$output = getUADetails( $data, $headers );
				
				# write to the file
				$fh = fopen( $out . '/' . $file, 'w' );
				foreach ( $output as $row )
				{
					fputcsv( $fh, $row );
				}
				fclose( $fh );
			}
		}
    }
    closedir( $dh );

}

function getUADetails( $data, $headers )
{
    global $parser;

	# what do we want back?
	$fields = array(
		'typ'				=> 'Type',
		# UA Info
		'ua_family'			=> 'UA Family',
		'ua_name'			=> 'UA Name',
		'ua_version'		=> 'UA Version',
		'ua_url'			=> 'UA URL',
		'ua_company'		=> 'UA Company',
		'ua_company_url'	=> 'UA Company URL',
		'ua_icon'			=> 'UA Icon',
		# OS Info
		'os_family'			=> 'OS Family',
		'os_name'			=> 'OS Name',
		'os_url'			=> 'OS URL',
		'os_company'		=> 'OS Company',
		'os_company_url'	=> 'OS URL',
		'os_icon'			=> 'OS Icon',
		# Device Info
		'device_type'		=> 'Device Type',
		'device_icon'		=> 'Device Icon',
		'device_info_url'	=> 'Device Info URL'
	);

	# create the hash
	$output = array();
	
	foreach ( $data as $row )
	{
		# UA must be the first column
		$UA = $row;
		
		# get the details
		$result = $parser->parse( $row[0] );
		
		# Success!
		if ( count( $result ) )
		{
			echo "We found info on {$row[0]}\r\n";

			foreach ( array_keys( $fields ) as $key )
			{
				$UA[] = $result[$key];
			}
		}
		
		# No result
		else
		{
			echo "We could not find {$row[0]}\r\n";
		}

		# push it to the output
		array_push( $output, $UA );

	}
	
	if ( count( $output ) )
	{
		# prepend the final headers row (merging in the original headers)
		array_unshift( $output, array_merge( $headers, array_values( $fields ) ) );
	}
	
	return $output;
}

?> 