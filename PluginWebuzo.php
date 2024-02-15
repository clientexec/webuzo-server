<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServerPlugin.php';
require_once dirname(__FILE__) . '/WebuzoApi.php';

class PluginWebuzo extends ServerPlugin
{
    public $features = array(
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => true,
        'directlink' => true,
        'admindirectlink' => true,
        'upgrades' => true
    );

    public $api;
    public $xmlapi;

    public function getVariables()
    {
        $variables = array (
            lang("Name") => array (
                "type" => "hidden",
                "description" => "Used By CE to show plugin - must match how you call the action function names",
                "value" => "Webuzo"
            ),
            lang("Description") => array (
                "type" => "hidden",
                "description" => lang("Description viewable by admin in server settings"),
                "value" => lang("Webuzo control panel integration")
            ),
            lang("Username") => array (
                "type" => "text",
                "description" => lang("Webuzo admin username"),
                "value" => ""
            ),
            lang("API Key") => array (
                "type" => "textarea",
                "description" => lang("Generate an API Key from Webuzo admin panel -> API Keys page"),
                "value" => "",
                "encryptable" => true
            ),
            lang("Use SSL") => array (
                "type" => "yesno",
                "description" => lang("Set NO if you want to make curl calls to insecure ports. YES if want to communicate with secure ports.<br><b>NOTE:</b>It is suggested that you keep this as YES"),
                "value" => "1"
            ),
            lang("Failure E-mail") => array (
                "type" => "text",
                "description" => lang("E-mail address Webuzo error messages will be sent to"),
                "value" => ""
            ),
            lang("Actions") => array (
                "type" => "hidden",
                "description" => lang("Current actions that are active for this plugin per server"),
                "value" => "Create,Delete,Suspend,UnSuspend"
            ),
            lang('reseller')  => array(
                'type'          => 'hidden',
                'description'   => lang('Whether this server plugin can set reseller accounts'),
                'value'         => '1',
            ),
            lang('package_addons') => array(
                'type'          => 'hidden',
                'description'   => lang('Supported signup addons variables'),
                'value'         => array(
                    'DISKSPACE', 'BANDWIDTH', 'SSL'
                ),
            ),
            lang('package_vars_values') => array(
                'type'          => 'hidden',
                'description'   => lang('Hosting account parameters'),
                'value'         => array(
                    'owner' => array(
                        'type'           => 'check',
                        'label'          => 'Make the reseller account own itself',
                        'description'    => lang('Make the reseller account own itself.'),
                        'value'          => '1',
                    ),
                )
            )
        );
        return $variables;
    }

    /**
     * Sets up the WebuzoApi object in order to make requests to the server.
     * @param <type> $args Standard set of arguments in order to make API request.
     */
    public function setup($args)
    {
        if (isset($args['server']['variables']['ServerHostName']) && isset($args['server']['variables']['plugin_webuzo_Username']) && isset($args['server']['variables']['plugin_webuzo_API_Key']) && isset($args['server']['variables']['plugin_webuzo_Use_SSL'])) {
            $this->api = new WebuzoApi($args['server']['variables']['ServerHostName'], $args['server']['variables']['plugin_webuzo_Username'], $args['server']['variables']['plugin_webuzo_API_Key'], $args['server']['variables']['plugin_webuzo_Use_SSL']);
        } else {
            throw new CE_Exception('Missing Server Credentials: please fill out all information when editing the server.');
        }
    }

    /**
     * Emails Webuzo server errors.
     * @param String $name
     * @param String $message
     * @param Array $args
     * @return string
     */
    private function emailError($name, $message, $args)
    {
        $error = "Webuzo Account " . $name . " Failed. ";
        if (trim($args['server']['variables']['plugin_webuzo_Failure_E-mail'])) {
            $error .= "An email with the Details was sent to " . $args['server']['variables']['plugin_webuzo_Failure_E-mail'] . ".\n";
        }

        if (is_array($message)) {
            $message = implode("\n", trim($message));
        }

        // remove apikey from e-mails
        unset($args['server']['variables']['plugin_webuzo_API_Key']);

        CE_Lib::log(1, 'Webuzo Error: ' . print_r(array('type' => $name, 'error' => $error, 'message' => $message, 'params' => $args), true));

        if (!empty($args['server']['variables']['plugin_webuzo_Failure_E-mail'])) {
            $mailGateway = new NE_MailGateway();
            $mailGateway->mailMessageEmail(
                $message,
                $args['server']['variables']['plugin_webuzo_Failure_E-mail'],
                "Webuzo Plugin",
                $args['server']['variables']['plugin_webuzo_Failure_E-mail'],
                "",
                "Webuzo Account " . $name . " Failure"
            );
        }
        return $error . nl2br($message);
    }

