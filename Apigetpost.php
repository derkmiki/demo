<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//all api calls inside the server here, this is external api
class Mdpapi extends CI_Controller {

	 var $ea_currentUsername = '';	
     var $ea_currentPassword = '';

   public function __construct()
   {
		parent::__construct();
		// Your own constructor code
     $this->db->query("SET SESSION time_zone = '+8:00'");		
     if (($this->router->method == 'authorize' || $this->router->method == 'add_client' ||  $this->router->method == 'activate_client' ||  $this->router->method == 'update_client_password' || $this->router->method == 'list_userclass' || $this->router->method == 'fb_get_gallery_image' )) {
		//excempted				 
	 		} 
	 	else {
					
		  $this->load->model("login_ci");
		  $this->load->model("core/user_session_ci");
		   
		  $token = $this->input->get_post('token', TRUE);
		  $tokenDecode = $this->login_ci->decodeToken($token);
		  if (!$tokenDecode) 
		  {
			mdp_output(0, array('error' => 'invalid access'));
		  } 
		  
		  $this->ea_currentUsername = $tokenDecode['username'];
		  $this->ea_currentPassword = $tokenDecode['password'];
		  
		  //load it
		  if (!$this->login_ci->checkToken ($token, $tokenDecode['username'], $tokenDecode['password']))
		  {
			//use second token
			  $token2 = $this->input->get_post('token2');
			  $token2Decode = $this->login_ci->decodeToken($token2);

			  if (!$token2Decode) 
			  {
				mdp_output(0, array('error' => 'invalid access'));
			  } 
			  
			  $this->ea_currentUsername = $token2Decode['username'];
			  $this->ea_currentPassword = $token2Decode['password'];

			  if (!$this->login_ci->checkToken ($token2, $token2Decode['username'], $token2Decode['password'])) 
			  {
				mdp_output(0, array('error' => 'invalid access'));
			  }
		  }		

		   //session first
		  $session = $this->input->get_post('session',TRUE);
		  $ip_address = $this->input->get_post('ip_address', TRUE);	   
			
		  if(!$this->user_session_ci->verify($ip_address, $session)) 
		  {
				mdp_output(0, array('error' => 'invalid access'));
		  }		  
	  }
   }

	public function index() 
	{
		mdp_output(0, array('error' => 'invalid access'));
	}


	public function authorize() 
	{
		//post_get
		$this->load->model('login_ci');
		$this->load->model('core/client_ci');		
		$data = array();  // array to pass back data
		//get param
		$username = $this->input->post_get('username', TRUE);
		$password = $this->input->post_get('password', TRUE);
		$ip_address = $this->input->post_get('ip_address', TRUE);
		
		$fb_id = $this->input->post_get('fb_id', TRUE);
		$fb_email = $this->input->post_get('fb_email', TRUE);

		$refer = $this->input->post_get('refer', TRUE);				
		$refer = $refer ? $refer : NULL;

		if ($fb_id && $fb_email) 
		{
			$username = $fb_email;
			$password = $fb_id;
			
			//check if email and username exists 
			$user = $this->client_ci->get('*', array('username' => $fb_email))->row();			
			if (count($user) == 0) 
			{
				$user = $this->client_ci->get('*', array('email' => $fb_email))->row();							
			}
			
			if (count($user) == 0)
			{
				$user = $this->client_ci->get('*', array('fb_id' => $fb_id))->row();											
			}

			if (count($user) == 0)
			{
				//add it this is really new
				$client_id = $this->client_ci->add($username  , $fb_email  , $password  , $this->client_ci->randomize_code(), $fb_id, $refer);
				
				if ($client_id)
				{
					//set it active
					$this->client_ci->save_params($client_id , array('active' => 1));					
				}	


			} 
			else {
				$username = $user->username;
				$this->client_ci->save_params($user->id , array('fb_id' => $fb_id, 'password' => password_hash($password, PASSWORD_DEFAULT)));									
			}							
		}

		
		$validate = array(
				'username' => $username,
				'password' => $password,
				'ip_address' => $ip_address
			);

		$this->form_validation->set_data($validate);
		
		//validate
		$this->form_validation->set_rules('username', 'Username', 'trim|required');
		$this->form_validation->set_rules('password', 'Password', 'trim|required');
		$this->form_validation->set_rules('ip_address', 'IP Address', 'trim|required');
		
		if ($this->form_validation->run() === FALSE)
		{
			mdp_output(0, array('error' => 'invalid access'));
		} 
		else 
		{
			$token = $this->login_ci->createToken($username, $password);
			$token2 = $this->login_ci->createToken2($username, $password);
			$session = $this->login_ci->set_sessions($username, $password, $ip_address, $token, $token2); 
			if ($session)
			{
			    $data['success'] = TRUE;
			    $data['message'] = 'Successfully logged-in!';
				$data['token'] = urlencode($token);
				$data['token2'] = urlencode($token2);
				$data['session'] = urlencode($session);
			} 
			else 
			{
				mdp_output(0, array('error' => 'invalid access'));
			}	
		}		
		//show ajax
		// return all our data to an AJAX call
		mdp_output(1, $data);		
	}


/////CAMERA/////
		
