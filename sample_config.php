<?php
$config = array(
	'api_email' => '',	// Cloudflare API email
	'api_token' => '',	// Cloudflare API token
	'api_domain' => '',	// Domain on Cloudflare being managed
	'record_name' => '',	// Record on Cloudflare DNS to update
	'cname' => '',		// Dynamic CNAME record to match. eg. xxx.us-east-1.elb.amazonaws.com
	'verbose' => 1,		// Whether to output verbose details
	'simulate' => 0		// Simulate, no changes to Cloudflare brought
);
