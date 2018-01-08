<?php

/**
 * Gluu-oxd-library
 *
 * An open source application library for PHP
 *
 *
 * @copyright Copyright (c) 2017, Gluu Inc. (https://gluu.org/)
 * @license	  MIT   License            : <http://opensource.org/licenses/MIT>
 *
 * @package	  Oxd Library by Gluu
 * @category  Library, Api
 * @version   3.0.1
 *
 * @author    Gluu Inc.          : <https://gluu.org>
 * @link      Oxd site           : <https://oxd.gluu.org>
 * @link      Documentation      : <https://gluu.org/docs/oxd/3.0.1/libraries/php/>
 * @director  Mike Schwartz      : <mike@gluu.org>
 * @support   Support email      : <support@gluu.org>
 * @developer Volodya Karapetyan : <https://github.com/karapetyan88> <mr.karapetyan88@gmail.com>
 *

 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2017, Gluu inc, USA, Austin
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */
/**
 * Client Setup_client class
 *
 * Class is connecting to oxd-server via socket, and registering site in gluu server.
 *
 * @package		  Gluu-oxd-library
 * @subpackage	Libraries
 * @category	  Relying Party (RP) and User Managed Access (UMA)
 * @see	        Client_Socket_OXD_RP
 * @see	        Client_OXD_RP
 * @see	        Oxd_RP_config
 */
require_once 'Client_OXD_RP.php';

class Get_client_access_token extends Client_OXD_RP {
    /*
     * @var string $request_op_host                         Gluu server url
     */

    private $request_op_host = null;

    /**
     * @var string $request_op_host                         Gluu server url
     */
    private $request_oxd_id;

    /**
     * @var string $request_client_id                       OpenID provider client id
     */
    private $request_client_id = null;

    /**
     * @var string $request_authorization_redirect_uri      OpenID provider client secret
     */
    private $request_client_secret = null;

    /**
     * Response parameter from oxd-server
     *
     * @var string $response_access_token
     */
    private $response_access_token;
    private $response_scope;
    private $response_expires_in;
    private $response_refresh_token;

    function getResponse_scope() {
        $this->response_scope = $this->response_object->data->scope;
        return $this->response_scope;
    }

    function getResponse_expires_in() {
        $this->response_expires_in = $this->response_object->data->expires_in;
        return $this->response_expires_in;
    }

    function getResponse_refresh_token() {
        $this->response_refresh_token = $this->response_object->data->refresh_token;
        return $this->response_refresh_token;
    }

    function setResponse_scope($response_scope) {
        $this->response_scope = $response_scope;
    }

    function setResponse_expires_in($response_expires_in) {
        $this->response_expires_in = $response_expires_in;
    }

    function setResponse_refresh_token($response_refresh_token) {
        $this->response_refresh_token = $response_refresh_token;
    }

    function getResponse_access_token() {
        $this->response_access_token = $this->response_object->data->access_token;
        return $this->response_access_token;
    }

    function setResponse_access_token($response_access_token) {
        $this->response_access_token = $response_access_token;
    }

    function getRequest_oxd_id() {
        return $this->request_oxd_id;
    }

    function getRequest_client_id() {
        return $this->request_client_id;
    }

    function getRequest_client_secret() {
        return $this->request_client_secret;
    }

    function setRequest_oxd_id($request_oxd_id) {
        $this->request_oxd_id = $request_oxd_id;
    }

    function setRequest_client_id($request_client_id) {
        $this->request_client_id = $request_client_id;
    }

    function setRequest_client_secret($request_client_secret) {
        $this->request_client_secret = $request_client_secret;
    }

    /**
     * @return string
     */
    public function getRequestOpHost() {
        return $this->request_op_host;
    }

    /**
     * @param string $request_op_host
     * @return void
     */
    public function setRequestOpHost($request_op_host) {
        $this->request_op_host = $request_op_host;
    }

    /**
     * Constructor
     *
     * @return	void
     */
    public function __construct($config = null) {
        parent::__construct(); // TODO: Change the autogenerated stub
    }

    /**
     * Protocol command to oXD server
     * @return void
     */
    public function setCommand() {
        $this->command = 'get_client_token';
    }

    /**
     * Protocol parameter to oXD server
     * @return void
     */
    public function setParams() {
        $this->params = array(
            "op_host" => $this->getRequestOpHost(),
            "oxd_id" => $this->getRequest_oxd_id(),
            "client_id" => $this->getRequest_client_id(),
            "client_secret" => $this->getRequest_client_secret()
        );
    }

    /**
     * Overriding request method.
     * send function sends the command to the oxD server.
     * Args:
     * command (dict) - Dict representation of the JSON command string
     * */
    public function request($url = null) {
        $this->setParams();

        $jsondata = json_encode($this->getData(), JSON_UNESCAPED_SLASHES);
        $length = strlen($jsondata);
        if ($length <= 0) {
            return array('status' => false, 'message' => 'Sorry .Problem with oxd.');
        } else {
            $length = $length <= 999 ? "0" . $length : $length;
        }
        if ($url) {
            $jsonHttpData = json_encode($this->getData()["params"]);
            $this->response_json = $this->oxd_http_request($url, $jsonHttpData);
        } else {
            $this->response_json = $this->oxd_socket_request(utf8_encode($length . $jsondata));
            $this->response_json = str_replace(substr($this->response_json, 0, 4), "", $this->response_json);
        }

        if ($this->response_json != 'Can not connect to oxd server') {
            if ($this->response_json) {
                $object = json_decode($this->response_json);
                if ($object->status == 'error') {
                    return false;
                } elseif ($object->status == 'ok') {
                    $this->response_object = json_decode($this->response_json);

                    return array('status' => true);
                }
            }
        } else {
            return false;
        }
    }

}
