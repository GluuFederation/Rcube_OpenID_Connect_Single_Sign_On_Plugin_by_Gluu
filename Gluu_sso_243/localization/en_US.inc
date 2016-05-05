<?php

$labels = array();
$labels['gluu_sso'] = 'Gluu SSO 2.4.3';
$labels['General'] = 'General';
$labels['OpenIDConnect'] = 'OpenID Connect Configuration';
$labels['rConfig'] = 'RoundCube Configuration';
$labels['helpTrouble'] = 'Help & Troubleshooting';
$labels['messageConnectProvider'] = 'Please enter the details of your OpenID Connect Provider.';
$labels['messageSiteRegisteredSuccessful'] = 'Site registered Successful. You can configure Gluu and Social Login now.';
$labels['messageScopeDeletedSuccessful'] = 'Scope deleted Successfully.';
$labels['messageConfigurationsDeletedSuccessful'] = 'Configurations deleted Successfully.';
$labels['messageScriptDeletedSuccessful'] = 'Custom script deleted Successfully.';
$labels['messageYourConfiguration'] = 'Your configuration has been saved.';
$labels['messageOpenIDConnectConfiguration'] = 'Your OpenID connect configuration has been saved.';
$labels['messageSorryUploading'] = 'Sorry, there was an error uploading ';
$labels['messageSwitchedOn'] = 'Sorry, but oxd server is not switched on!';
$labels['problemImapConnection'] = 'Problem with imap connection, please look your imapData in your gluu server scopes.';
$labels['necessaryToFill'] = 'Necessary to fill the hole row.';
$labels['file'] = 'file';
$labels['registerMessageConnectProvider'] = 'Register your site with an OpenID Connect Provider';

$labels['linkToGluu'] = 'If you do not have an OpenID Connect provider, you may want to look at the Gluu Server (
                                    <a target="_blank" href="http://www.gluu.org/docs">Like RoundCube, there is a free open source Community Edition. For more information about Gluu Server support please visit <a target="_blank" href="http://www.gluu.org">our website.</a></a>)';

$labels['Instructions'] = '<h3>Instructions to Install oxd server</h3>
                                    <br><b>NOTE:</b> The oxd server should be installed on the same server as your RoundCube site. It is recommended that the oxd server listen only on the localhost interface, so only your local applications can reach its API"s.
                                    <ol style="list-style:decimal !important; margin: 30px">
                                        <li>Extract and copy in your DMZ Server.</li>
                                        <li>Download the latest oxd-server package for Centos or Ubuntu. See
                                            <a target="_blank" href="http://gluu.org/docs-oxd">oxd docs</a> for more info.
                                        </li><li>If you are installing an .rpm or .deb, make sure you have Java in your server.
                                        </li><li>Edit <b>oxd-conf.json</b> in the <b>conf</b> directory to specify the port on which
                                            it will run, and specify the hostname of the OpenID Connect provider.</li>
                                        <li>Open the command line and navigate to the extracted folder in the <b>bin</b> directory.</li>
                                        <li>For Linux environment, run <b>sh oxd-start.sh &amp;</b></li>
                                        <li>For Windows environment, run <b>oxd-start.bat</b></li>
                                        <li>After the server starts, set the port number and your email in this page and click Next.</li>
                                    </ol>';
