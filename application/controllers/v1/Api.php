<?php 
use Restserver\Libraries\REST_Controller;

require_once APPPATH . 'controllers/v1/Utility.php'; 
require_once("application/libraries/Format.php");
require(APPPATH.'/libraries/REST_Controller.php');

class Api extends REST_Controller{
    function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	    header('Access-Control-Allow-Headers: Content-Type, x-api-key');
        header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Origin: *');
	   	if ( "OPTIONS" === $_SERVER['REQUEST_METHOD'] ) {
		  	die();
			}
    }

//    public function account_creation_post(){
//     $username = ucfirst(trim($this->input->post('username')));
//     $phonenumber = $this ->input->post('phonenumber');
//     $email = $this->input->post('email');
//     $password = md5($this->input->post('password'));

//     if($username == ""){
//         $this->response(array('status_code'=>'1', 'message'=>'Username cannot be empty'));
//     }
//     if($phonenumber == ""){
//         $this->response(array('status_code'=>'1', 'message'=>'Phonenumber is required'));
//     }
//     if($email == ""){
//         $this->response(array('status_code'=>'1', 'message'=>'Email is required'));
//     }
//     if($password ==""){
//         $this->response(array('status_code'=>'1', 'message'=>'Password cannot be empty'));
//     }

//     $utility = new Utility();
//     $table_name = 'user_accounts';
//     $condition = 'email';
//     $validate_activity = $utility->is_exist($condition,$table_name,$email);
//     if($validate_activity['status_code'] != '0'){
//         $this->response(array('status_code'=>$validate_activity['status_code'], 'message'=>$validate_activity['message']));
//     }
    
//     try{
//         return $this->response($utility->account_creation($username,$phonenumber,$email,$password));
//     }
//     catch(Exception $e){
//         $this->response(array('status_code'=>'1', 'message'=>'Registration Error'.$e->getMessage()));
//     }

// }

