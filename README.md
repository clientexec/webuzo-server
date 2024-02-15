[![Webuzo Plugin](/logo.png)](https://webuzo.com)

# Description
Webuzo plugin lets you to create and manage your hosting accounts from Clientexec

# Features

* Create, Suspend, Unsuspend and Delete accounts
* Shared/Dedicated IP support
* Plans support
* Reseller Accounts support
* Login to Webuzo Enduser/Reseller panel from Clientexec Admin/Client portal
* Change Password from Clientexec Admin/Client portal
* Change IP and primary domain name from Clientexec Admin portal

# Installation
If the plugin is not already available in your Clientexec installation just download and unzip the package in your Clientexec installation at the following path :
```clientexec/plugins/server/webuzo/```


## Steps

1. In Clientexec Admin portal go to Settings -> Products -> Servers and add a Webuzo server
2. Enter your server hostname, ip address, nameservers, etc
3. In the Plugin dropdown choose Webuzo
4. Generate an API Key from your Webuzo admin panel -> Settings -> API Keys page. Don't forget to allow **All Acts**
5. In Clientexec edit server fill in your Webuzo admin username and API Key you just created
6. Save the page and then make a Test Connection
7. Create a product in Clientexec and choose the server and package name in Advanced & Plugin Settings tab. Please use the **slug of the plan name** you create in Webuzo
8. That's it! You have successfully configured the Webuzo plugin


[1]: https://webuzo.com/contact/
# Support
If you need any assistance feel free to reach out to our [support team][1]