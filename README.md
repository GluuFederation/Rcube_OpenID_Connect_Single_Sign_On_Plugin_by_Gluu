# <a id="RoundCube_GLUU_SSO_plugin_0"></a>RoundCube OpenID Connect Single Sign-On (SSO) Plugin by Gluu

![image](https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/plugin.jpg)

RoundCube OpenID Connect Single Sign-On (SSO) Plugin by Gluu gives access for login to your RoundCube site, with the help of GLUU server.

Plugin will not be working if your host does not have https://.

## <a id="Step_1_Install_Gluuserver_13"></a>Step 1\. Install Gluu-server

(version 2.4.2)

If you want to use external gluu server, You can not do this step.

[Gluu-server installation gide](https://www.gluu.org/docs/deployment/).

## <a id="Step_2_Download_oxDserver_21"></a>Step 2\. Download oxd-server

[Download oxd-server-2.4.2.Final](https://ox.gluu.org/maven/org/xdi/oxd-server/2.4.2.Final/oxd-server-2.4.2.Final-distribution.zip).

## <a id="Step_3_Unzip_and_run_oXDserver_31"></a>Step 3\. Unzip and run oxd-server

1.  Unzip your oxd-server.
2.  Open the command line and navigate to the extracted folder in the conf directory.
3.  Open oxd-conf.json file.
4.  If your server is using 8099 port, please change “port” number to free port, which is not used.
5.  Set parameter “op_host”:“Your gluu-server-url (internal or external)”
6.  Open the command line and navigate to the extracted folder in the bin directory.
7.  For Linux environment, run sh [oxd-start.sh](http://oxd-start.sh)&.
8.  For Windows environment, run oxd-start.bat.
9.  After the server starts, go to Step 4.

## <a id="Step_6_General_73"></a>Step 4\. General

![General](https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/6.png)

1.  Admin Email: please add your or admin email address for registrating site in Gluu server.
2.  Port number: choose that port which is using oxd-server (see in oxd-server/conf/oxd-conf.json file).
3.  Click `Next` to continue.

If You are successfully registered in gluu server, you will see bottom page.

![oxD_id](https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/7.png)

For making sure go to your gluu server / OpenID Connect / Clients and search for your oxd ID

If you want to reset configurations click on Reset configurations button.

## <a id="Step_8_OpenID_Connect_Configuration_89"></a>Step 5\. OpenID Connect Configuration

### <a id="Scopes_93"></a>Scopes.

You can look all scopes in your gluu server / OpenID Connect / Scopes and understand the meaning of every scope. Scopes are need for getting loged in users information from gluu server. Pay attention to that, which scopes you are using that are switched on in your gluu server.

In RoundCube-gluu-sso 2.4.3 you can enable, disable and delete scope. ![Scopes1](https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/8.png)

### <a id="Custom_scripts_104"></a>Custom scripts.

![Customscripts](https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/10.png)

You can look all custom scripts in your gluu server / Configuration / Manage Custom Scripts / and enable login type, which type you want. Custom Script represent itself the type of login, at this moment gluu server supports (U2F, Duo, Google +, Basic) types.

### <a id="Pay_attention_to_that_111"></a>Pay attention to that.

1.  Which custom script you enable in your RoundCube site in order it must be switched on in gluu server too.
2.  Which custom script you will be enable in OpenID Connect Configuration page, after saving that will be showed in RoundCube Configuration page too.
3.  When you create new custom script, both fields are required.

## <a id="Step_9_RoundCube_Configuration_117"></a>Step 6\. RoundCube Configuration

### <a id="Customize_Login_Icons_119"></a>Customize Login Icons

Pay attention to that, if custom scripts are not enabled, nothing will be showed. Customize shape, space between icons and size of the login icons.

![RoundCubeConfiguration](https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/11.png)

## <a id="Step_10_Show_icons_in_frontend_126"></a>Step 7\. Show icons in frontend

![frontend](https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/12.png)



