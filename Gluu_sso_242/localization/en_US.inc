<?php

$labels = array();
$labels['gluu_sso'] = 'Gluu SSO 2.4.2';
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

?>
