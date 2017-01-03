<?php
/*
*
* IP Delegation
* Created By Idan Ben-Ezra
*
* Copyrights @ Jetserver Web Hosting
* www.jetserver.net
*
* Hook version 1.0.2
*
**/

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

function hook_ipDelegation_fields($vars)
{
	if($vars['filename'] == 'configproducts')
	{
		$fields = array(
			'id'			=> array('Type' => 'int(11)', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => 'NULL', 'Extra' => 'AUTO_INCREMENT'),
			'pid'			=> array('Type' => 'int(11)', 'Null' => 'NO', 'Key' => '', 'Default' => '0', 'Extra' => ''),
			'ipdelegation'		=> array('Type' => 'int(11)', 'Null' => 'NO', 'Key' => '', 'Default' => '0', 'Extra' => ''),
			'mainip'		=> array('Type' => 'varchar(255)', 'Null' => 'NO', 'Key' => '', 'Default' => 'NULL', 'Extra' => ''),
			'excludereservedips'	=> array('Type' => 'tinyint(1)', 'Null' => 'NO', 'Key' => '', 'Default' => '0', 'Extra' => ''),
			'excludeips'		=> array('Type' => 'text', 'Null' => 'NO', 'Key' => '', 'Default' => 'NULL', 'Extra' => ''),
			'includeips'		=> array('Type' => 'text', 'Null' => 'NO', 'Key' => '', 'Default' => 'NULL', 'Extra' => ''),
			'department'		=> array('Type' => 'int(11)', 'Null' => 'NO', 'Key' => '', 'Default' => '0', 'Extra' => ''),
		);

		$key_fields = array();
		$table_fields = array();

		foreach($fields as $field_name => $field_details)
		{
			$table_fields[] = "`{$field_name}` {$field_details['Type']} " . ($field_details['Null'] == 'NO' ? "NOT " : '') . "NULL" . ($field_details['Default'] != 'NULL' ? " DEFAULT '{$field_details['Default']}'" : '') . ($field_details['Extra'] ? " {$field_details['Extra']}" : '');

			if($field_details['Key'])
			{
				switch($field_details['Key'])
				{
					case 'PRI':
						$key_fields[] = "PRIMARY KEY (`{$field_name}`)";
					break;
				}
			}
		}

		$table_fields = array_merge($table_fields, $key_fields);

		// create table for the first time.
		$sql = "CREATE TABLE IF NOT EXISTS `mod_ipdelegation` (\n" . implode(",\n", $table_fields) . "\n) ENGINE=MyISAM";
		mysql_query($sql);

		$columns = array();

		$sql = "SHOW COLUMNS 
			FROM `mod_ipdelegation`";
		$result = mysql_query($sql);

		while($table_details = mysql_fetch_assoc($result))
		{
			$columns[$table_details['Field']] = $table_details;
		}
		mysql_free_result($result);

		foreach($fields as $field_name => $field_details)
		{
			if(!isset($columns[$field_name]))
			{
				$sql = "ALTER TABLE `mod_ipdelegation`
					ADD `{$field_name}` {$field_details['Type']} " . ($field_details['Null'] == 'NO' ? "NOT " : '') . "NULL" . ($field_details['Default'] != 'NULL' ? " DEFAULT '{$field_details['Default']}'" : '') . ($field_details['Extra'] ? " {$field_details['Extra']}" : '');
				mysql_query($sql);
			}
		}

		foreach($columns as $column_name => $column_details)
		{
			if(!isset($fields[$column_name]))
			{
				$sql = "ALTER TABLE `mod_ipdelegation`
					DROP `{$column_name}`";
				mysql_query($sql);
			}
		}

		$product_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

		$sql = "SELECT *
			FROM tblproducts
			WHERE id = '{$product_id}'";
		$result = mysql_query($sql);
		$product_details = mysql_fetch_assoc($result);

		if($product_details && $product_details['servertype'] == 'cpanel')
		{
			$sql = "SELECT *
				FROM mod_ipdelegation
				WHERE pid = '{$product_id}'";
			$result = mysql_query($sql);
			$delegation_details = mysql_fetch_assoc($result);

			$ipdelegation = isset($delegation_details['ipdelegation']) ? intval($delegation_details['ipdelegation']) : 0;
			$mainip = isset($delegation_details['mainip']) ? $delegation_details['mainip'] : '';
			$excludereservedips = $delegation_details['excludereservedips'] ? 1 : 0;
			$excludeips = isset($delegation_details['excludeips']) ? $delegation_details['excludeips'] : '';
			$includeips = isset($delegation_details['includeips']) ? $delegation_details['includeips'] : '';
			$department = isset($delegation_details['department']) ? intval($delegation_details['department']) : 0;

			$options = array();

			$sql = "SELECT *
				FROM tblticketdepartments";
			$result = mysql_query($sql);

			while($department_details = mysql_fetch_assoc($result))
			{
				$options .= "<option value=\"{$department_details['id']}\"" . ($department_details['id'] == $department ? " selected=\"selected\"" : '') . ">{$department_details['name']}</option>";
			}
			mysql_free_result($result);

			return "<script type='text/javascript'>$(document).ready(function() { var contentBox = $('#tab3');var delegationTable = $('<table />').addClass('form').css({ marginTop: '15px' }).attr({width: '100%',cellspacing: '2',cellpadding: '3',border: '0'});delegationTable.append('<tr><td class=\"fieldlabel\">Max IP Delegation</td><td class=\"fieldarea\"><input type=\"text\" value=\"{$ipdelegation}\" size=\"5\" name=\"ipdelegation\" /></td><td class=\"fieldlabel\">Main IP Usage</td><td class=\"fieldarea\"><select name=\"mainip\"><option value=\"random\"" . ($mainip == 'random' ? " selected=\"selected\"" : '') . ">Random Selection</option><option value=\"force\"" . ($mainip == 'force' ? " selected=\"selected\"" : '') . ">Force Main IP</option><option value=\"exclude\"" . ($mainip == 'exclude' ? " selected=\"selected\"" : '') . ">Exclude Main IP</option></select></td></tr><tr><td class=\"fieldlabel\">Exclude Reserved IPs</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" value=\"1\" " . ($excludereservedips ? "checked=\"checked\" " : '') . "name=\"excludereservedips\" /> Exclude all reserved IPs</label></td><td class=\"fieldlabel\">Department to Open Ticket on Error</td><td class=\"fieldarea\"><select name=\"department\"><option value=\"0\">Don\'t Open Ticket</option>{$options}</select></td></tr><tr><td class=\"fieldlabel\">Exclude IP Addresses</td><td class=\"fieldarea\"><input type=\"text\" value=\"{$excludeips}\" size=\"30\" name=\"excludeips\" /> Comma Seperated List</td><td class=\"fieldlabel\">Force IP Addresses</td><td class=\"fieldarea\"><input type=\"text\" value=\"{$includeips}\" size=\"30\" name=\"includeips\" /> Comma Seperated List</td></tr>');contentBox.children('table:eq(1)').after(delegationTable);});</script>";
		}
	}
}

