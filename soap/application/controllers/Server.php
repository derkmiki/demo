<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Server extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library(array('Nusoap', 'form_validation'));
		$this->load->helper(array('url', 'form', 'array'));
		$this->load->database();

	}

	public function index() {
		$namespace = base_url();
		$server = new soap_server();
		$server->configureWSDL("Server");
		
		// set our namespace
		$server->wsdl->schemaTargetNamespace = $namespace;
		$server->soap_defencoding = 'UTF-8';
		//variable declare
		//User
		$server->wsdl->addComplexType(
			'User',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'UserId' => array(
						'name' => 'UserId', 'type' => 'xsd:int'
					),
				'UserFirstname' => array(
						'name' => 'UserFirstname', 'type' => 'xsd:string'
					),
				'UserLastname' => array(
						'name' => 'UserLastname', 'type' => 'xsd:string'
					),
				'UserEmail' => array(
						'name' => 'UserEmail', 'type' => 'xsd:string'
					)
			)
		);

		//Users
		$server->wsdl->addComplexType(
			'Users',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(
				array(
					'ref' => 'SOAP-ENC:arrayType',
					'wsdl:arrayType' => 'tns:User[]'
				)
			),
			'tns:User'
		);

		//Result
		$server->wsdl->addComplexType(
			'Result', //name
			'complexType', //typeclass (complexType, simpleType, attribute)
			'struct', //phpType (array, struct)
			'all', //compositor (all, sequence, choice)
			'', //restrictionBase namespace
			array(
				'ResultId' => array(
						'name' => 'ResultId', 'type' => 'xsd:int'
					),
				'ResultStatus' => array(
						'name' => 'ResultStatus', 'type' => 'xsd:boolean'
					),
				'ResultMessage' => array(
						'name' => 'ResultMessage', 'type' => 'xsd:string'
					),
				'ResultUsers' => array(
						'name' => 'ResultUsers', 'type' => 'tns:Users' //, 'minOccurs' => '0', 'maxOccurs' => 'unbounded'
					)
			) //elements  array ( name = array(name=>”,type=>”) )
			//attrs array(array(‘ref’ => “http://schemas.xmlsoap.org/soap/encoding/:arrayType”, “http://schemas.xmlsoap.org/wsdl/:arrayType” => “string[]”))
			//arrayType  namespace:name (http://www.w3.org/2001/XMLSchema:string)
		);

		//function declare
		$server->register(
			'userAdd', 	//method
			array('user' => 'tns:User'), //input
			array('result' => 'tns:Result'), 	//output
			$namespace, //namespace
			false,	//soapaction
			'rpc', //style rpc or document
			'encoded', //use encoded or literal
			'Add User to DB'
		);

		$server->register(
			'userList', 	//method
			array('userId' => 'xsd:int'), //input
			array('result' => 'tns:Result'), 	//output
			$namespace, //namespace
			false,	//soapaction
			'rpc', //style rpc or document
			'encoded', //use encoded or literal
			'List User from DB'
		);

		$server->register(
			'userEdit', 	//method
			array('user' => 'tns:User'), //input
			array('result' => 'tns:Result'), 	//output
			$namespace, //namespace
			false,	//soapaction
			'rpc', //style rpc or document
			'encoded', //use encoded or literal
			'Edit User in DB'
		);


		$server->register(
			'userRemove', 	//method
			array('userId' => 'xsd:int'), //input
			array('result' => 'tns:Result'), 	//output
			$namespace, //namespace
			false,	//soapaction
			'rpc', //style rpc or document
			'encoded', //use encoded or literal
			'Edit User in DB'
		);


		//function define
		function userAdd($user) {
			try {
				$CI =& get_instance();
				$user = $CI->security->xss_clean($user);

				//validate
				$CI->form_validation->set_data($user);
				$CI->form_validation->set_rules('UserFirstname', 'Firstname', 'trim|required');
				$CI->form_validation->set_rules('UserLastname', 'Lastname', 'trim|required');
				$CI->form_validation->set_rules('UserEmail', 'Email Address', 'trim|required|valid_email');
				$CI->form_validation->set_error_delimiters('', ',');

				//process	
				if ($CI->form_validation->run()) {
					//collect input
					$data = array(
						'firstname' => element('UserFirstname', $user, NULL),
						'lastname' => element('UserLastname', $user, NULL),
						'email' => element('UserEmail', $user, NULL)	
					);
					if($CI->db->insert('user', $data)) {
						$id = $CI->db->insert_id();
						return array('ResultStatus' => TRUE, 'ResultId' => $id);
					} else {
						return array('ResultStatus' => FALSE, 'ResultMessage' => 'Can not create user');
					}
				} else {
					return array('ResultStatus' => FALSE, 'ResultMessage' => validation_errors());
				}
			}
			catch(Exception $e) {
				 return new soap_fault('-1', 'Server', $e.'', 'Please refer documentation.');
			}	
		}


		function userList($userId) {
			try {
				$CI =& get_instance();
				$userId = $CI->security->xss_clean($userId);

				//collect input
				$data = array(
					'id' => $userId
				);

				//validate
				$CI->form_validation->set_data($data);	
				$CI->form_validation->set_rules('id', 'Id', 'trim|is_natural');
				$CI->form_validation->set_error_delimiters('', ',');

				//process	
				if ($CI->form_validation->run()) {
					//collect input
					$CI->db->select('id as UserId, firstname as UserFirstname, lastname as UserLastname, email as UserEmail');
					if (!$data['id']) {
						$query = $CI->db->get_where('user', array());
					}	else {
						$query = $CI->db->get_where('user', $data);
					}

					if($query->num_rows()) {
						return  array('ResultStatus' => TRUE, 'ResultUsers' => $query->result_array());														
					} else {
						return array('ResultStatus' => FALSE, 'ResultMessage' => 'No user found');
					}
				} else {
					return array('ResultStatus' => FALSE, 'ResultMessage' => validation_errors());
				}
			}
			catch(Exception $e) {
				 return new soap_fault('-1', 'Server', $e.'', 'Please refer documentation.');
			}	
		}
	
		function userEdit($user) {
			try {
				$CI =& get_instance();
				$user = $CI->security->xss_clean($user);
				//validate
				$CI->form_validation->set_data($user);
				$CI->form_validation->set_rules('UserId', 'Id', 'trim|required|is_natural');
				$CI->form_validation->set_rules('UserFirstname', 'Firstname', 'trim|required');
				$CI->form_validation->set_rules('UserLastname', 'Lastname', 'trim|required');
				$CI->form_validation->set_rules('UserEmail', 'Email Address', 'trim|required|valid_email');
				$CI->form_validation->set_error_delimiters('', ',');

				//process	
				if ($CI->form_validation->run()) {
					//collect input
					$data = array(
						'firstname' => element('UserFirstname', $user, NULL),
						'lastname' => element('UserLastname', $user, NULL),
						'email' => element('UserEmail', $user, NULL)	
					);
					
					if ($CI->db->update('user', $data, array('id' => $user['UserId']))) {
						return array('ResultStatus' => TRUE, 'ResultUsers' => array($user));
					} else {
						return array('ResultStatus' => FALSE, 'ResultMessage' => 'Can not update user');
					}
				} else {
					return array('ResultStatus' => FALSE, 'ResultMessage' => validation_errors());
				}
			}
			catch(Exception $e) {
				 return new soap_fault('-1', 'Server', $e.'', 'Please refer documentation.');
			}	
		}

		function userRemove($userId) {
			try {
				$CI =& get_instance();
				$userId = $CI->security->xss_clean($userId);

				//collect input
				$data = array(
					'id' => $userId
				);

				//validate
				$CI->form_validation->set_data($data);	
				$CI->form_validation->set_rules('id', 'Id', 'trim|is_natural|required');
				$CI->form_validation->set_error_delimiters('', ',');

				//process	
				if ($CI->form_validation->run()) {
					if($CI->db->delete('user', $data)) {
						return  array('ResultStatus' => TRUE, 'ResultId' => $data['id']);														
					} else {
						return array('ResultStatus' => FALSE, 'ResultMessage' => 'Can not delete user');
					}
				} else {
					return array('ResultStatus' => FALSE, 'ResultMessage' => validation_errors());
				}
			}
			catch(Exception $e) {
				 return new soap_fault('-1', 'Server', $e.'', 'Please refer documentation.');
			}	

		}

		// pass our posted data (or nothing) to the soap service
		$server->service(file_get_contents("php://input"));
	}

}
