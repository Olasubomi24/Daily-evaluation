<?php 
class Utility extends CI_Controller{
    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-api-key,client-id');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Origin: *');
        if ("OPTIONS" === $_SERVER['REQUEST_METHOD']) {
            die();
        }
    }

    public function is_exist($condition,$table_name,$value){
        $response = array("status_code" => "0", "message"=> $value.' not found');
        $query = "SELECT ".$condition. " FROM ".$table_name . " WHERE ".$condition. " = '$value'";

        $sqlQuery = $this->db->query($query)->result_array();
        if(sizeof($sqlQuery) > 0){
            $response = array("status_code"=>"1", "message"=>$value. " already exist");
        }

        return $response;
    }
    private function yesterday_count($subject_id,$user_id){
        $response = '1';
        $dt = date('Y-m-d',strtotime("-1 days"));
        $sqlQuery = "SELECT (SELECT COUNT(subject_id) FROM activities  WHERE subject_id ='$subject_id') - 
                            (SELECT COUNT(subject_id) FROM user_subject_assessments WHERE user_id = '$user_id' AND 
                            subject_id = '$subject_id' AND inserted_dt ='$dt') yesterday_count FROM DUAL";
        $result = $this->db->query($sqlQuery)->result_array();

        if($result[0]['yesterday_count'] == 0){
            $response = '0';
        }
        return $response;
    }

    private function yesterday_data($subject_id, $user_id){
        $dt = date('Y-m-d',strtotime("-1 days"));
        $sqlQuery = "SELECT activity_id,subject,activity,activities.description as description FROM activities INNER JOIN subjects
                     ON subjects.id = activities.subject_id WHERE subject_id ='$subject_id' AND activity NOT IN (
                    SELECT activity FROM user_subject_assessments  u INNER JOIN activities a USING (activity_id)
                    WHERE user_id ='$user_id' AND u.subject_id = '$subject_id' AND inserted_dt='$dt')";
        $result = $this->db->query($sqlQuery)->result_array();

        return $result;

    }

    private function user_subject($user_id){
        $sqlQuery = "SELECT subject_id, subject, description FROM user_subjects u , subjects s WHERE u.subject_id = s.id
                    AND user_id ='$user_id'
                    GROUP BY subject_id";
        $result = $this->db->query($sqlQuery)->result_array();
        return $result;
    }

    private function no_of_activites($subject_id){
        $sqlQuery = "SELECT COUNT(activity_id) AS activity FROM activities WHERE subject_id = '$subject_id'";
        $result = $this->db->query($sqlQuery)->result_array();
        return $result;
    }

    private function user_daily_activites($user_id, $subject_id){
        $date = date('Y-m-d');
        $sqlQuery = "SELECT COUNT(subject_id) subject_id FROM user_subject_assessments WHERE user_id ='$user_id' AND subject_id='$subject_id' AND inserted_dt ='$date'";
        $result = $this->db->query($sqlQuery)->result_array();
        return $result;
    }

    private function user_weekly_activites($user_id, $subject_id){
        $sqlQuery = "SELECT COUNT(subject_id) subject_id FROM user_subject_assessments WHERE user_id ='$user_id'
                     AND subject_id='$subject_id' AND inserted_dt BETWEEN DATE_SUB(NOW(),INTERVAL 1 WEEK) AND NOW()";
        $result = $this->db->query($sqlQuery)->result_array();
        return $result;
    }

    private function user_monthly_activites($user_id, $subject_id){
        $date = date('m');
        $sqlQuery = "SELECT COUNT(subject_id) subject_id FROM user_subject_assessments WHERE user_id ='$user_id'
                     AND subject_id='$subject_id' AND MONTH(inserted_dt)=$date";
        $result = $this->db->query($sqlQuery)->result_array();
        return $result;
    }
    
    private function user_yearly_activites($user_id, $subject_id){
        $date = date('Y');
        $sqlQuery = "SELECT COUNT(subject_id) subject_id FROM user_subject_assessments WHERE user_id ='$user_id'
                     AND subject_id='$subject_id' AND YEAR(inserted_dt)=$date";
        $result = $this->db->query($sqlQuery)->result_array();
        return $result;
    }
    
    public function is_evaluation_exist($user_id,$evaluation_id){
        $response = array("status_code" => "0", "message"=>'Evaluation is not associated with the user');
        $query = "SELECT user_id, subject_id FROM user_subjects WHERE user_id = '$user_id' AND subject_id = '$evaluation_id'";

        $sqlQuery = $this->db->query($query)->result_array();
        if(sizeof($sqlQuery) > 0){
            $response = array("status_code"=>"1", "message"=>"Evaluation already exist for such User");
        }

        return $response;
    }


    public function is_activity_exist($user_id,$activity_id){
        $dt = date('Y-m-d');
        $response = array("status_code" => "0", "message"=>'Activity is not associated with the user');
        $query = "SELECT user_id, activity_id FROM user_subject_assessments WHERE user_id = '$user_id' AND activity_id = '$activity_id' and inserted_dt='$dt'";

        $sqlQuery = $this->db->query($query)->result_array();
        if(sizeof($sqlQuery) > 0){
            $response = array("status_code"=>"1", "message"=>"Activity already submitted for today, Check Back Tomorrow");
        }

        return $response;
    }

    public function account_creation($username,$phonenumber,$email,$password){
        $dt = date('Y-m-d H:i:s');
        $response = array();
        $query1 = "INSERT into users(email,username,phonenumber,password,created_at) VALUES ('$email', '$username','$phonenumber','$password','$dt')";

        $this->db->query($query1);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "User Account Creation Unsuccessful");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'User Account Creation Successful');
        }
        return $response;
    }

    public function user_login($email,$password){
        $sqlQuery = $this->db->query("SELECT u_a_c.id AS user_id,email,username,phonenumber, CASE WHEN u_a_s.user_id IS NULL THEN '0'
		                                                                                      ELSE '1' END AS assessment_status
                                    FROM users u_a_c
                                    LEFT JOIN user_subjects u_a_s
                                    ON u_a_c.id =u_a_s.user_id
                                    WHERE email = '$email' and password= '$password' LIMIT 1")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Login Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'Incorrect Username or Password');
        }
        return $response;
    }

    public function create_evalutaion_category($evaluation_category_name,$description ){
        $dt = date('Y-m-d H:i:s');
        $sqlQuery = "INSERT INTO subjects(subject,description, inserted_dt) VALUES('$evaluation_category_name', '$description','$dt')";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Evaluation Category Creation Unsuccessful");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Evaluation Category  Creation Successful');
        }
        return $response;
    }

    public function list_evalutaion_category(){
        $sqlQuery = $this->db->query("SELECT id, subject, description FROM subjects ORDER BY id desc")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No data Available');
        }
        return $response;
    }

   public function update_evalutaion_category($evaluation_category_name,$category_id, $description){
        $sqlQuery = "UPDATE subjects SET subject ='$evaluation_category_name', description= '$description' where id ='$category_id'";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Evaluation Category Failed to Update");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Evaluation Category Update Successful');
        }
        return $response;
    }

    public function delete_evaluation_category($id){
        $sqlQuery = "DELETE FROM subjects WHERE id ='$id'";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Evaluation Category Failed to Delete");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Evaluation Category Deleted Successful');
        }
        return $response;
    }

    public function admin_login($email,$password){
        $sqlQuery = $this->db->query("SELECT id, firstname, lastname, email, status FROM admin_accounts where email ='$email' AND password = '$password'")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Login Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'Incorrect Username or Password');
        }
        return $response;
    }

    public function create_user_evaluation($user_id,$evaluation_id){
        $dt = date('Y-m-d H:i:s');
         $sqlQuery = "INSERT INTO user_subjects(user_id,subject_id,inserted_dt) VALUES('$user_id', '$evaluation_id','$dt')";
        // $sqlQuery = "INSERT INTO user_subject_activities (user_id,subject_id,activity_id) 
        //              SELECT '$user_id',subject_id, activity_id
        //              FROM activities
        //              WHERE subject_id = '$evaluation_id'";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "User Evaluation creation unsuccessful");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'User Evaluation Creation Successful');
        }
        return $response;
    }

    public function get_user_evaluation($user_id){
        $sqlQuery = "SELECT user_id, subject_id,subject FROM user_subjects u
                    LEFT JOIN subjects a  ON a.id = u.subject_id
                    WHERE user_id = '$user_id'   ORDER BY u.id DESC";
        $result = $this->db->query($sqlQuery)->result(); 
        $response = array('status_code' => 0, 'message'=>'successful', 'result' =>$result);
        return $response;
    }

    public function list_users(){
        $sqlQuery = $this->db->query("SELECT id,email,username,phonenumber,created_at FROM users ORDER BY id desc")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No data Available');
        }
        return $response;
    }

    public function count_users(){
        $sqlQuery = $this->db->query("SELECT count(*) as Number_of_Users from users")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'0', 'message'=>'No User available');
        }
        return $response;
    }
    public function count_evaluation(){
        $sqlQuery = $this->db->query("SELECT count(id) as Number_of_Evaluation from subjects")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'0', 'message'=>'No User available');
        }
        return $response;
    }

    public function count_quran(){
        $sqlQuery = $this->db->query("SELECT count(id) as Number_of_Quran_Verse from quran_verses")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'0', 'message'=>'No User available');
        }
        return $response;
    }

    public function count_feedbacks(){
        $sqlQuery = $this->db->query("SELECT count(id) as Number_of_Feedbacks from feedbacks")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'0', 'message'=>'No User available');
        }
        return $response;
    }
    public function remove_user_evaluation($user_id,$assessment_id){
        $result = $this->db->query("DELETE FROM user_subjects WHERE user_id='$user_id' AND subject_id ='$assessment_id'"); 
        $response = array('status_code' => 0, 'message'=>'User Evaluation remove successful');
        return $response;
        
    }

    public function create_activity($subject_id,$activity,$description){
        $sqlQuery = "INSERT INTO activities(subject_id,activity,description) VALUES('$subject_id','$activity','$description')";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Activity creation unsuccessful");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Activity Creation Successful');
        }
        return $response;
    }
    public function list_activity($evaluation_id, $user_id){
        $date = date('Y-m-d');
        $datevalue = $this->db->query("SELECT DISTINCT DATEDIFF(DATE(inserted_dt), '$date') AS datedif 
                                      FROM user_subjects WHERE user_id ='$user_id'")->result_array();

        if($datevalue[0]['datedif'] == 0){
            $user_status = '0';
        }
        else{
            $user_status = '1';
        }
        $query = "SELECT activity_id, subject, activity,a.description
                    FROM activities a        INNER JOIN subjects s
                    ON s.id = a.subject_id   WHERE subject_id = '$evaluation_id'";

        $sqlQuery = $this->db->query($query)->result();

        if(count($sqlQuery)>0){

            $response = array('status_code'=>'0', 'message'=>'Successful','user_status'=>$user_status,'yesterday_status' => $this->yesterday_count($evaluation_id,$user_id),'yesterday_data'=>$this->yesterday_data($evaluation_id,$user_id), 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No Activity for such evaluation');
        }
        return $response;
    }

    public function admin_list_activity($evaluation_id){
        $query = "SELECT activity_id, subject, activity, a.description as description
                    FROM activities a        INNER JOIN subjects s
                    ON s.id = a.subject_id   WHERE subject_id = '$evaluation_id'";

        $sqlQuery = $this->db->query($query)->result();

        if(count($sqlQuery)>0){

            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No Activity for such evaluation', 'result'=>'No result available');
        }
        return $response;
    }
    public function update_activity($activity_id,$subject_id,$activity,$description){
        $sqlQuery = "UPDATE activities SET subject_id ='$subject_id',activity = '$activity', description = '$description' WHERE activity_id= '$activity_id'";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Activity Update unsuccessful");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Activity Update Successful');
        }
        return $response;
    }
    
    public function delete_activity($activity_id){
        $sqlQuery = "DELETE FROM activities WHERE activity_id ='$activity_id'";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Activity Failed to Delete");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Activity Deleted Successful');
        }
        return $response;
    }

    public function create_user_activity($user_id, $subject_id, $date, $activity_id){
        $sqlQuery = "INSERT INTO user_subject_assessments(user_id,subject_id,activity_id,inserted_dt) VALUES('$user_id', '$subject_id', '$activity_id','$date')";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "User Subject activity creation unsuccessful");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'User Subject activity Creation Successful');
        }
        return $response;
    }
    public function feedback($fullname,$email,$message){
        $dt = date('Y-m-d H:i:s');
        $sqlQuery = "INSERT INTO feedbacks(fullname,email,message,inserted_dt) VALUES ('$fullname','$email','$message', '$dt')";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Feedback failed to Submit");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Thanks, Feedback Submitted Successfull ');
        }
        return $response;
    }

    public function create_quran_verse($verse,$words){
        $dt = date('Y-m-d H:i:s');
        $sqlQuery = "INSERT INTO quran_verses(verse,words,status,inserted_dt) VALUES ('$verse','$words','1', '$dt')";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Verse failed to Submit");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Quran Verse Created Successfull');
        }
        return $response;
    }

    public function list_quran(){
        $sqlQuery = $this->db->query("SELECT id,verse,words,status, DATE(inserted_dt) inserted_dt FROM quran_verses ORDER BY id desc")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No data Available');
        }
        return $response;
    }

    public function list_feedback(){
        $sqlQuery = $this->db->query("SELECT id,fullname,message, DATE(inserted_dt) date FROM feedbacks ORDER BY id desc")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No data Available');
        }
        return $response;
    }
    public function admin_list_quran(){
        $sqlQuery = $this->db->query("SELECT id,verse,words,status FROM quran_verses where status ='0'")->result();

        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No data Available');
        }
        return $response;
    }

    public function update_quran_verse($verse,$words,$id){
        $sqlQuery = "UPDATE quran_verses SET verse = '$verse', words = '$words' WHERE id= '$id'";
        $this->db->query($sqlQuery);

        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Verse failed to Update");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Quran Verse Updated Successfull');
        }
        return $response;
    }

    public function active_quran_verse($id){
        $this->db->query("UPDATE quran_verses SET STATUS ='1'");
        $sqlQuery = "UPDATE quran_verses SET STATUS ='0' WHERE id = '$id'";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Quran verse Failed to Update");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Quran verse is now active');
        }
        return $response;
    }

    public function delete_quran_verse($id){
        $sqlQuery = "DELETE FROM quran_verses WHERE id ='$id'";
        $this->db->query($sqlQuery);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $response =   array('status_code' => '1','message' => "Quran verse Failed to Delete");
        } else {
            $this->db->trans_commit();
            $response =  array('status_code' => '0' ,'message' => 'Quran verse Deleted Successful');
        }
        return $response;
    }

    public function progress_report($user_id){
        $user_subjects =  $this->user_subject($user_id);
        $response = array("status_code" => "1", "result" => array());
        $aa = array();
        $i = 0;
        foreach($user_subjects as $us){
           $no_of_activites = $this->no_of_activites($us['subject_id'])[0]['activity'];
           $user_daily_activites =$this->user_daily_activites($user_id, $us['subject_id'])[0]['subject_id'];

           $aa[$i] = array("subject_id" => $us['subject_id'] , 'subject_name'=>$us['subject'], 'description'=>$us['description'],"total_no_of_activites" => $no_of_activites ,"user_no_of_activites" => $user_daily_activites );
           //$bb 
           $i++;
          
        }
   
        if ($i > 0){
           $response = array("status_code" => "0", "result" => $aa);
        }
   
        return $response;
       }

       public function user_all_report($user_id,$subject_id){
        $no_of_days = date('t');
        $no_of_daily_activites = $this->no_of_activites($subject_id)[0]['activity'];
        $user_daily_activites =$this->user_daily_activites($user_id, $subject_id)[0]['subject_id'];
        $no_weekly_activities = $this->no_of_activites($subject_id)[0]['activity'] * 7;
        $user_weekly_activites =$this->user_weekly_activites($user_id, $subject_id)[0]['subject_id'];
        $no_monthly_activities = $this->no_of_activites($subject_id)[0]['activity'] * $no_of_days;
        $user_monthly_activites =$this->user_monthly_activites($user_id, $subject_id)[0]['subject_id'];

        $result= array("subject_id"=>(int)$subject_id, 
        'No_of_activities'=>(int)$no_of_daily_activites, 'User_Daily_ACtivities'=>(int)$user_daily_activites,
                                                   'No_of_Weekly_Activities'=>(int)$no_weekly_activities , 'User_Weekly_Activites'=>(int)$user_weekly_activites,
                                                   'No_of_Mothly_Activities'=>(int)$no_monthly_activities, 'User_Monthly_Activites'=>(int)$user_monthly_activites);
        $response = array("status_code"=>"0", "result"=>$result);
        return $response;

       }
    
       public function weekly_repot($user_id){
        $user_subjects =  $this->user_subject($user_id);
        $response = array("status_code" => "1", "result" => array());
        $aa = array();
        $i = 0;
        foreach($user_subjects as $us){
           $weekly_activities = $this->no_of_activites($us['subject_id'])[0]['activity'] * 7;
           $no_of_activites = "$weekly_activities";
           $user_daily_activites =$this->user_weekly_activites($user_id, $us['subject_id'])[0]['subject_id'];

           $aa[$i] = array("subject_id" => $us['subject_id'] , 'subject_name'=>$us['subject'], 'description'=>$us['description'],"total_no_of_activites" => $no_of_activites ,"user_no_of_activites" => $user_daily_activites );
           $i++;
          
        }
   
        if ($i > 0){
           $response = array("status_code" => "0", "result" => $aa);
        }
   
        return $response;
       }

       public function monthly_repot($user_id){
        $no_of_days = date('t');
        $user_subjects =  $this->user_subject($user_id);
        $response = array("status_code" => "1", "result" => array());
        $aa = array();
        $i = 0;
        foreach($user_subjects as $us){
           $monthly_activities = $this->no_of_activites($us['subject_id'])[0]['activity'] * $no_of_days;
           $no_of_activites = "$monthly_activities";
           $user_daily_activites =$this->user_monthly_activites($user_id, $us['subject_id'])[0]['subject_id'];

           $aa[$i] = array("subject_id" => $us['subject_id'] , 'subject_name'=>$us['subject'], 'description'=>$us['description'],"total_no_of_activites" => $no_of_activites ,"user_no_of_activites" => $user_daily_activites );
           $i++;
          
        }
   
        if ($i > 0){
           $response = array("status_code" => "0", "result" => $aa);
        }
   
        return $response;
       }

       public function yearly_repot($user_id){
        $user_subjects =  $this->user_subject($user_id);
        $response = array("status_code" => "1", "result" => array());
        $aa = array();
        $i = 0;
        foreach($user_subjects as $us){
           $no_of_activites = $this->no_of_activites($us['subject_id'])[0]['activity'];
           $user_daily_activites =$this->user_yearly_activites($user_id, $us['subject_id'])[0]['subject_id'];

           $aa[$i] = array("subject_id" => $us['subject_id'] , 'subject_name'=>$us['subject'], 'description'=>$us['description'],"total_no_of_activites" => $no_of_activites ,"user_no_of_activites" => $user_daily_activites );
           $i++;
          
        }
   
        if ($i > 0){
           $response = array("status_code" => "0", "result" => $aa);
        }
   
        return $response;
       }

       public function other_evaluation($user_id){
        $sqlQuery = $this->db->query("SELECT id , subject, description FROM subjects WHERE id NOT IN( 
                                     SELECT subject_id  FROM user_subjects u, subjects s
                                     WHERE u.subject_id = s.id AND user_id ='$user_id')")->result();
        if(count($sqlQuery)>0){
            $response = array('status_code'=>'0', 'message'=>'Successful', 'result'=>$sqlQuery);
        }
        else{
            $response = array('status_code'=>'1', 'message'=>'No data Available' , 'result'=>$sqlQuery);
        }
        return $response;
       }

}
?>