public function account_creation_post() {
    $username = ucfirst(trim($this->input->post('username')));
    $phonenumber = trim($this->input->post('phonenumber'));
    $email = trim($this->input->post('email'));
    $password = trim($this->input->post('password'));

    // Basic Validations
    if (empty($username)) {
        $this->response(array('status_code' => '1', 'message' => 'Username cannot be empty'), 400);
        return;
    }
    if (empty($phonenumber)) {
        $this->response(array('status_code' => '1', 'message' => 'Phone number is required'), 400);
        return;
    }
    if (!ctype_digit($phonenumber) || strlen($phonenumber) < 10 || strlen($phonenumber) > 15) {
        $this->response(array('status_code' => '1', 'message' => 'Invalid phone number format'), 400);
        return;
    }
    if (empty($email)) {
        $this->response(array('status_code' => '1', 'message' => 'Email is required'), 400);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->response(array('status_code' => '1', 'message' => 'Invalid email format'), 400);
        return;
    }
    if (empty($password)) {
        $this->response(array('status_code' => '1', 'message' => 'Password cannot be empty'), 400);
        return;
    }
    if (strlen($password) < 6) {
        $this->response(array('status_code' => '1', 'message' => 'Password must be at least 6 characters long'), 400);
        return;
    }

    // Encrypt password using MD5
    $hashed_password = md5($password);

    // Check if email exists in the database
    $utility = new Utility();
    $table_name = 'user_accounts';
    $condition = 'email';
    $validate_activity = $utility->is_exist($condition, $table_name, $email);
    
    if ($validate_activity['status_code'] != '0') {
        $this->response(array('status_code' => $validate_activity['status_code'], 'message' => $validate_activity['message']), 400);
        return;
    }

    // Attempt to create the account
    try {
        return $this->response($utility->account_creation($username, $phonenumber, $email, $hashed_password));
    } catch (Exception $e) {
        $this->response(array('status_code' => '1', 'message' => 'Registration Error: ' . $e->getMessage()), 500);
    }
}



    public function user_login_post(){
        $email = $this->input->post('email');
        $password = md5($this->input->post('password'));

        if($email == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Email is required'));
        }
       $utility = new Utility();
        try{
            return $this->response($utility->user_login($email,$password));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'User Authentication Error'.$e->getMessage()));
        }
    }

    public function create_evaluation_category_post(){
        $evaluation_category_name = ucfirst(trim($this->input->post('evaluation_category_name')));
        $description = ucfirst(trim($this->input->post('description')));
        if($evaluation_category_name == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Evaluation Category Name is required'));
        }

        if($description == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Description Name is required'));
        }

        $utility = new Utility();
        $table_name = 'subjects';
        $condition = 'subject';
        $validate_evalution_category_name = $utility->is_exist($condition, $table_name, $evaluation_category_name);

        if($validate_evalution_category_name['status_code'] != '0'){
            $this->response(array('status_code'=>$validate_evalution_category_name['status_code'], 'message'=>$validate_evalution_category_name['message']));
        }
        
        try{
            return $this->response($utility->create_evalutaion_category($evaluation_category_name,$description ));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Registration Error'.$e->getMessage()));
        }
    }

    public function list_evaluation_category_get(){
        $utility = new Utility();
        return $this->response($utility->list_evalutaion_category());
    }

    public function update_evaluation_category_post(){
        $evaluation_category_name = ucfirst(trim($this->input->post('evaluation_category_name')));
        $category_id = trim($this->input->post('id'));
        $description = ucfirst(trim($this->input->post('description')));
        if($evaluation_category_name == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Evaluation Category Name is required'));
        }
        if($category_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Category ID is required'));
        }
        if($description == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Description Name is required'));
        }
        $utility = new Utility();
        try{
            return $this->response($utility->update_evalutaion_category($evaluation_category_name,$category_id,$description));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Update Error'.$e->getMessage()));
        }

    }

    public function delete_evaluation_category_get($id){
        $utility = new Utility();
        return $this->response( $utility->delete_evaluation_category($id));
    }

    public function admin_login_post(){
        $email = $this->input->post('email');
        $password = md5($this->input->post('password'));
    
        if($email == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Email is required'));
        }
       $utility = new Utility();
        try{
            return $this->response($utility->admin_login($email,$password));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Admin Authentication Error'.$e->getMessage()));
        }
    }

    public function add_user_evaluation_post(){
        $user_id =  $this->input->post('user_id');
        $evaluation_id =  trim($this->input->post('evaluation_id'));

        if($evaluation_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly Select an Evaluation'));
        }

        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }
        
        $utility = new Utility(); 
        $id_arrs = json_decode($evaluation_id, true);        
        try{
            $success_count =  0;
            $fail_count =  0;
            $fail_msg = "";
            $fail_evalution_name = '';
            $success_evalution_name = '';
            $success_msg = "";
            foreach( $id_arrs  as $arr){ 
                    $validate_evaluation = $utility->is_evaluation_exist($user_id, $arr['id'] );
                    if($validate_evaluation['status_code'] != '1'){
                        $aaa =  $utility->create_user_evaluation($user_id, $arr['id']);
                        if ($aaa['status_code'] !== '1'){
                        $success_count++;
                        $success_evalution_name = $success_evalution_name.''. $arr['evaluation_name'] .', ';
                        }
                    }
                    else{
                        $fail_count++;
                        $fail_evalution_name = $fail_evalution_name.''. $arr['evaluation_name'] .', ';
                        continue;
                    }
            }
             
            if ($fail_count > 0){
                $fail_msg = $fail_evalution_name. ' already exit';
            }

            if ($success_count > 0){
                $success_msg = $success_evalution_name. ' added successfully';
            }
           

            $msg = $fail_msg .'  '.$success_msg;
            echo $this->response(array('status_code'=>'0', 'message'=>$msg));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'User evaluation Error'.$e->getMessage()));
        }

    }

    public function list_user_evaluation_get(){
        $user_id = trim($this->input->get('user_id'));
        $utility = new Utility();
        return $this->response($utility->get_user_evaluation($user_id));

    }

    public function list_user_get(){
        $utility = new Utility();
        return $this->response($utility->list_users());
    }
        public function count_user_get(){
        $utility = new Utility();
        return $this->response($utility->count_users());
    }

    public function count_evaluation_get(){
        $utility = new Utility();
        return $this->response($utility->count_evaluation());
    }

    public function count_quran_get(){
        $utility = new Utility();
        return $this->response($utility->count_quran());
    }

    public function count_feedbacks_get(){
        $utility = new Utility();
        return $this->response($utility->count_feedbacks());
    }


    public function remove_user_evaluation_post(){
        $user_id = $this->input->post('user_id');
        $assessment_id = trim($this->input->post('assessment_id'));

        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly Select an Evaluation'));
        }
        if($assessment_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }
        $utility = new Utility();
        try{
            return $this->response($utility->remove_user_evaluation($user_id,$assessment_id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'User evaluation removal Error'.$e->getMessage()));
        }
    }

    public function add_activity_post(){
        $subject_id = trim($this->input->post('subject_id'));
        $activity = trim(ucfirst($this->input->post('activity')));
        $description = trim(ucfirst($this->input->post('description')));

        if($subject_id =="" ){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly assign a Evaluation to the activity'));
        }
        if($activity == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly enter an activity'));
        }
        if($description == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly enter a description for the activity'));
        }

        $utility = new Utility();
        $table_name = 'activities';
        $condition = 'activity';
        $validate_activity = $utility->is_exist($condition,$table_name,$activity);
        if($validate_activity['status_code'] != '0'){
            $this->response(array('status_code'=>$validate_activity['status_code'], 'message'=>$validate_activity['message']));
        }
        
        try{
            return $this->response($utility->create_activity($subject_id,$activity,$description));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Create Activity Error'.$e->getMessage()));
        }

    }
    
    public function list_activity_get(){
        $evaluation_id = $this->input->get('evaluation_id');
        $user_id = $this->input->get('user_id');
        if($evaluation_id =="" ){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select an Evaluation'));
        }
        if($user_id =="" ){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a User'));
        }
        $utility = new Utility();
        return  $this->response($utility->list_activity($evaluation_id, $user_id));
    }

    public function admin_list_activity_get(){
        $evaluation_id = $this->input->get('evaluation_id');
        if($evaluation_id =="" ){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select an Evaluation'));
        }
       
        $utility = new Utility();
        return  $this->response($utility->admin_list_activity($evaluation_id));
    }
    public function update_activity_post(){
        $activity_id = trim($this->input->post('activity_id'));
        $subject_id = trim($this->input->post('subject_id'));
        $activity = trim(ucfirst($this->input->post('activity')));
        $description = trim(ucfirst($this->input->post('description')));

        if($activity_id == "" ){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly fill the activity id '));
        }
        if($subject_id =="" ){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly assign a Evaluation to the activity'));
        }
        if($activity == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly enter an activity'));
        }
        if($description == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly enter a description for the activity'));
        }
        $utility = new Utility();        
        try{
            return $this->response($utility->update_activity($activity_id,$subject_id,$activity,$description));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Update Activity Error'.$e->getMessage()));
        }
    }

    public function delete_activity_get($activity_id){
        $utility = new Utility();
        return $this->response( $utility->delete_activity($activity_id));
    }
    public function add_user_activity_post(){
        $user_id =  $this->input->post('user_id');
        $subject_id =  trim($this->input->post('subject_id'));
        $activity_id = trim($this->input->post('activity_body'));
        $date = $this->input->post('date');

        if($subject_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly Select an Evaluation'));
        }
    
        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }
        
        if($activity_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select an activty'));
        }
        if($date == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a date'));
        }

        $utility = new Utility(); 
        $id_arrs = json_decode($activity_id, true);        
        try{
            $success_count =  0;
            $fail_count =  0;
            $fail_msg = "";
            $fail_activity_name = '';
            $success_activity_name = '';
            $success_msg = "";
            foreach( $id_arrs  as $arr){ 
                    $validate_evaluation = $utility->is_activity_exist($user_id, $arr['activity_id'] );
                    if($validate_evaluation['status_code'] != '1'){
                        $submitData =  $utility->create_user_activity($user_id, $subject_id, $date, $arr['activity_id']);
                        if ($submitData['status_code'] !== '1'){
                        $success_count++;
                        $success_activity_name = $success_activity_name.''. $arr['activity'] .', ';
                        }
                    }
                    else{
                        $fail_count++;
                        $fail_activity_name = $fail_activity_name.''. $arr['activity'] .', ';
                        continue;
                    }
            }
             
            if ($fail_count > 0){
                $fail_msg = $fail_activity_name. ' already exit for today';
            }

            if ($success_count > 0){
                $success_msg = $success_activity_name. ' added successfully';
            }
           

            $msg = $fail_msg .'  '.$success_msg;
            echo $this->response(array('status_code'=>'0', 'message'=>$msg));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'User evaluation Error'.$e->getMessage()));
        }

    }
    public function feedback_post(){
        $fullname = trim(ucfirst($this->input->post('fullname')));
        $email = trim($this->input->post('email'));
        $message = trim($this->input->post('message'));

        if($fullname == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Full name is a required field'));
        }
        if($email == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Email is a required field'));
        }
        if($message == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Message cannot be empty'));
        }
        $utility = new Utility();
        $table_name = 'feedbacks';
        $condition = 'message';
        $validate_activity = $utility->is_exist($condition,$table_name,$email);
        if($validate_activity['status_code'] != '0'){
            $this->response(array('status_code'=>$validate_activity['status_code'], 'message'=>$validate_activity['message']));
        }

        try{
            return $this->response($utility->feedback($fullname,$email,$message));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Feedback Submission Error'.$e->getMessage()));
        }   
    }

    public function list_feedback_get(){
        $utility = new Utility();
        return $this->response($utility->list_feedback());
    }

    public function quran_verse_post(){
        $verse = trim($this->input->post('verse'));
        $word = trim($this->input->post('words'));
        $words= str_replace("'", "\'", $word);

        if($verse == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Verse cannot be empty'));
        }
        if($words == ""){
            $this->response(array('status_code'=>'1', 'message'=>'The quran words cannot be empty'));
        }

        $utility = new Utility();
        $table_name = 'quran_verses';
        $condition = 'verse';
        $validate_activity = $utility->is_exist($condition,$table_name,$verse);
        if($validate_activity['status_code'] != '0'){
            $this->response(array('status_code'=>$validate_activity['status_code'], 'message'=>$validate_activity['message']));
        }
        
        try{
            return $this->response($utility->create_quran_verse($verse,$words));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Create Quran Verse Error'.$e->getMessage()));
        }

    }

    public function list_quran_verse_get(){
        $utility = new Utility();
        return $this->response($utility->list_quran());
    }

    public function admin_list_quran_verse_get(){
        $utility = new Utility();
        return $this->response($utility->admin_list_quran());
    }

    public function update_quran_verse_post($id){
        $verse = trim($this->input->post('verse'));
        $words = trim($this->input->post('words'));

        if($verse == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Verse cannot be empty'));
        }
        if($words == ""){
            $this->response(array('status_code'=>'1', 'message'=>'The quran words cannot be empty'));
        }

        $utility = new Utility();
        
        try{
            return $this->response($utility->update_quran_verse($verse,$words,$id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Update Quran Verse Error'.$e->getMessage()));
        }
    }

    public function set_active_verse_get($id){
        $utility = new Utility();
        return $this->response( $utility->active_quran_verse($id));
    }

    public function delete_quran_verse_get($id){
        $utility = new Utility();
        return $this->response( $utility->delete_quran_verse($id));
    }

    public function progress_report_get(){
        $user_id =  $this->input->get('user_id');
        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }    

        $utility = new Utility();        
        try{

            $this->response($utility->progress_report($user_id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Daily report Error'.$e->getMessage()));
        }
    }

    public function user_all_report_get(){
        $user_id =  $this->input->get('user_id');
        $subject_id =  $this->input->get('subject_id');
        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }  
        
        if($subject_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a Subject'));
        }  

        $utility = new Utility();        
        try{

            $this->response($utility->user_all_report($user_id,$subject_id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Daily report Error'.$e->getMessage()));
        }
    }


    public function weekly_report_get(){
        $user_id =  $this->input->get('user_id');
        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }    

        $utility = new Utility();        
        try{

            $this->response($utility->weekly_repot($user_id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Daily report Error'.$e->getMessage()));
        }
    }

    public function monthly_report_get(){
        $user_id =  $this->input->get('user_id');
        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }    

        $utility = new Utility();        
        try{

            $this->response($utility->monthly_repot($user_id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Daily report Error'.$e->getMessage()));
        }
    }
    public function yearly_report_get(){
        $user_id =  $this->input->get('user_id');
        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }    

        $utility = new Utility();        
        try{

            $this->response($utility->yearly_repot($user_id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Daily report Error'.$e->getMessage()));
        }
    }

    public function user_other_evaluation_get(){
        $user_id =  $this->input->get('user_id');
        if($user_id == ""){
            $this->response(array('status_code'=>'1', 'message'=>'Kindly select a user'));
        }  
        $utility = new Utility();        
        try{

            $this->response($utility->other_evaluation($user_id));
        }
        catch(Exception $e){
            $this->response(array('status_code'=>'1', 'message'=>'Other Evaluation Error'.$e->getMessage()));
        }
    }

}

?>