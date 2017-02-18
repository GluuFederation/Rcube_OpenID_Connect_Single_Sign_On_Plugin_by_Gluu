# Roundcube OpenID Connect Single Sign-On (SSO) Plugin By Gluu

![image](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/master/roundcube.png)

Gluu's OpenID Connect Single Sign-On (SSO) Roundcube plugin will enable you to authenticate users against any standard OpenID Connect Provider (OP). If you don't already have an OP you can [deploy the free open source Gluu Server](https://gluu.org/docs/deployment).  

## Requirements
In order to use the Roundcube plugin you will need to have a standard OP (like Google or a Gluu Server) and the oxd server.

* [Gluu Server Installation Guide](https://www.gluu.org/docs/deployment/).

* [oxd Server Installation Guide](https://oxd.gluu.org/docs/oxdserver/install/)

 
## Installation
 
### Download

[Github source](https://github.com/GluuFederation/roundcube_oxd_plugin/archive/v3.0.0.zip).

[Link to RoundCube repository](https://plugins.roundcube.net/packages/gluufederation/roundcube_oxd_plugin)

To install RoundCube OpenID Connect Single Sign On (SSO) Plugin By Gluu via Composer, execute the following command 

```
$ composer install `composer require "gluufederation/roundcube_oxd_plugin": "3.0.0"`

```


## Configuration

### General
 
In your Roundcube admin menu panel you should now see the OpenID Connect menu tab. Click the link to navigate to the General configuration  page:

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/master/docu/1.png) 

1. Automatically login any user with an account in the OpenID Provider: By setting login to automatic, any user with an account in the OP will be able to dynamically login for an account in your Roundcube site. 
2. Only register and allow ongoing access to users with one or more of the following roles in the OP: Using this option you can limit login to users who have a specified role in the OP, for instance `roundcube`. This is not configurable in all OP's. It is configurable if you are using a Gluu Server. [Follow the instructions below](#role-based-enrollment) to limit access based on an OP role. 
3. URI of the OpenID Provider: insert the URI of the OpenID Connect Provider.
4. Custom URI after logout: custom URI after logout (for example "Thank you" page).
5. oxd port: enter the oxd-server port (you can find this in the `oxd-server/conf/oxd-conf.json` file).
6. Click `Register` to continue.

If your OpenID Provider supports dynamic registration, no additional steps are required in the general tab and you can navigate to the [OpenID Connect Configuration](#openid-connect-configuration) tab. 

If your OpenID Connect Provider doesn't support dynamic registration, you will need to insert your OpenID Provider `client_id` and `client_secret` on the following page.

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/master/docu/2.png)  

To generate your `client_id` and `client_secret` use the redirect uri: `https://{site-base-url}/index.php?option=oxdOpenId`.

> If you are using a Gluu server as your OpenID Provider, you can make sure everything is configured properly by logging into to your Gluu Server, navigate to the OpenID Connect > Clients page. Search for your `oxd id`.

#### Enrollment and Access Management

Navigate to your Gluu Server admin GUI. Click the `Users` tab in the left hand navigation menu. Select `Manage People`. Find the person(s) who should have access. Click their user entry. Add the `User Permission` attribute to the person and specify the same value as in the plugin. For instance, if in the plugin you have limit enrollment to user(s) with role = `roundcube`, then you should also have `User Permission` = `roundcube` in the user entry. Update the user record, and now they are ready for enrollment at your Roundcube site. 

### OpenID Connect Configuration

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/master/docu/3.png) 

#### User Scopes

Scopes are groups of user attributes that are sent from the OP to the application during login and enrollment. By default, the requested scopes are `profile`, `imapData`, `email`, and `openid`.  

To view your OP's available scopes, in a web browser navigate to `https://OpenID-Provider/.well-known/openid-configuration`.  

If you are using a Gluu server as your OpenID Provider, you can view all available scopes by navigating to the OpenID Connect > Scopes interface. 

In the plugin interface you can enable, disable and delete scopes. 

> If you have chosen to limit enrollment to users with specific roles in the OP, you will also need to request the `Permission` scope, as shown in the above screenshot. 

### Attention

For doing login to your Roundcube site, it is very important that your OP supports `imapData` scope, which contains your imap connection climes (`imapHost`,`imapPort`,`imapUsername`,`imapPassword`).
This is not configurable in all OP's. It is configurable if you are using a Gluu Server.
For example : `imapHost` = `ssl://imap.gmail.com` ; `imapPort` = `993` ; `imapUsername` = `username@gmail.com` ; `imapPassword` = `password` ; 


#### Authentication

##### Bypass the local Roundcube login page and send users straight to the OP for authentication

Check this box so that when users attempt to login they are sent straight to the OP, bypassing the local Roundcube login screen.
When it is not checked, it will give proof the following screen.   

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/master/docu/4.png) 

##### Select acr

To signal which type of authentication should be used, an OpenID Connect client may request a specific authentication context class reference value (a.k.a. "acr"). The authentication options available will depend on which types of mechanisms the OP has been configured to support. The Gluu Server supports the following authentication mechanisms out-of-the-box: username/password (basic), Duo Security, Super Gluu, and U2F tokens, like Yubikey.  

Navigate to your OpenID Provider configuration webpage `https://OpenID-Provider/.well-known/openid-configuration` to see supported `acr_values`. In the `Select acr` section of the plugin page, choose the mechanism which you want for authentication. 

Note: If the `Select acr` value is `none`, users will be sent to pass the OP's default authentication mechanism.



