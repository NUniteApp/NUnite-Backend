<?php
/**
 *
 *
 * @author Mahdi Ali
 *
 */
class JSONpage {
    private $page;
    private $recordset;

    /**
     * @param $pathArr - an array containing the route information
     */
    public function __construct($pathArr, $recordset) {
        $this->recordset = $recordset;
        $path = (empty($pathArr[1])) ? "api" : $pathArr[1];

        switch ($path) {
            case 'api':
                $this->page = $this->json_welcome();
                break;
            case 'login':
                $this->page =$this->json_login();
                break;
            case 'registration':
                $this->page =$this->json_registration();
                break;
            case 'posts':
                $this->page =$this->json_posts();
                break;
            case 'create_posts':
                $this->page =$this->json_create_posts();
                break;
            case 'userprofile':
                $this->page =$this->json_userprofile();
                break;
            case 'delete_post':
                $this->page =$this->json_delete_posts();
                break;
            default:
                $this->page = $this->json_error();
                break;
        }
    }

//an arbitrary max length of 20 is set
    private function sanitiseString($x) {
        return substr(trim(filter_var($x, FILTER_SANITIZE_STRING)), 0, 20);
    }

//an arbitrary max range of 1000 is set
    private function sanitiseNum($x) {
        return filter_var($x, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>0, "max_range"=>1000)));
    }
    public function get_page() {
        return $this->page;
    }
    private function json_welcome() {
        $msg = array("message"=>"welcome", "user"=>"helllo111  smith");
        return json_encode($msg);
    }

    private function json_error() {
        $msg = array("message"=>"error");
        return json_encode($msg);
    }



    private function json_login()
    {
        $msg = "Invalid Request. email and Password Required";
        $status = 400;
        $token = null;
        $input = json_decode(file_get_contents("php://input"));
        $admin = "";

        if ($input) {


            if (isset($input->user_email) && isset($input->password)) {
                $query = "SELECT user_email, password FROM Users WHERE user_email LIKE :user_email";
                $params = ["user_email" => $input->user_email];
                $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
                $password = ($res['count']) ? $res['data'][0]['password'] : null;
                if (password_verify($input->password, $password)) {
                    $msg = "User Authorised. Welcome " . $res['data'][0]['user_email'];
                    $status = 200;
                    $admin = "true";

                    $token = array();
                    $token['user_email'] = $input->user_email;
                    $token['user_email'] = $res['data'][0]['user_email'];
                    $token['iat'] = time();
                    $token['exp'] = time() + (60 + 60);

                    $jwtkey = JWTKEY;
                    $token = \Firebase\JWT\JWT::encode($token, $jwtkey);

                } else {
                    $msg = "email or Password is invalid";
                    $status = 401;
                    $admin = "null";
                }
            }
        }

        return json_encode(array("status" => $status, "message" => $msg, "token" => $token, "adminStatus" => $admin));
    }
    private function json_registration(){

        $input = json_decode(file_get_contents("php://input"));

        $user_email = $input->user_email;
        $username = $input->username;
        $password =  $input->password;
        
        // Check if the email exists in the database
        $emailCheckQuery = "SELECT * FROM Users WHERE user_email = :user_email;";
        $checkQueryParams = [":user_email" => $user_email ] ;

        $resEmail = json_decode($this->recordset->getJSONRecordSet($emailCheckQuery, $checkQueryParams), true);

        if(isset($resEmail['data'][0]['user_email']) ){
            $res['status'] = 200;
            $res['message'] = "User email exists already";
    
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO Users (user_email, username, password )  Values (:user_email,:username,:password); ";
            $params = [ ":user_email" => $user_email,":username"=> $username,":password" => $password_hash];
            
            // This decodes the JSON encoded by getJSONRecordSet() from an associative array
            $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
    
            $res['status'] = 200;
            $res['message'] = "ok";
        }



        return json_encode($res);

    }


    private function json_posts()
    {
        $query = "SELECT * FROM post";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_create_posts()
    {

        // First get the inputs

        $input = json_decode(file_get_contents("php://input"));
        $post= $input->user_email;
        $post_title= $input->user_email;
        $post_description= $input->user_email;

        // Scond get the lastpost id
        $query= "SELECT post_id FROM Post ORDER BY post_id DESC LIMIT 1;";






        // Third create the folders

        // Fourth is to insert into posts table

        return json_encode($res);
    }





    private function json_userprofile()
    {
        $query = "SELECT * FROM UserProfile;";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_delete_posts()
    {
        $input = json_decode(file_get_contents("php://input"));
        $post_id = $input->post_id;
        $query = "Delete from post where post_id = :postid ";
        $deleted = [":post_id" => $post_id ] ;




        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $deleted), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        return json_encode($res);
    }




}

?>