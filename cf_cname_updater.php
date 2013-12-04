<?php
/**
 * Cloudflare CNAME flattener/updater
 * PHP port of https://github.com/bundan/CloudFlare-CNAME-Flattener
 * Supports A/AAAA records
 *
 * @author Jawish Hameed <jawish@gmail.com>
 * @version 1.0
 */

if (!isset($argv[1])) {
	die("Please specify a config filename\r\n");
}

$configFile = $argv[1] . '.php';
if (!file_exists($configFile)) {
	die('Specified config file not found or inaccessible: ' . $configFile . "\r\n");
}

require $configFile;


/**
 * Call Cloudflare API with given action and data
 *
 * @param $action 	string 	API action
 * @param $data 	array 	Fields required by the action
 * @return 			array 	API results
 */
function callApi($action, $data = array()) {
	global $config;

	$postData = array_merge(
		array(
			'tkn' => $config['api_token'], 
			'email' => $config['api_email'],
			'z' => $config['api_domain'],
			'a' => $action
		),
		$data
	);
	
	$ch = curl_init();

	curl_setopt_array($ch, array(
	    CURLOPT_URL => 'https://www.cloudflare.com/api_json.html',
	    CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_POST => true,
	    CURLOPT_POSTFIELDS => $postData,
	    CURLOPT_FOLLOWLOCATION => true
	));
	 
	$output = curl_exec($ch);
	curl_close($ch);

	return json_decode($output, true);
}

/**
 * Get the IPs associated with the CNAME record
 */
function getNewIps($cname) {
	$ips = array();

	// Get IPv4 records
	$records = dns_get_record($cname, DNS_A);
	foreach ($records as $r) {
		$ips[$r['ip']] = $r['type'];
	}

	// Get IPv6 records
	$records = dns_get_record($cname, DNS_AAAA);
	foreach ($records as $r) {
		$ips[$r['ipv6']] = $r['type'];
	}

	return $ips;
}

/**
 * Get the current list of IPs for the given record listed on Cloudflare DNS
 */
function getCurrentIps($recordName) {
	$ips = array();

	// Get current IPs on Cloudflare
	$result = callApi('rec_load_all');

	if ($result['result'] == 'error') {
		die("An error occured while fetching the current records from Cloudflare API. ({$result['msg']})\r\n");
	}

	foreach ($result['response']['recs']['objs'] as $record) {
		if ($record['name'] == $recordName) {
			if ($record['type'] == 'A' || $record['type'] == 'AAAA') {
				$ips[$record['content']] = $record['rec_id'];
			}
		}
	}

	return $ips;
}

/**
 * Add a record to Cloudflare DNS
 */
function addRecord($type, $name, $ip) {
	$result = callApi('rec_new', array(
		'type' => $type,
		'name' => $name,
		'content' => $ip,
		'ttl' => 300,
		'service_mode' => 0
	));

	return $result['result'] == 'success';
}


/**
 * Delete a record from Cloudflare DNS
 */
function deleteRecord($rec_id) {
	$result = callApi('rec_delete', array(
		'id' => $rec_id
	));

	return $result['result'] == 'success';
}


/**
 * Update DNS
 */
$currentIps = getCurrentIps($config['record_name']);
$newIps = getNewIps($config['cname']);

if ($config['simulate']) {
	echo "SIMULATION MODE ON! No changes to Cloudflare DNS will be made.\r\n";
}

if ($config['verbose']) {
	echo 'Existing IPs: ' . join(', ', array_keys($currentIps)) . "\r\n";
	echo 'New IPs: ' . join(', ', array_keys($newIps)) . "\r\n";
}

// Add new IPs
foreach ($newIps as $ip => $type) {
	if (!array_key_exists($ip, $currentIps)) {
		if ($config['simulate']) {
			addRecord($type, $config['record_name'], $ip);
		}

		if ($config['verbose']) {
			echo "Adding new IP $ip\r\n";
		}
	}
	else {
		unset($currentIps[$ip]);
	}
}

// Delete expired/invalidated IPs
foreach ($currentIps as $ip => $rec_id) {
	if ($config['simulate']) {
		deleteRecord($rec_id);
	}

	if ($config['verbose']) {
		echo "Deleting invalidated IP $ip\r\n";
	}
}