function hook_ipDelegation_save($vars)
{
	$product_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

	if($vars['servertype'] == 'cpanel')
	{
		$ipdelegation = isset($_REQUEST['ipdelegation']) ? intval($_REQUEST['ipdelegation']) : 0;
		$mainip = isset($_REQUEST['mainip']) ? $_REQUEST['mainip'] : '';
		$excludereservedips = $_REQUEST['excludereservedips'] ? 1 : 0;
		$excludeips = isset($_REQUEST['excludeips']) ? $_REQUEST['excludeips'] : '';
		$includeips = isset($_REQUEST['includeips']) ? $_REQUEST['includeips'] : '';
		$department = isset($_REQUEST['department']) ? intval($_REQUEST['department']) : 0;

		if($ipdelegation)
		{
			$sql = "SELECT *
				FROM mod_ipdelegation
				WHERE pid = '{$product_id}'";
			$result = mysql_query($sql);
			$delegation_details = mysql_fetch_assoc($result);

			if($delegation_details)
			{
				$sql = "UPDATE mod_ipdelegation
					SET 
						ipdelegation = '{$ipdelegation}', 
						mainip = '{$mainip}', 
						excludereservedips = '{$excludereservedips}', 
						excludeips = '{$excludeips}', 
						includeips = '{$includeips}', 
						department = '{$department}'
					WHERE id = '{$delegation_details['id']}'";
				mysql_query($sql);
			}
			else
			{
				$sql = "INSERT INTO mod_ipdelegation (`pid`,`ipdelegation`,`mainip`,`excludereservedips`,`excludeips`,`includeips`,`department`) VALUES
					('{$product_id}','{$ipdelegation}','{$mainip}','{$excludereservedips}','{$excludeips}','{$includeips}','{$department}')";
				mysql_query($sql);
			}
		}
		else
		{
			$sql = "DELETE
				FROM mod_ipdelegation
				WHERE pid = '{$product_id}'";
			mysql_query($sql);
		}
	}
	else
	{
		$sql = "DELETE
			FROM mod_ipdelegation
			WHERE pid = '{$product_id}'";
		mysql_query($sql);
	}
}

