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
            case 'username': 
                $this->page =$this->json_username();
                break;

                //Admin Panel Endpoints
                case 'admin_users':
                    $this->page =$this->json_admin_users();
                    break;
                case 'admin_posts':
                    $this->page =$this->json_admin_posts();
                    break;
                case 'admin_sponsors':
                    $this->page =$this->json_admin_sponsors();
                    break;
                case 'admin_contact_requests':
                    $this->page =$this->json_admin_contact_requests();
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
                $query = "SELECT user_id, username, user_email, password FROM Users WHERE user_email LIKE :user_email";
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
                    $token['user_id'] = $res['data'][0]['user_id'];
                    $token['username'] = $res['data'][0]['username'];
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
        $post_file = $_FILES['postimage']['name'];
        // $post_file = $_FILES['postimage']['tmp_name'];
        $post_title = $_POST['post_title'];
        $post_description = $_POST['post_description'];
        $post_type = $_POST['post_type'];
        $post_user_id = $_POST['post_user_id'];

        // Second get the last post id
        $query= "SELECT post_id FROM Post ORDER BY post_id DESC LIMIT 1;";
        $params = [];
        $resLastPostId = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $currentPostId = $resLastPostId['data']['0']['post_id'] + 1 ; 

        // Create the uploads directory 
        $uploadsDirectory = dirname(__FILE__, 2) . "\uploads"; 
        $userDirectory = $uploadsDirectory . "\\" . $post_user_id;
        $postDirectory = $userDirectory . "\\" . $currentPostId;
        

        if(!file_exists($postDirectory) ){
            mkdir($postDirectory, 0777, true);
        }
        

        $path = pathinfo($post_file);
        $filename = $path['filename'];
        $ext = $path['extension'];
        $temp_name = $_FILES['postimage']['tmp_name'];
        $path_filename_ext = $postDirectory ."\\".$filename.".".$ext;
        if (file_exists($path_filename_ext)) {
            // echo "Sorry, file already exists.";
        }else{
            move_uploaded_file($temp_name , $path_filename_ext);
           // echo "Congratulations! File Uploaded Successfully.";
        }
        $post_image_url = "uploads/" . $post_user_id . "/" . $currentPostId . "/". $_FILES['postimage']['name'] ;


        $createPostQuery = "INSERT INTO Post (post_title, post_image_url, post_type, post_description, user_id) VALUES (:post_title,
        :post_image_url, :post_type, :post_description, :user_id );"; 


        $createPostParams = 
        [
            ":post_title" =>  $post_title,
            ":post_image_url"=> $post_image_url,
            ":post_type" => $post_type,
            ":post_description" => $post_description, 
            ":user_id" => $post_user_id 
        ];

        $resCreatePost = json_decode($this->recordset->getJSONRecordSet($createPostQuery , $createPostParams ), true);




        $res['status'] = 200;
        $res['filename'] = $post_file;
        $res['dirname'] = dirname(__FILE__, 2);
        $res['lastPostId'] = $resLastPostId;
        $res['postDirectory'] = $postDirectory;    
        // Third create the folders

        // Fourth is to insert into posts table

        return json_encode($res);
    }

    private function json_username() {
        $input = json_decode(file_get_contents("php://input"));

        $user_id = $input->user_id;

        $userNameQuery = "SELECT * FROM Users WHERE user_id = :user_id;";
        $userNameParams = [":user_id" => $user_id ] ;

        $resUserName = json_decode($this->recordset->getJSONRecordSet($userNameQuery, $userNameParams), true);

        $userName = $resUserName['data'][0]['username'];

        $res['status'] = 200;
        $res['username'] = $userName;

        return json_encode($res);
    }



    private function json_userprofile()
    {

        $query = "SELECT ContactForm.contact_id, Users.username, Users.user_email, ContactForm.contact_title, ContactForm.contact_text
                  FROM ContactForm
                  JOIN Users on (ContactForm.user_id = Users.user_id);";

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



    //Admin Panel Endpoints
    //Admin Panel Endpoints
    //Admin Panel Endpoints
    //Admin Panel Endpoints
    //Admin Panel Endpoints
    //Admin Panel Endpoints

    private function json_admin_users()
    {
        $query = "SELECT Users.user_id, UserProfile.firstname, UserProfile.lastname, Users.username, Users.user_email 
                  FROM Users
                  JOIN UserProfile on (Users.user_id = UserProfile.user_id);";

        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_admin_posts()
    {
        $query = "SELECT Users.user_id, Users.username, Post.post_id, Post.post_title, Post.post_date 
                  FROM Post
                  JOIN Users on (Post.user_id = Users.user_id);";

        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_admin_sponsors()
    {
        $query = "SELECT Sponsorships.sponsor_id, Sponsorships.sponsor_title, Sponsorships.sponsor_text
                      FROM Sponsorships;";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_admin_contact_requests()
    {
        $query = "SELECT ContactForm.contact_id, Users.username, Users.user_email, ContactForm.contact_title, ContactForm.contact_text
                  FROM ContactForm
                  JOIN Users on (ContactForm.user_id = Users.user_id);";
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

        $query = "DELETE FROM Post WHERE post_id = :post_id ";

        $deleted = [":post_id" => $post_id ] ;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $deleted, 'DELETE' ), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        return json_encode($res);
    }


}

?>