    public function getPackages($args)
    {
        $this->setup($args);
        return $this->api->packages();
    }

    public function getAccounts($args)
    {
        $this->setup($args);
        return $this->api->accounts();
    }

    /**
     * Show views that might be specific to this plugin.
     * This content should be echoed out not returned
     *
     * @param UserPackage $user_package
     * @param CE_Controller_Action $action
     * @return html
     */
    public function show_publicviews($user_package, $action)
    {
		//echo 'This is public view';
		
		return true;
    }

    /**
     * Preps for account creation or update.
     * @param <type> $args
     */
    public function validateCredentials($args)
    {
        //$this->setup($args);
        $args['package']['username'] = trim(strtolower($args['package']['username']));

        $errors = array();

        // Ensure that the username is not test and doesn't contain test
        if (strpos(strtolower($args['package']['username']), 'test') !== false) {
            if (strtolower($args['package']['username']) != 'test') {
                $args['package']['username'] = str_replace('test', '', $args['package']['username']);
            } else {
                $errors[] = 'Domain username can\'t contain \'test\'';
            }
        }

        // Username cannot start with a number
        if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1))) {
            $args['package']['username'] = preg_replace("/^\d*/", '', $args['package']['username']);

            if (is_numeric(mb_substr(trim($args['package']['username']), 0, 1)) || strlen(trim($args['package']['username'])) == 0) {
                $errors[] = 'Domain username can\'t start with a number';
            }
        }

        // Username cannot contain a space
        if (strpos($args['package']['username'], " ") !== false) {
            $args['package']['username'] = str_replace(" ", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain spaces';
        }

         // Username cannot contain a period (.)
        if (strpos($args['package']['username'], ".") !== false) {
            $args['package']['username'] = str_replace(".", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain periods';
        }

        // Username cannot contain a @
        if (strpos($args['package']['username'], "@") !== false) {
            $args['package']['username'] = str_replace("@", "", $args['package']['username']);
            $errors[] = 'Domain username can\'t contain @';
        }

        // Username cannot be greater than 16 characters (if database prefixing is on in WHM, then it is only 8)
        if (strlen($args['package']['username']) > 16) {
            $args['package']['username'] = mb_substr($args['package']['username'], 0, 16);
        } elseif (strlen(trim($args['package']['username'])) <= 0) {
            $errors[] = 'The Webuzo username is blank.';
        } elseif (strlen(trim($args['package']['password'])) <= 0) {
            $errors[] = 'The Webuzo password is blank';
        }

        // Only make the request if there have been no errors so far.
        if (!empty($errors) && count($errors) == 0) {
            if (strpos($args['package']['password'], $args['package']['username']) !== false) {
                $errors[] = 'Domain password can\'t contain domain username';
            }
        }

        // Check if we want to supress errors during signup and just return a valid username
        if (isset($args['noError'])) {
            return $args['package']['username'];
        } else {
            if (!empty($errors) && count($errors) > 0) {
                CE_Lib::log(4, "plugin_webuzo::validate::error: " . print_r($errors, true));
                throw new CE_Exception($errors[0]);
            }
            return $args['package']['username'];
        }
    }

    //plugin function called after account is activated
    public function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->create($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been created.';
    }

    public function create($args)
    {
        $this->setup($args);
        $errors = array();

        if ($args['package']['name_on_server'] == null) {
            throw new CE_Exception("This package is not configured properly.  Missing 'Package Name on Server'.");
        }

        // package add-ons handling (Currently not supported)
        /* if (isset($args['package']['addons']['DISKSPACE'])) {
            @$args['package']['acl']['acl-rslimit-disk'] += ((int)$args['package']['addons']['DISKSPACE']);
        }
        if (isset($args['package']['addons']['BANDWIDTH'])) {
            @$args['package']['acl']['acl-rslimit-bw'] += ((int)$args['package']['addons']['BANDWIDTH']) * 1024; // Convert from Gigs to MB
        }
        if (isset($args['package']['is_reseller']) && isset($args['package']['addons']['SSL']) && $args['package']['addons']['SSL'] == 1) {
            $args['package']['acl']['acl-ssl'] = 1;
        } */

        $params = array();
        $params['user'] = $args['package']['username'];
        $params['domain'] = $args['package']['domain_name'];
        $params['plan'] = $args['package']['name_on_server'];
        $params['user_passwd'] = $args['package']['password'];
        $params['cnf_user_passwd'] = $args['package']['password'];
        $params['email'] = $args['customer']['email'];
        $params['billing_prefill'] = 1;
        $params['create_user'] = 1;

        $userPackage = new UserPackage($args['package']['id']);
        $userPackage->setCustomField('User Name', $params['user']);

        // Check if we need to set a dedicated IP
        if ($userPackage->getCustomField('Shared') == '0') {
            $params['ip'] = $args['package']['ip'];
        }

        $request = $this->api->call('add_user', $params);

        if (!empty($request['error'])) {
            $errors[] = $this->emailError('Creation', $request['error'], $args);
        } elseif (!empty($request['done'])) {
            // setup the reseller permissions if necessary
            if (isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                $this->addReseller($args);
            }
        } else {
            $errors[] = "Error connecting to Webuzo server";
        }

        if (!empty($errors) && count($errors) > 0) {
            CE_Lib::log(4, "plugin_webuzo::create::error: " . print_r($errors, true));
            throw new CE_Exception($errors[0]);
        }
        return;
    }

    public function doUpdate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->update($this->buildParams($userPackage, $args));
        return $userPackage->getCustomField("Domain Name") . ' has been updated.';
    }

    public function update($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
		
		$params = array('edit_user' => 1, 'user' => $args['package']['username'], 'user_name' => $args['package']['username'], 'domain' => $args['package']['domain_name'], 'email' => $args['customer']['email'], 'plan' => $args['package']['name_on_server']);

        $userPackage = new UserPackage($args['userPackageId']);

        // Check if we need to set a dedicated IP
        if ($userPackage->getCustomField('Shared') == '0') {
            $params['ip'] = $args['package']['ip'];
        }
		
        $params['reseller'] = 0;
		if(!empty($args['package']['is_reseller'])){
            $params['reseller'] = 1;
			
			if(!empty($args['package']['variables']['owner'])){
				$params['owner'] = $args['package']['username'];
			}
		}
		
        $errors = array();
        // Loop over changes array
        foreach ($args['changes'] as $key => $value) {
            switch ($key) {
                case 'username':
                    $errors[] = 'Username cannot be changed';
                    break;

                case 'password':
					$params['user_passwd'] = $value;
					$params['cnf_user_passwd'] = $value;
                    $request = $this->api->call('add_user', $params);
                    // passwd has a different json struct.
                    if (!empty($request['error'])) {
                        $errors = $request['error'];
						$this->emailError('Password Change', $request['error'], $args);
                    }
                    break;

                case 'domain':
					$params['domain'] = $value;
                    $request = $this->api->call('add_user', $params);
                    if (!empty($request['error'])) {
                        $errors = $request['error'];
						$this->emailError('Domain Change', $request['error'], $args);
                    }
                    $args['package']['domain_name'] = $value;
                    break;

                case 'ip':
					$params['ip'] = $value;
					// When you want to use sharedip you should post empty IP
					if(!empty($_POST['usesharedip'])){
						$params['ip'] = '';
					}
					
                    $request = $this->api->call('add_user', $params);
                    if (!empty($request['error'])) {
                        $errors = $request['error'];
						$this->emailError('IP Change', $request['error'], $args);
                    }
                    break;

                case 'package':
					$params['plan'] = $value;
                    $request = $this->api->call('add_user', $params);
                    if (!empty($request['error'])) {
                        $errors = $request['error'];
						$this->emailError('Plan Change', $request['error'], $args);
                    } else {
                        // setup or delete the reseller permissions if necessary
                        if (isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                            if (!isset($args['changes']['leave_reseller'])) {
                                $this->addReseller($args);
                            }
                        } else {
                            // If the old package was a reseller, we need to remove it.
                            if (isset($args['changes']['remove_reseller']) && $args['changes']['remove_reseller'] == 1) {
                                $this->removeReseller($args);
                            }
                        }
                    }
                    break;
            }
        }

        if (!empty($errors) && count($errors) > 0) {
            CE_Lib::log(4, "plugin_webuzo::update::error: " . print_r($errors, true));
            throw new CE_Exception(current($errors));
        }
    }

    public function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->delete($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }

    public function delete($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);

        if (isset($args['package']['is_reseller'])) {
			$request = $this->api->call('users', ['delete_user' => $args['package']['username'], 'del_sub_acc' => 1]);
        } else {
			$request = $this->api->call('users', ['delete_user' => $args['package']['username']]);
        }

        if (!empty($request['error'])) {
            $errors[] = $this->emailError('Deletion', $request['error'], $args);
        }

        if (!empty($errors) && count($errors) > 0) {
            CE_Lib::log(4, "plugin_webuzo::delete::error: " . print_r($errors, true));
            throw new CE_Exception($errors[0]);
        }
    }

    public function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->suspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been suspended.';
    }

    public function suspend($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
        $request = $this->api->call('users', array('suspend' => $args['package']['username']));

        if (!empty($request['error'])) {
            $errors[] = $this->emailError('Suspension', $request['error'], $args);
        }

        if (!empty($errors) && count($errors) > 0) {
            CE_Lib::log(4, "plugin_webuzo::suspend::error: " . print_r($errors, true));
            throw new CE_Exception($errors[0]);
        }
    }

    public function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->unsuspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been unsuspended.';
    }

    public function unsuspend($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
        $request = $this->api->call('users', array('unsuspend' => $args['package']['username']));

        if (!empty($request['error'])) {
            $errors[] = $this->emailError('Unsuspension', $request['error'], $args);
        }

        if (!empty($errors) && count($errors) > 0) {
            CE_Lib::log(4, "plugin_webuzo::unsuspend::error: " . print_r($errors, true));
            throw new CE_Exception($errors[0]);
        }
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to Webuzo server');
        $this->setup($args);
        try {
            $response = $this->api->call('');
        } catch (Exception $e) {
            throw new CE_Exception($e->getMessage());
        }

        if (!is_array($response) || empty($response['version'])) {
            throw new CE_Exception("Connection to server failed.");
        }
    }

    private function addReseller($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
		
		$params = array('edit_user' => 1, 'user' => $args['package']['username'], 'user_name' => $args['package']['username'], 'domain' => $args['package']['domain_name'], 'email' => $args['customer']['email'], 'plan' => $args['package']['name_on_server'], 'reseller' => 1);
        
		/* CE_Lib::log(4, "plugin_webuzo::addReseller::args: " . var_export($args, true));

		$userPackage = new UserPackage($args['userPackageId']);
        
		CE_Lib::log(4, "plugin_webuzo::addReseller::userPackage: " . var_export($userPackage, true));
		
        // Check if we need to set a dedicated IP
        if ($userPackage->getCustomField('Shared') == '0') {
            $params['ip'] = $args['package']['ip'];
        }
        
		CE_Lib::log(4, "plugin_webuzo::addReseller::after getCustomField: " . var_export($userPackage->getCustomField('Shared'), true)); */

        if (isset($args['package']['variables']['owner']) && $args['package']['variables']['owner'] == 1) {
            $params['owner'] = $args['package']['username'];
        }
		
		$request = $this->api->call('add_user', $params);
		
		if (!empty($request['error'])) {
			$errors = $request['error'];
			$this->emailError('Setup Reseller', $request['error'], $args);
		}

        if (!empty($errors) && count($errors) > 0) {
            CE_Lib::log(4, "plugin_webuzo::setupreseller::error: " . print_r($errors, true));
            throw new CE_Exception(current($errors));
        }
    }

    private function removeReseller($args)
    {
        $this->setup($args);
        $args = $this->updateArgs($args);
		
		$params = array('edit_user' => 1, 'user' => $args['package']['username'], 'user_name' => $args['package']['username'], 'domain' => $args['package']['domain_name'], 'email' => $args['customer']['email'], 'plan' => $args['package']['name_on_server'], 'reseller' => 0);

		$userPackage = new UserPackage($args['userPackageId']);
		
        // Check if we need to set a dedicated IP
        if ($userPackage->getCustomField('Shared') == '0') {
            $params['ip'] = $args['package']['ip'];
        }
		
		$request = $this->api->call('add_user', $params);
		
		if (!empty($request['error'])) {
			$errors = $request['error'];
			$this->emailError('UnSetup Reseller', $request['error'], $args);
		}

        if (!empty($errors) && count($errors) > 0) {
            CE_Lib::log(4, "plugin_webuzo::unsetupreseller::error: " . print_r($errors, true));
            throw new CE_Exception(current($errors));
        }
    }

    private function updateArgs($args)
    {
        $args['package']['username'] = trim(strtolower($args['package']['username']));
        if (isset($args['changes']['username'])) {
            $args['changes']['username'] = trim(strtolower($args['changes']['username']));
        }

        return $args;
    }

    public function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $args = $this->updateArgs($args);
        $actions = array();

        if ($args['package']['username'] == '') {
            // no username, so just pass create, and return
            $actions[] = 'Create';
            return $actions;
        }

        try {
            $request = $this->api->call('users', array('search' => $args['package']['username']));
			
			if(empty($request['users'][$args['package']['username']])){
				$actions[] = 'Create';
			}else{
				$actions[] = 'Delete';
				if (!empty($request['users'][$args['package']['username']]['status']) && $request['users'][$args['package']['username']]['status'] == 'suspended') {
					$actions[] = 'UnSuspend';
				} else {
					$actions[] = 'Suspend';
				}
			}
        } catch (Exception $e) {
            $actions[] = 'Create';
        }
        return $actions;
    }

    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);
        $params = [];
        $params['user'] = trim($args['package']['username']);

        $linkText = $this->user->lang('Login to Webuzo');
        if ($isReseller && isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
            $linkText = $this->user->lang('Login to Webuzo Reseller Panel');
        }

        if ($fromAdmin) {
            $cmd = 'panellogin';
            if ($isReseller && isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                $cmd = 'panellogin_reseller';
            }
            return [
                'cmd' => $cmd,
                'label' => $linkText
            ];
        } elseif ($getRealLink) {
			
			if(empty($isReseller)){
				$result = $this->api->enduser_call('sso&noip=1&loginAs='.$params['user']);
			}else{
				$result = $this->api->call('sso&noip=1', $params);
			}

            return array(
                'fa' => 'fa fa-user fa-fw',
                'link' => $result['done']['url'],
                'text' =>  $linkText,
                'form' => ''
            );
        } else {
            $link = 'index.php?fuse=clients&controller=products&action=openpackagedirectlink&packageId=' . $userPackage->getId() . '&sessionHash=' . CE_Lib::getSessionHash();

            if ($isReseller && isset($args['package']['is_reseller']) && $args['package']['is_reseller'] == 1) {
                $link .= '&isReseller=1';
            }

            return [
                'fa' => 'fa fa-user fa-fw',
                'link' => $link,
                'text' => $linkText,
                'form' => ''
            ];
        }
    }

    public function dopanellogin($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage);
        return $response['link'];
    }

    public function dopanellogin_reseller($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage, true, false, true);
        return $response['link'];
    }

    public function getAdminDirectLink($args)
    {
		
		$this->api = new WebuzoApi($args['ServerHostName'], $args['plugin_webuzo_Username'], $args['plugin_webuzo_API_Key'], $args['plugin_webuzo_Use_SSL']);
		
        $params = [];
        $params['noip'] = '1';
		
        $request = $this->api->call('sso', $params);
		
        if (!empty($request['done']['url'])) {
            return $request['done']['url'];
        }
        return false;
    }
}