function hook_ipDelegation_create($vars)
{
	global $CONFIG;

	$dedicated_ip = '';
	$product_id = $vars['params']['pid'];

	$sql = "SELECT *
		FROM mod_ipdelegation
		WHERE pid = '{$product_id}'";
	$result = mysql_query($sql);
	$delegation_details = mysql_fetch_assoc($result);

	if($delegation_details)
	{
		logModuleCall('ipdelegation', 'settings', '', $delegation_details);

		$ipdelegation = isset($delegation_details['ipdelegation']) ? intval($delegation_details['ipdelegation']) : 0;
		$mainip = isset($delegation_details['mainip']) ? $delegation_details['mainip'] : '';
		$excludereservedips = $delegation_details['excludereservedips'] ? true : false;
		$excludeips = isset($delegation_details['excludeips']) ? explode(',', $delegation_details['excludeips']) : array();
		$includeips = isset($delegation_details['includeips']) ? explode(',', $delegation_details['includeips']) : array();
		$department = isset($delegation_details['department']) ? intval($delegation_details['department']) : 0;

		$sql = "SELECT *
			FROM tblservers
			WHERE id = '{$vars['params']['serverid']}'";
		$result = mysql_query($sql);
		$server_details = mysql_fetch_assoc($result);

		if($server_details && $server_details['username'] && ($server_details['accesshash'] || $server_details['password']))
		{
			if($vars['params']['configoption6'] == 'on')
			{
				$response = hook_ipDelegation_request($server_details, "json-api/listaccts", array(
					'searchtype'	=> 'user',
					'search'	=> $vars['params']['username'],
					'searchmethod'	=> 'exact',
					'want'		=> 'ip',
					'api.version' 	=> 1,
				));

				if($response['success'])
				{
					$response['output'] = json_decode($response['output'], true);
					$dedicated_ip = $response['output']['data']['acct'][0]['ip'];
				}
				else
				{
					if($department)
					{
						// Something not working properly
						localAPI('openticket', array(
							'clientid'	=> 0,
							'name'		=> 'IP Delegation Hook',
							'email'		=> $CONFIG['Email'],
							'deptid' 	=> $department,
							'subject' 	=> "Failed to find account Dedicated IP",
							'message' 	=> "We tried to find the account '{$vars['params']['username']}' Dedicated IP on {$server_details['name']} unseccessfully.\nThe error message we got in the response: {$response['message']}",
							'priority' 	=> 'High',
						));
					}

					return;
				}
			}

			$selected_ips = array();
			$available_ips = array();
			$main_shared_ip = '';

			$response = hook_ipDelegation_request($server_details, "json-api/listips", array('api.version' => 1));

			logModuleCall('ipdelegation', 'listips', "https://{$server_details['hostname']}:2087/json-api/listips?api.version=1", $response, json_decode($response['output'], true));

			if($response['success'])
			{
				$response['output'] = json_decode($response['output'], true);

				foreach($response['output']['data']['ip'] as $ip_details)
				{
					$ip_details['used'] = preg_match("/^192\.168\./", $ip_details['ip']);

					if($ip_details['mainaddr']) $main_shared_ip = $ip_details['ip'];

					if(($mainip == 'force' && $ip_details['mainaddr']) || in_array($ip_details['ip'], $includeips) || ($dedicated_ip && $dedicated_ip == $ip_details['ip']))
					{
						$selected_ips[] = $ip_details['ip'];
						continue;
					}
					elseif(($mainip == 'exclude' && $ip_details['mainaddr']) || in_array($ip_details['ip'], $excludeips) || ($excludereservedips && $ip_details['used']))
					{
						continue;
					}

					$available_ips[] = $ip_details['ip'];
				}
			}

			logModuleCall('ipdelegation', 'selected', '', $selected_ips);
			logModuleCall('ipdelegation', 'available', '', $available_ips);

			if($ipdelegation < count($selected_ips))
			{
				$reduce = (count($selected_ips) - $ipdelegation);

				for($i = $ipdelegation; $i < count($selected_ips); $i++)
				{
					unset($selected_ips[$i]);
				}
			}
			elseif($ipdelegation > count($selected_ips))
			{
				$ip_keys = hook_ipDelegation_uniqueRandom(0, (count($available_ips)-1), ($ipdelegation-count($selected_ips)));

				foreach($ip_keys as $ip_key)
				{
					$selected_ips[] = $available_ips[$ip_key];
				}
			}

			if(sizeof($selected_ips) && sizeof($selected_ips) == $ipdelegation)
			{
				$response = hook_ipDelegation_request($server_details, "json-api/setresellerips", array(
					'user'		=> $vars['params']['username'],
					'ips'		=> implode(",", $selected_ips),
					'delegate'	=> true,
					'api.version' 	=> 1,
				));

				logModuleCall('ipdelegation', 'setresellerips', "https://{$server_details['hostname']}:2087/json-api/setresellerips?user={$vars['params']['username']}&ips=" . implode(",", $selected_ips) . "delegate=1&api.version=1", $response);

				if($response['success'])
				{
					if($dedicated_ip || !in_array($main_shared_ip, $selected_ips))
					{
						$mainip = $dedicated_ip ? $dedicated_ip : $selected_ips[0];

						if($mainip)
						{
							$response = hook_ipDelegation_request($server_details, "json-api/setresellermainip", array(
								'user'		=> $vars['params']['username'],
								'ip'		=> $mainip,
								'api.version' 	=> 1,
							));

							if(!$response['success'])
							{
								// Something not working properly
								localAPI('openticket', array(
									'clientid'	=> 0,
									'name'		=> 'IP Delegation Hook',
									'email'		=> $CONFIG['Email'],
			 						'deptid' 	=> $department,
									'subject' 	=> "Failed to set Reseller main shared IP",
									'message' 	=> "We tried to change the Reseller main shared IP to '{$mainip}' for the account '{$vars['params']['username']}' on {$server_details['name']} unseccessfully.\nThe error message we got in the response: {$response['message']}",
									'priority' 	=> 'High',
								));
							}
						}
					}


					logActivity("IP Delegation - The IPs " . implode(",", $selected_ips) . " added to the account {$vars['params']['username']} - Service ID: {$vars['params']['serviceid']}");
				}
				elseif($department)
				{
					// Something not working properly
					localAPI('openticket', array(
						'clientid'	=> 0,
						'name'		=> 'IP Delegation Hook',
						'email'		=> $CONFIG['Email'],
 						'deptid' 	=> $department,
						'subject' 	=> "Failed to change IP Delegation",
						'message' 	=> "We tried to change the IP Delegation settings for the account '{$vars['params']['username']}' on {$server_details['name']} unseccessfully.\nThe error message we got in the response: {$response['message']}",
						'priority' 	=> 'High',
					));
				}
			}
			elseif($department)
			{
				// No selected IPs or not the required IP amount

				localAPI('openticket', array(
					'clientid'	=> 0,
					'name'		=> 'IP Delegation Hook',
					'email'		=> $CONFIG['Email'],
 					'deptid' 	=> $department,
					'subject' 	=> "Failed to change IP Delegation",
					'message' 	=> "We tried to change the IP Delegation settings for the account '{$vars['params']['username']}' on {$server_details['name']} unseccessfully.\n\nIPs Requested: {$ipdelegation}\nTotal Selected IPs: " . count($selected_ips) . " - " . implode(", " , $selected_ips),
					'priority' 	=> 'High',
				));
			}
		}
		elseif($department)
		{
			localAPI('openticket', array(
				'clientid'	=> 0,
				'name'		=> 'IP Delegation Hook',
				'email'		=> $CONFIG['Email'],
 				'deptid' 	=> $department,
				'subject' 	=> "Failed to change IP Delegation",
				'message' 	=> "We tried to change the IP Delegation settings for the account '{$vars['params']['username']}' unseccessfully.\n" . ($server_details ? "We unable to find the server #{$vars['params']['serverid']} in the system database" : (!$server_details['username'] ? "Username and " : '') . "Password or Accesshash is missing for the server {$server_details['name']}.\nUnable to connect to the server without those details."),
				'priority' 	=> 'High',
			));
		}
	}
}

function hook_ipDelegation_uniqueRandom($min, $max, $quantity) 
{
	$numbers = range($min, $max);
	shuffle($numbers);
	return array_slice($numbers, 0, $quantity);
}

function hook_ipDelegation_request($server_details, $url, $params = '')
{
	$output = array('success' => true, 'message' => '', 'output' => '');

	if($server_details['accesshash'])
	{
		$authorization = "Authorization: WHM {$server_details['username']}:" . preg_replace("'(\r|\n)'", "", $server_details['accesshash']);
	}
	else
	{
		$authorization = "Authorization: Basic " . base64_encode("{$server_details['username']}:{$server_details['password']}");
	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://{$server_details['hostname']}:2087/{$url}");
	if($params) curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization));

	$output['output'] = curl_exec($ch);

	curl_close($ch);

	return $output;
}

add_hook('AdminAreaHeadOutput', 1, 'hook_ipDelegation_fields');
add_hook('ProductEdit', 1, 'hook_ipDelegation_save');
add_hook('AfterModuleCreate', 1, 'hook_ipDelegation_create');

?>