$labels['adminEmail'] = 'Admin Email:';
$labels['hederGluu'] = 'Use OpenID Connect to login by leveraging the oxd client service demon.';
$labels['portNumber'] = 'Port number:';
$labels['Addacr'] = 'Add acr';
$labels['Save'] = 'Save';
$labels['EnterportNumber'] = 'Enter port number.';
$labels['InputScopeName'] = 'Input scope name';
$labels['exampleGoogle'] = 'Display name (example Google+)';
$labels['scriptName'] = 'ACR Value (script name in the Gluu Server)';
$labels['next'] = 'Next';
$labels['resetConfig'] = 'Reset configurations';
$labels['allScopes'] = 'All Scopes';
$labels['name'] = 'Name';
$labels['or'] = 'or';
$labels['isExist'] = 'is exist.';
$labels['delete'] = 'Delete';
$labels['addScopes'] = 'Add scopes';
$labels['DisplayName'] = 'Display Name';
$labels['ACRvalue'] = 'ACR Value';
$labels['Image'] = 'Image';
$labels['multipleCustomScripts'] = 'Add multiple custom scripts';
$labels['allCustomScripts'] = 'All custom scripts';
$labels['BothFields'] = 'Both fields are required';
$labels['OXDConfiguration'] = 'Please enter OXD configuration to continue.';
$labels['GluuLoginConfig'] = 'Gluu login config ';
$labels['Theme '] = 'Theme ';
$labels['Round'] = 'Round ';
$labels['NoApps'] = 'No apps selected ';
$labels['LongButton '] = 'Long Button with Text ';
$labels['CustomBackground'] = 'Custom Background* ';
$labels['RoundedEdges'] = 'Rounded Edges ';
$labels['Width'] = 'Width:&nbsp; ';
$labels['SpaceBetweenIcons'] = 'Space between Icons ';
$labels['SizeofIcons'] = 'Size of Icons';
$labels['CustomizeLoginIcons'] = 'Customize Login Icons';
$labels['CustomizeShape'] = 'Customize shape, theme and size of the login icons';
$labels['CustomizeYourLogin'] = 'Customize your login icons using a range of shapes and sizes. You can choose different places to display these icons and also customize redirect url after login.';
$labels['manageAuthentication'] = '<h3>Manage Authentication</h3>
                                        <p>An OpenID Connect Provider (OP) like the Gluu Server may provide many different work flows for
                                            authentication. For example, an OP may offer password authentication, token authentication, social
                                            authentication, biometric authentication, and other different mechanisms. Offering a lot of different
                                            types of authentication enables an OP to offer the most convenient, secure, and affordable option to
                                            identify a person, depending on the need to mitigate risk, and the sensors and inputs available on the
                                            device that the person is using.
                                        </p>
                                        <p>
                                            The OP enables a client (like a RoundCube site), to signal which type of authentication should be
                                            used. The client can register a
                                            <a target="_blank" href="http://openid.net/specs/openid-connect-registration-1_0.html#ClientMetadata">default_acr_value</a>
                                            or during the authentication process, a client may request a specific type of authentication using the
                                            <a target="_blank" href="http://openid.net/specs/openid-connect-core-1_0.html#AuthRequest">acr_values</a> parameter.
                                            This is the mechanism that the Gluu SSO module uses: each login icon corresponds to a acr request value.
                                            For example, and acr may tell the OpenID Connect to use Facebook, Google or even plain old password authentication.
                                            The nice thing about this approach is that your applications (like RoundCube) don"t have
                                            to implement the business logic for social login--it"s handled by the OpenID Connect Provider.
                                        </p>
                                        <p>If you are using the Gluu Server as your OP, youll notice that in the Manage Custom Scripts
                                            tab of oxTrust (the Gluu Server admin interface), each authentication script has a name.
                                            This name corresponds to the acr value.  The default acr for password authentication is set in
                                            the
                                            <a target="_blank" href="https://www.gluu.org/docs/admin-guide/configuration/#manage-authentication">LDAP Authentication</a>,
                                            section--look for the "Name" field. Likewise, each custom script has a "Name", for example see the
                                            <a target="_blank" href="https://www.gluu.org/docs/admin-guide/configuration/#manage-custom-scripts">Manage Custom Scripts</a> section.
                                        </p>';

