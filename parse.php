<?php 

// XML-RPC Config 
$access_key	= 'free'; //"free" or Your key 

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
    global $path, $host, $port;

	# include client lib from http://phpxmlrpc.sourceforge.net/ 
    require_once 'XML/RPC2/Client.php';
	require_once 'XML/RPC2/Value.php';
	
	$client = XML_RPC2_Client::create(
		'http://user-agent-string.info/rpc/rpcxml.php',
		array( 'prefix'	=> 'ua.' )
	);

	# what do we want back?
	$fields = array(
		'typ'				=> 'Type',
		# UA Info
		'ua_family'			=> 'UA Family',
		'ua_name'			=> 'UA Name',
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
	);

	# create the hash
	$output = array();
	
	foreach ( $data as $row )
	{
		# UA must be the first column
		$ua = base64_encode( $row[0] );
		
		# build the query
		try {
			
			$result = $client->search( $ua, $access_key ); 

			# get the flag
			$flag = $result['flag']; 
			
			# if flag == 5 -> system error 
			if ( $flag == 5 )
			{
				echo "ERROR text: " . $result['errortext'];
			}
			else
			{
				
				# start with the original data
				$UA = $row;

				# add the results
				foreach ( array_keys( $fields ) as $key )
				{
					$UA[] = $result[$key];
				}
				
				# push it to the output
				array_push( $output, $UA );

			} 

		}
		catch ( XML_RPC2_FaultException $e )
		{
		    // The XMLRPC server returns a XMLRPC error
			die( 'Exception #' . $e->getFaultCode() . ' : ' . $e->getFaultString() );
		}
		catch ( Exception $e )
		{
		    // Other errors (HTTP or networking problems...)
		    die( 'Exception : ' . $e->getMessage() );
		}
	}
	
	if ( count( $output ) )
	{
		# prepend the final headers row (merging in the original headers)
		array_unshift( $output, array_merge( $headers, array_values( $fields ) ) );
	}
	
	return $output;
}

?> 