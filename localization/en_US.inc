<?php

$labels = array();
$labels['gluu_sso'] = 'OpenID Connect Single Sign-On (SSO) Plugin by Gluu';
$labels['General'] = 'General';
$labels['OpenIDConnect'] = 'OpenID Connect Configuration';
$labels['helpTrouble'] = 'Documentation';
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
$labels['registerMessageConnectProvider'] = '<br/>
                                <div  style="padding-left: 20px;">Register your site with any standard OpenID Provider (OP). If you need an OpenID Provider you can deploy the <a target="_blank" href="https://gluu.org/docs/deployment/"> free open source Gluu Server.</a></div>
                                <hr>
                                <div style="padding-left: 20px;">This plugin relies on the oxd mediator service. For oxd deployment instructions and license information, please visit the <a target="_blank" href="http://oxd.gluu.org">oxd website.</a></div>
                                <hr>';

$labels['OpenIDProvider'] = 'URI of the OpenID Provider:';
$labels['CustomURI'] = 'Custom URI after logout:';
$labels['hederGluu'] = 'OpenID Connect Single Sign-On (SSO) Plugin by Gluu';
$labels['portNumber'] = 'oxd port:';
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
                                            This is the mechanism that the OpenID Connect Single Sign-On (SSO) Plugin by Gluu uses: each login icon corresponds to a acr request value.
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
