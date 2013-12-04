Resolves the given CNAME into the associated A/AAAA records and syncs Cloudflare DNS with the records via Cloudflare API.

Used when managing DNS in Cloudflare with a domain that has dynamic CNAMEs that change occassionally. This was originally written for use on a domain with web/app servers running behind Amazon AWS Elastic Load Balancers.


Instructions
============
1. Clone/download the script and sample config script somewhere onto a server. The server should be using a nameserver that has the most up-to-date records.
2. Copy the sample config file and fill in the details.
3. Run the script manually or setup a cron job to run the script every x minutes.

php cf_cname_updater.php sample_config


That's all!

License
=======
MIT License
