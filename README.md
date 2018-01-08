# RoundCube OpenID Connect Single Sign-On (SSO) Plugin By Gluu

![image](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/master/roundcube.png)

Gluu's OpenID Connect Single Sign-On (SSO) Roundcube plugin will enable you to 
authenticate users against any standard OpenID Connect Provider. If you don't already have an OpenID Connect provider you can 
[deploy the free open source Gluu Server](https://gluu.org/docs/ce/3.1.1/installation-guide/install/).  

## Requirements
In order to use the RoundCube plugin you will need to have a standard OP (like Google or a Gluu Server) and the oxd server.

* Compatibility : 0.6.0 <= 1.3.3 versions

* [Gluu Server Installation Guide](https://gluu.org/docs/ce/3.1.1/installation-guide/install/).

* [oxd Server Installation Guide](https://oxd.gluu.org/docs/install/)

!!!Note: 
    Here standard OpenID Connect provider means the Gluu Server,because Google OpenID Connect provider is not supported in `RoundCube` plugin.

## Installation
 
### Download

[Github source](https://github.com/GluuFederation/roundcube_oxd_plugin/archive/v3.1.1.zip).

[Link to RoundCube repository](https://plugins.roundcube.net/packages/gluufederation/roundcube_oxd_plugin)

To install `RoundCube` OpenID Connect Single Sign On (SSO) Plugin By Gluu via Composer, execute the following command 

```
$ composer require "gluufederation/roundcube_oxd_plugin": "3.1.1"

```

## Gluu Server customization for RoundCube plugin

#### IMAP Scopes

For doing login to your RoundCube site, it is very important that your OpenID Connect provider supports `imapData` scope, which contains your imap connection claims (`imapHost`,`imapPort`,`imapUsername`,`imapPassword`).
This is not configurable in all OpenID Connect provider's. It is configurable if you are using a Gluu Server.
For example : `imapHost` = `ssl://imap.email.com` ; `imapPort` = `993` ; `imapUsername` = `username@email.com` ; `imapPassword` = `password` ; 

All these scopes are to be added with the help of this following link `https://gluu.org/docs/ce/admin-guide/attribute/`

#### Navigation steps

Navigate to your Gluu Server admin GUI. Click the `OpenID Connect` tab in the left hand navigation menu. Select `Scopes`. Find `imapData` and click on it. Now click `Add Claim`, search for `IMAP`, select the required claims and then click `OK`.

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/v3.1.1/docu/gluusrv1.png) 

Once you add the required claim, then you can add the claims on any user.

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/v3.1.1/docu/gluusrv2.png)


## Configuration

### General
 
For the first time configuration, after logging in as `admin`(for example : `admin@email.org`), you should now see your RoundCube admin menu panel and the OpenID Connect menu tab. Click the link to navigate to the General configuration page:

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/v3.1.1/docu/rcopidlnk.png) 

A short description of each field follows:

1. URI of the OpenID Provider: insert the URI of the OpenID Connect Provider, for example : `https://idp.example.com`. 

2. Custom URI after logout: Provide a URL for a landing page to redirect users after logout of the RoundCube site, for instance `https://example.com/thank-you`. If you don't have a preferred logout page we recommend simply entering your website homepage URL. If you leave this field blank the user will see the default logout page presented by RoundCube.

3. oxd port: enter the oxd-server port (you can find this in the `oxd-server/conf/oxd-conf.json` file).

4. Click `Register` to continue.

If your OpenID Provider supports dynamic registration, no additional steps are required in the general tab and you can navigate to the [OpenID Connect Configuration](#openid-connect-configuration) tab. 

If your OpenID Connect Provider doesn't support dynamic registration, you will need to insert your OpenID Provider `client_id` and `client_secret` on the following page.

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/v3.1.1/docu/rcregimg.png)  

To generate your `client_id` and `client_secret` use the redirect uri: `https://{site-base-url}/index.php?option=oxdOpenId`.

!!! Note:
    Once the OpenID Connect provider is registered and if you want to check the role of the OpenID Connect provider for confirmation,
    navigate to `Users` from RoundCube-admin portal of your Roundcube site and Check the OpenID Connect provider roles, 
    listed under the user Roles.

!!!Note: 
    If you are using a Gluu server as your OpenID Provider, you can make sure everything is configured properly by logging into to your Gluu Server, navigate to the OpenID Connect > Clients page. Search for your `oxd id`.

#### User Scopes

Navigate to your Gluu Server admin GUI. Click the `Users` tab in the left hand navigation menu. Select `Manage People`. Find the person(s) who should have access. Click their user entry. Add the `User Permission` attribute to the person and specify the same value as in the plugin. For instance, if in the plugin you have limit enrollment to user(s) with role = `roundcube`, then you should also have `User Permission` = `roundcube` in the user entry. Update the user record, and now they are ready for enrollment at your Roundcube site. 


### OpenID Connect Configuration

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/v3.1.1/docu/rcscopdtl.png) 

#### Enrollment and Access Management

Scopes are groups of user attributes that are sent from the OpenID Connect provider to the application during login and enrollment. By default, the requested scopes are `profile`, `imapData`, `email`, and `openid`.  

To view your OpenID Connect provider's available scopes, in a web browser navigate to `https://OpenID-Provider/.well-known/openid-configuration`.  

If you are using a Gluu server as your OpenID Provider, 
you can view all available scopes by navigating to the Scopes interface in Gluu CE Server Admin UI

`OpenID Connect` > `Scopes`  

In the plugin interface you can enable, disable and delete scopes. 

!!!Note: 
    If you have chosen to limit enrollment to users with specific roles in the OP, you will also need to request the `Permission` scope, as shown in the above screenshot. 

### Understanding user permissions

To understand user permission more precisely, lets take a quick look at the image below:

![image](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/v3.1.1/docu/RoundCube2.png)

!!!Note: 
    Here Server permission means the Gluu Server and the client settings permission means the permission which is defined at the time of `oxd` client registration.


#### Authentication

##### Bypass the local RoundCube login page and send users straight to the OpenID Connect provider for authentication

Check this box so that when users attempt to login they are sent straight to the OpenID Connect provider, bypassing the local RoundCube login screen.
When it is not checked, it will give proof the following screen.   

![upload](https://raw.githubusercontent.com/GluuFederation/roundcube_oxd_plugin/v3.1.1/docu/4.png) 

##### Select acr

To signal which type of authentication should be used, an OpenID Connect client may request a specific authentication context class reference value (a.k.a. "acr"). The authentication options available will depend on which types of mechanisms the OpenID Connect provider has been configured to support. The Gluu Server supports the following authentication mechanisms out-of-the-box: username/password (basic), Duo Security, Super Gluu, and U2F tokens, like Yubikey.  

Navigate to your OpenID Provider configuration webpage `https://OpenID-Provider/.well-known/openid-configuration` to see supported `acr_values`. In the `Select acr` section of the plugin page, choose the mechanism which you want for authentication. 

Note: If the `Select acr` value is `none`, users will be sent to pass the OpenID Connect provider's default authentication mechanism.


## Support
Please report technical issues and suspected bugs on our [support page](https://support.gluu.org). If you do not already have an account on Gluu Support, you can login and create an account using the same credentials you created when you registered for your oxd license.