$labels['doocumentation243'] = '<h1><a id="RoundCube_GLUU_SSO_plugin_0"></a>RoundCube GLUU SSO plugin</h1>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/plugin.jpg" alt="image"></p>
                <p>RoundCube-GLUU-SSO plugin gives access for login to your RoundCube site, with the help of GLUU server.</p>
                <p>There are already 2 versions of RoundCube-GLUU-SSO (2.4.2 and 2.4.3) plugins, each in its turn is working with oxD and GLUU servers.
                    For example if you are using RoundCube-gluu-sso-2.4.3 plugin, you need to connect with oxD-server-2.4.3.</p>
                <p>Now I want to explain in details how to use plugin step by step.</p>
                <p>Plugin will not be working if your host does not have https://.</p>
                <h2><a id="Step_1_Install_Gluuserver_13"></a>Step 1. Install Gluu-server</h2>
                <p>(version 2.4.2 or 2.4.3)</p>
                <p>If you want to use external gluu server, You can not do this step.</p>
                <p><a target="_blank" href="https://www.gluu.org/docs/deployment/">Gluu-server installation gide</a>.</p>
                <h2><a id="Step_2_Download_oxDserver_21"></a>Step 2. Download oxD-server</h2>
                <p><a target="_blank" href="https://ox.gluu.org/maven/org/xdi/oxd-server/2.4.3.Final/oxd-server-2.4.3.Final-distribution.zip">Download oxD-server-2.4.3.Final</a>.</p>
                <h2><a id="Step_3_Unzip_and_run_oXDserver_31"></a>Step 3. Unzip and run oXD-server</h2>
                <ol>
                    <li>Unzip your oxD-server.</li>
                    <li>Open the command line and navigate to the extracted folder in the conf directory.</li>
                    <li>Open oxd-conf.json file.</li>
                    <li>If your server is using 8099 port, please change “port” number to free port, which is not used.</li>
                    <li>Set parameter “op_host”:“Your gluu-server-url (internal or external)”</li>
                    <li>Open the command line and navigate to the extracted folder in the bin directory.</li>
                    <li>For Linux environment, run sh <a href="http://oxd-start.sh">oxd-start.sh</a>&amp;.</li>
                    <li>For Windows environment, run oxd-start.bat.</li>
                    <li>After the server starts, go to Step 4.</li>
                </ol>
                <h2><a id="Step_6_General_73"></a>Step 4. General</h2>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/6.png" alt="General"></p>
                <ol>
                    <li>Admin Email: please add your or admin email address for registrating site in Gluu server.</li>
                    <li>Port number: choose that port which is using oxd-server (see in oxd-server/conf/oxd-conf.json file).</li>
                    <li>Click <code>Next</code> to continue.</li>
                </ol>
                <p>If You are successfully registered in gluu server, you will see bottom page.</p>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/7.png" alt="oxD_id"></p>
                <p>For making sure go to your gluu server / OpenID Connect / Clients and search for your oxD ID</p>
                <p>If you want to reset configurations click on Reset configurations button.</p>
                <h2><a id="Step_8_OpenID_Connect_Configuration_89"></a>Step 5. OpenID Connect Configuration</h2>
                <p>OpenID Connect Configuration page for RoundCube-gluu-sso 2.4.3.</p>
                <h3><a id="Scopes_93"></a>Scopes.</h3>
                <p>You can look all scopes in your gluu server / OpenID Connect / Scopes and understand the meaning of  every scope.
                    Scopes are need for getting loged in users information from gluu server.
                    Pay attention to that, which scopes you are using that are switched on in your gluu server.</p>
                <p>In RoundCube-gluu-sso 2.4.3  you can add, enable, disable and delete scope.
                    <img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/9.png" alt="Scopes1"></p>
                <h3><a id="Custom_scripts_104"></a>Custom scripts.</h3>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/10.png" alt="Customscripts"></p>
                <p>You can look all custom scripts in your gluu server / Configuration / Manage Custom Scripts / and enable login type, which type you want.
                    Custom Script represent itself the type of login, at this moment gluu server supports (U2F, Duo, Google +, Basic) types.</p>
                <h3><a id="Pay_attention_to_that_111"></a>Pay attention to that.</h3>
                <ol>
                    <li>Which custom script you enable in your RoundCube site in order it must be switched on in gluu server too.</li>
                    <li>Which custom script you will be enable in OpenID Connect Configuration page, after saving that will be showed in RoundCube Configuration page too.</li>
                    <li>When you create new custom script, both fields are required.</li>
                </ol>
                <h2><a id="Step_9_RoundCube_Configuration_117"></a>Step 6. RoundCube Configuration</h2>
                <h3><a id="Customize_Login_Icons_119"></a>Customize Login Icons</h3>
                <p>Pay attention to that, if custom scripts are not enabled, nothing will be showed.
                    Customize shape, space between icons and size of the login icons.</p>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/11.png" alt="RoundCubeConfiguration"></p>
                <h2><a id="Step_10_Show_icons_in_frontend_126"></a>Step 7. Show icons in frontend</h2>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-plugin/master/docu/12.png" alt="frontend"></p>';

?>
