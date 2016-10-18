# WHMCS-Auto-IP-Delegation

*** Only for cPanel servers ***

This hook allows you to control a reseller’s access to the server’s IP addresses.

By default, cPanel grants a reseller access to all of the available IP addresses on the server, with this hook you will be able to assign only specific ips to a product.

Very usefull for SEO Hosting !

* Pure WHMCS API (no need for changes on server side)
* Option to set Max IP Delegation (very useful for SEO hosting)
* Option to exclude reserved IPs
* Ability to open a support tickets if any errors (for support staff review)
* Option to always force main shared ip
* Option to exclude IP Addresses from being assigned

# Installation

Upload the hook file to your WHMCS hooks folder (“includes/hooks“).

Next, login to your WHMCS system as admin user, and edit the product (reseller hosting probebly) you want to activate this hook on.

Setup -> Product Services -> Products Services -> (Edit the desired product) -> Module Settings

A configuration box will be added to the product’s module settings tab.

Once a product is ordered and the “create” action was triggered, the hook will make the necessary changes.

# More Information

https://docs.jetapps.com/category/whmcs-addons/whmcs-auto-ip-delegation
