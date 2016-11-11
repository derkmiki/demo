<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Server extends REST_Controller {


	public function __construct() {
		parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
		$this->load->database();
	}

	//allowed method
	protected $methods = array(
		'user_put' => array('limit' => 5),
		'user_get' => array('limit' => 50),
		'user_delete' => array('limit' => 5),
		'user_post' => array('limit' => 10),
	);


	public function user_put() {

		//collect input
		$data = array(
			'firstname' => $this->put('firstname'),
			'lastname' => $this->put('lastname'),
			'email' => $this->put('email')	
		);

		//validate
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('firstname', 'Firstname', 'trim|required');
		$this->form_validation->set_rules('lastname', 'Lastname', 'trim|required');
		$this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email');
		$this->form_validation->set_error_delimiters('', ',');

		//process	
		if ($this->form_validation->run()) {
			if($this->db->insert('user', $data)) {
				$id = $this->db->insert_id();
				$this->response(array('status' => TRUE, 'id' => $id), 
					REST_Controller::HTTP_CREATED 
				);
			} else {
				$this->response(array('status' => FALSE, 'message' => 'Can not create user'), 
					REST_Controller::HTTP_INTERNAL_SERVER_ERROR 
				);
			}
		} else {
			$this->response(array('status' => FALSE, 'message' => validation_errors()), 
				REST_Controller::HTTP_BAD_REQUEST 
			);	
		}

	}

	public function user_get() {

		//collect input
		$data = array(
			'id' => $this->get('id')
		);

		//validate
		$this->form_validation->set_data($data);	
		$this->form_validation->set_rules('id', 'Id', 'trim|is_natural');
		$this->form_validation->set_error_delimiters('', ',');

		//process
		if ($this->form_validation->run()) {
			if (!$data['id']) {
				$query = $this->db->get_where('user', array());
			}	else {
				$query = $this->db->get_where('user', $data);
			}
			if($query->num_rows()) {
				$this->response($query->result_array(), 
					REST_Controller::HTTP_OK 
				);		
			} else {
				$this->response(array('status' => FALSE, 'message' => 'No user found'), 
					REST_Controller::HTTP_NOT_FOUND 
				);		

			}
		} else {
			$this->response(array('status' => FALSE, 'message' => validation_errors()), 
				REST_Controller::HTTP_BAD_REQUEST 
			);				
		}	
	}

	public function user_delete() {

		//collect input
		$data = array(
			'id' => $this->delete('id')
		);

		//validate
		$this->form_validation->set_data($data);	
		$this->form_validation->set_rules('id', 'Id', 'trim|is_natural|required');
		$this->form_validation->set_error_delimiters('', ',');

		//process
		if ($this->form_validation->run()) {
			if ($this->db->delete('user', $data)) {
				$this->response(array('status' => TRUE, 'id' => $data['id']), 
					REST_Controller::HTTP_OK 
				);
			} else {
				$this->response(array('status' => FALSE, 'message' => 'Can not delete user'), 
					REST_Controller::HTTP_INTERNAL_SERVER_ERROR 
				);
			}
		} else {
			$this->response(array('status' => FALSE, 'message' => validation_errors()), 
				REST_Controller::HTTP_BAD_REQUEST 
			);				
		}	
	}

	public function user_post() {

		//id
		$id = $this->post('id');

		//collect input
		$data = array(
			'firstname' => $this->post('firstname'), 
			'lastname' => $this->post('lastname'),
			'email' => $this->post('email')		
		);
		
		//validate
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('firstname', 'Firstname', 'trim|required');
		$this->form_validation->set_rules('lastname', 'Lastname', 'trim|required');
		$this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email');
		$this->form_validation->set_error_delimiters('', ',');

		//process	
		if ($this->form_validation->run()) {
			if(!$id) {
				$this->response(array('status' => FALSE, 'message' => 'Can not update user'), 
					REST_Controller::HTTP_INTERNAL_SERVER_ERROR 
				);
			}	
			elseif($this->db->update('user', $data, array('id' => $id))) {
				$this->response(array('status' => TRUE, 'data' => $data), 
					REST_Controller::HTTP_OK 
				);
			} else {
				$this->response(array('status' => FALSE, 'message' => 'Can not update user'), 
					REST_Controller::HTTP_INTERNAL_SERVER_ERROR 
				);
			}
		} else {
			$this->response(array('status' => FALSE, 'message' => validation_errors()), 
				REST_Controller::HTTP_BAD_REQUEST 
			);	
		}
	}

}