	public function list_camera() 
	{
		$this->load->model('core/camera_ci');
		$this->load->model('core/client_ci');
		//check user
		$user = $this->client_ci->get('*', array('username' => $this->ea_currentUsername, 'deleted' => 0, 'active' => 1))->row();		
		if (count($user) == 0) 	mdp_output(0, array('error' => 'no session user'));		


		$cameras = $this->camera_ci->get_all(0, PHP_INT_MAX, 'u.user = '.$user->id);
			
		$cams['camera'] = $cameras;		
		$cams['camera_count'] = count($cameras);					
		
		mdp_output(1, $cams);			
	}

	public function list_camera_preset() 
	{
		$this->load->model('core/camerapreset_ci');

		$cameras = $this->camerapreset_ci->get_all();
			
		$cams['camera'] = $cameras;		
		$cams['camera_count'] = count($cameras);					
		
		mdp_output(1, $cams);			
	}

	public function get_camera() 
	{
		$this->load->model('core/camera_ci');
		$this->load->model('core/client_ci');
		$id = $this->input->post_get('id', TRUE);

		$user = $this->client_ci->get('*', array('username' => $this->ea_currentUsername, 'deleted' => 0, 'active' => 1))->row();		
		if (count($user) == 0) 	mdp_output(0, array('error' => 'no session user'));		

		$camera = $this->camera_ci->get('*' ,  array('id' => $id, 'user' => $user->id))->row();

		if (count($camera) == 0) 	mdp_output(0, array('error' => 'camera not existed'));				
		
		mdp_output(1, $camera);

	}	

	public function delete_camera() 
	{

		$this->load->model('core/camera_ci');
		$this->load->model('core/client_ci');

		$user = $this->client_ci->get('*', array('username' => $this->ea_currentUsername, 'deleted' => 0, 'active' => 1))->row();		
		if (count($user) == 0) 	mdp_output(0, array('error' => 'no session user'));		

		$id = $this->input->post_get('id', TRUE);

		$result = $this->camera_ci->save_params($id , $user->id ,  array('deleted' => 1));

		mdp_output(1, $result);
	}


	public function edit_camera() 
	{

		$this->load->model('core/camera_ci');
		$this->load->model('core/client_ci');

		$user = $this->client_ci->get('*', array('username' => $this->ea_currentUsername, 'deleted' => 0, 'active' => 1))->row();		
		if (count($user) == 0) 	mdp_output(0, array('error' => 'no session user'));		

		$id = $this->input->post_get('id', TRUE);
		$name = $this->input->post_get('name', TRUE);
		$subject = $this->input->post_get('subject', TRUE);
		$is_attachment = $this->input->post_get('is_attachment', TRUE);

		$result = $this->camera_ci->update(array('user' => $user->id, 'id' => $id),  $name  , $subject , $is_attachment );


		if ($result) {
			mdp_output(1, $result);
		} else {
			mdp_output(0, array('error' => 'Cannot save camera setup. Review your form.'));
		}

	}


	public function add_camera() 
	{
		$this->load->model('core/userclass_ci');
		$this->load->model('core/camera_ci');
		$this->load->model('core/client_ci');

		$user = $this->client_ci->get('*', array('username' => $this->ea_currentUsername, 'deleted' => 0, 'active' => 1))->row();		
		if (count($user) == 0) 	mdp_output(0, array('error' => 'no session user'));		

		$name = $this->input->post_get('name', TRUE);
		$subject = $this->input->post_get('subject', TRUE);
		$is_attachment = $this->input->post_get('is_attachment', TRUE);


		//limiter here
		$class = $this->userclass_ci->get('camerasetup', array('id' => $user->class , 'deleted' => 0))->row();
		if (count($class) == 0 ) mdp_output(0, array('error' => 'User class has been deleted.'));	

		if ($this->camera_ci->count_all('u.user = '.$user->id)->row()->cnt >= $class->camerasetup) {
			mdp_output(0, array('error' => 'Camera setup exceeded maximum. Try to upgrade your account.'));					
		}


		$result = $this->camera_ci->add($user->id , $name  , $subject , $is_attachment );
		if ($result) {
			mdp_output(1, $result);
		} else {
			mdp_output(0, array('error' => 'Cannot add camera setup. Review your form.'));
		}

	}



}
