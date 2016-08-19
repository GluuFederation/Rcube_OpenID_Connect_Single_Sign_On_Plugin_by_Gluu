[TOC]

# RoundCube OpenID Connect Single Sign On (SSO) Plugin By Gluu

![image](https://raw.githubusercontent.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/master/plugin.jpg)

Gluu's RoundCube OpenID Connect Single Sign On (SSO) Plugin will enable you to authenticate users against any standard OpenID Connect Provider (OP). If you don't already have an OP you can [deploy the free open source Gluu Server](https://gluu.org/docs/deployment).  

## Requirements
In order to use the RoundCube plugin, you will need to have deployed a standard OP like the Gluu Server and the oxd Server.

* [Gluu Server Installation Guide](https://www.gluu.org/docs/deployment/).

* [oxd Server Installation Guide](https://oxd.gluu.org/docs/oxdserver/install/)


## Installation
 
### Step 1: Download

[Github source](https://github.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/archive/v2.4.4.zip).

[Link to RoundCube repository](https://plugins.roundcube.net/packages/gluufederation/rcube_openid_connect_single_sign_on_plugin_by_gluu)

To install RoundCube OpenID Connect Single Sign On (SSO) Plugin By Gluu via Composer, execute the following command 

```
$ composer install `composer require "gluufederation/rcube_openid_connect_single_sign_on_plugin_by_gluu": "2.4.4"`

```

### Step 2: General
![General](https://raw.githubusercontent.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/master/docu/6.png)  

1. Admin Email: please add your or admin email address for registrating site in Gluu server.
2. Gluu Server URL: please insert your Gluu server URL.
3. Port number: choose that port which is using oxd-server (see in oxd-server/conf/oxd-conf.json file).
4. Click next to continue.

If You are successfully registered in gluu server, you will see bottom page.

![Oxd_id](https://raw.githubusercontent.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/master/docu/7.png)

To make sure everything is configured properly, login to your Gluu Server and navigate to the OpenID Connect > Clients page. Search for your `oxd id`.


### Step 3: OpenID Connect Provider (OP) Configuration

#### Scopes
Scopes are groups of user attributes that are sent from your OP (in this case, the Gluu Server) to the application during login and enrollment. You can view all available scopes in your Gluu Server by navigating to the OpenID Connect > Scopes intefrace. 

In the Plugin interface you can enable, disable and delete scopes. You can also add new scopes. If/when you add new scopes via the plugin, be sure to also add the same scopes in your gluu server. 
![Scopes2](https://raw.githubusercontent.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/master/docu/9.png) 

#### Authentication
To specify the desired authentication mechanism navigate to the Configuration > Manage Custom Scripts menu in your Gluu Server. From there you can enable one of the out-of-the-box authentication mechanisms, such as password, U2F device (like yubikey), or mobile authentication. You can learn more about the Gluu Server authentication capabilities in the [docs](https://gluu.org/docs/multi-factor/intro/).

![Customscripts](https://raw.githubusercontent.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/master/docu/10.png) 

Note:    
- The authentication mechanism specified in your RoundCube plugin page must match the authentication mechanism specified in your Gluu Server.     
- After saving the authentication mechanism in your Gluu Server, it will be displayed in the RoundCube plugin configuration page too.      
- If / when you create a new custom script, both fields are required. 

### Step 4:RoundCube Configuration

#### Customize Login Icons
 
If custom scripts are not enabled, nothing will be showed. Customize shape, space between icons and size of the login icons.

![RoundCubeConfiguration](https://raw.githubusercontent.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/master/docu/11.png)  

### Step 5:Show icons in frontend

Once you've configured all the options, you should see your supported authentication mechanisms on your default RoundCube login page like the screenshot below.

![frontend](https://raw.githubusercontent.com/GluuFederation/rcube_openid_connect_single_sign_on_plugin_by_gluu/master/docu/12.png) 
