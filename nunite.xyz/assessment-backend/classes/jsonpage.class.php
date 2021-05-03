<?php
/**
 *
 *
 * @author Mahdi Ali
 *
 */
class JSONpage
{
    private $page;
    private $recordset;

    /**
     * @param $pathArr - an array containing the route information
     */
    public function __construct($pathArr, $recordset)
    {
        $this->recordset = $recordset;
        $path = (empty($pathArr[1])) ? "api" : $pathArr[1];

        switch ($path) {
            case 'api':
                $this->page = $this->json_welcome();
                break;
            case 'login':
                $this->page = $this->json_login();
                break;
            case 'registration':
                $this->page = $this->json_registration();
                break;

            case 'create_posts':
                $this->page = $this->json_create_posts();
                break;
            case 'create_tenancy_posts':
                $this->page = $this->json_create_tenancy_posts();
                break;
            case 'userprofile':
                $this->page = $this->json_userprofile();
                break;
            case 'username':
                $this->page = $this->json_username();
                break;
            case 'my_friends':
                $this->page = $this->json_my_friends();
                break;


                // retrieving posts
            case 'retrieve_user_posts':
                $this->page = $this->json_retrieve_user_posts();
                break;
            case 'random_posts':
                $this->page = $this->json_retrieving_random_posts();
                break;
            case 'random_tenancy_posts':
                $this->page = $this->json_retrieving_random_tenancy_post();
                break;

                //reporting and enquirees
            case 'report_posts':
                $this->page = $this->json_report_post();
                break;




            //Admin Panel Endpoints

            //Admin Panel Endpoints
            //Admin Panel Endpoints
            //Admin Panel Endpoints
            //Admin Panel Endpoints

            //Display Data
            case 'admin_users':
                $this->page =$this->json_admin_users();
                break;
            case 'admin_posts':
                $this->page =$this->json_admin_posts();
                break;
            case 'admin_sponsors':
                $this->page =$this->json_admin_sponsors();
                break;
            case 'admin_reported_posts':
                $this->page =$this->json_admin_reported_posts();
                break;
            case 'admin_contact_requests':
                $this->page =$this->json_admin_contact_requests();
                break;

            //Dashboard Data
            case 'admin_login':
                $this->page =$this->json_admin_login();
                break;
            case 'total_users':
                $this->page =$this->json_total_users();
                break;
            case 'total_posts':
                $this->page =$this->json_total_posts();
                break;
            case 'total_reports':
                $this->page =$this->json_total_reports();
                break;
            case 'total_requests':
                $this->page =$this->json_total_requests();
                break;

            //Delete Data
            case 'delete_post':
                $this->page =$this->json_delete_posts();
                break;
            case 'delete_user':
                $this->page =$this->json_delete_user();
                break;
            case 'delete_sponsor':
                $this->page =$this->json_delete_sponsor();
                break;
            case 'delete_request':
                $this->page =$this->json_delete_request();
                break;

            // add data
            case 'create_sponsorships':
                $this->page = $this->json_create_sponsorships();
                break;

            default:
                $this->page = $this->json_error();
                break;
        }
    }

//an arbitrary max length of 20 is set
    private function sanitiseString($x)
    {
        return substr(trim(filter_var($x, FILTER_SANITIZE_STRING)), 0, 20);
    }

//an arbitrary max range of 1000 is set
    private function sanitiseNum($x)
    {
        return filter_var($x, FILTER_VALIDATE_INT, array("options" => array("min_range" => 0, "max_range" => 1000)));
    }

    public function get_page()
    {
        return $this->page;
    }

    private function json_welcome()
    {
        $msg = array("message" => "welcome", "user" => "helllo111  smith");
        return json_encode($msg);
    }

    private function json_error()
    {
        $msg = array("message" => "error");
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

    private function json_registration()
    {

        $input = json_decode(file_get_contents("php://input"));

        $user_email = $input->user_email;
        $username = $input->username;
        $password = $input->password;
        $firstname = $input->firstname;
        $lastname = $input->lastname;
        $dob = $input->dob;
        $course = $input->course;
        $student_id =$input ->student_id;
        $bio =$input ->bio;
        $avatar_url = $input -> avatar_url;


        // Check if the email exists in the database
        $emailCheckQuery = "SELECT * FROM Users WHERE user_email = :user_email;";
        $checkQueryParams = [":user_email" => $user_email];

        $resEmail = json_decode($this->recordset->getJSONRecordSet($emailCheckQuery, $checkQueryParams), true);

        if (isset($resEmail['data'][0]['user_email'])) {
            $res['status'] = 200;
            $res['message'] = "User email exists already";

        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO Users (user_email, username, password )  Values (:user_email,:username,:password); ";
            $params = [":user_email" => $user_email, ":username" => $username, ":password" => $password_hash];
            $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
            $queryforUserid = "SELECT user_id FROM Users ORDER BY user_id DESC LIMIT 1;";
            $user_idparam = [];
            $resuserid = json_decode($this->recordset->getJSONRecordSet($queryforUserid, $user_idparam), true);

        }

            if (isset($resuserid['data'][0]['user_id'])) {

                $queryforprofile= "INSERT into UserProfile(firstname, lastname,dob,course, avatar_url,bio,student_id,user_id) values (:firstname,:lastname,:dob,:course,:avatar_url,:bio,:student_id,:user_id)";
                $paramsProfile =[":firstname" => $firstname,":lastname" => $lastname, ":dob" => $dob,":course" => $course,":avatar_url" => $avatar_url,":bio" => $bio,":student_id" => $student_id,":user_id" => $resuserid['data'][0]['user_id']];

                $resinsertprofile = json_decode($this->recordset->getJSONRecordSet($queryforprofile, $paramsProfile), true);

                return json_encode($resinsertprofile);
            }


                $res['status'] = 200;
                $res['message'] = "Something went wrong";



        return json_encode($res);

    }
    private function Json_report_post()
    {
        $input = json_decode(file_get_contents("php://input"));


        $post_id= $input->post_id;
        $report_title= $input->report_title;
        $report_text = $input->report_text;

        $query = "INSERT INTO Reports  (report_title,report_text,post_id) VALUES (:report_title, :report_text,:post_id );; ";
        $params = [":report_title" => $report_title, ":report_text" => $report_text, ":post_id" => $post_id];


        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $res['status'] = 200;
        $res['message'] = "Report has been sent please wait for admin for response, in due course";
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

        $post_user_id = $_POST['post_user_id'];


        print_r($post_user_id);
        // Second get the last post id
        $query = "SELECT post_id FROM Post ORDER BY post_id DESC LIMIT 1;";
        $params = [];
        $resLastPostId = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $currentPostId = $resLastPostId['data']['0']['post_id'] + 1;

        // Create the uploads directory 
        $uploadsDirectory = dirname(__FILE__, 2) . "/uploads";
        $userDirectory = $uploadsDirectory . "/" . $post_user_id;
        $postDirectory = $userDirectory . "/" . $currentPostId;


        if (!file_exists($postDirectory)) {
            mkdir($postDirectory, 0777, true);
        }


        $path = pathinfo($post_file);
        $filename = $path['filename'];
        $ext = $path['extension'];
        $temp_name = $_FILES['postimage']['tmp_name'];
        $path_filename_ext = $postDirectory . "/" . $filename . "." . $ext;
        if (file_exists($path_filename_ext)) {
            // echo "Sorry, file already exists.";
        } else {
            move_uploaded_file($temp_name, $path_filename_ext);
            // echo "Congratulations! File Uploaded Successfully.";
        }
        $post_image_url = "uploads/" . $post_user_id . "/" . $currentPostId . "/" . $_FILES['postimage']['name'];


        $createPostQuery = "INSERT INTO Post (post_title, post_image_url, post_type, post_description, user_id) VALUES (:post_title,
        :post_image_url, :post_type, :post_description, :user_id );";


        $createPostParams =
            [
                ":post_title" => $post_title,
                ":post_image_url" => $post_image_url,
                ":post_description" => $post_description,
                ":user_id" => $post_user_id,
                ":post_type" => "generic"
            ];

        $resCreatePost = json_decode($this->recordset->getJSONRecordSet($createPostQuery, $createPostParams), true);


        $res['status'] = 200;
        $res['filename'] = $post_file;
        $res['dirname'] = dirname(__FILE__, 2);
        $res['lastPostId'] = $resLastPostId;
        $res['postDirectory'] = $postDirectory;
        // Third create the folders

        // Fourth is to insert into posts table

        return json_encode($res);
    }

    private function json_create_tenancy_posts()
    {

        // First get the inputs
        $input = json_decode(file_get_contents("php://input"));
        $post_file = $_FILES['postimage']['name'];
        // $post_file = $_FILES['postimage']['tmp_name'];
        $post_title = $_POST['post_title'];
        $post_description = $_POST['post_description'];
        $post_user_id = $_POST['post_user_id'];
        $property_type = $_POST['property_type'];
        $location = $_POST['location'];
        $price = $_POST['price'];
        $payment_type = $_POST['payment_type'];


        print_r($post_user_id);
        // Second get the last post id
        $query = "SELECT post_id FROM Post ORDER BY post_id DESC LIMIT 1;";
        $params = [];
        $resLastPostId = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $currentPostId = $resLastPostId['data']['0']['post_id'] + 1;

        // Create the uploads directory
        $uploadsDirectory = dirname(__FILE__, 2) . "/uploads";
        $userDirectory = $uploadsDirectory . "/" . $post_user_id;
        $postDirectory = $userDirectory . "/" . $currentPostId;


        if (!file_exists($postDirectory)) {
            mkdir($postDirectory, 0777, true);
        }


        $path = pathinfo($post_file);
        $filename = $path['filename'];
        $ext = $path['extension'];
        $temp_name = $_FILES['postimage']['tmp_name'];
        $path_filename_ext = $postDirectory . "/" . $filename . "." . $ext;
        if (file_exists($path_filename_ext)) {
            // echo "Sorry, file already exists.";
        } else {
            move_uploaded_file($temp_name, $path_filename_ext);
            // echo "Congratulations! File Uploaded Successfully.";
        }
        $post_image_url = "uploads/" . $post_user_id . "/" . $currentPostId . "/" . $_FILES['postimage']['name'];


        $createPostQuery = "INSERT INTO Post (post_title, post_image_url, post_type, post_description, user_id, property_type, location, price, payment_type ) VALUES (:post_title,
        :post_image_url, :tenancy, :post_description, :user_id, :property_type, :location, :price, :payment_type );";


        $createPostParams =
            [
                ":post_title" => $post_title,
                ":post_image_url" => $post_image_url,
                ":post_description" => $post_description,
                ":user_id" => $post_user_id,
                ":property_type" => $property_type,
                ":location" => $location,
                ":price" => $price,
                ":payment_type" => $payment_type,
                ":tenancy" => "tenancy",

        ];

        $resCreatePost = json_decode($this->recordset->getJSONRecordSet($createPostQuery, $createPostParams), true);




        $res['status'] = 200;
        $res['filename'] = $post_file;
        $res['dirname'] = dirname(__FILE__, 2);
        $res['lastPostId'] = $resLastPostId;
        $res['postDirectory'] = $postDirectory;
        // Third create the folders

        // Fourth is to insert into posts table

        return json_encode($res);
    }

    private function json_username()
    {
        $input = json_decode(file_get_contents("php://input"));

        $user_id = $input->user_id;

        $userNameQuery = "SELECT * FROM Users WHERE user_id = :user_id;";
        $userNameParams = [":user_id" => $user_id];

        $resUserName = json_decode($this->recordset->getJSONRecordSet($userNameQuery, $userNameParams), true);

        $userName = $resUserName['data'][0]['username'];

        $res['status'] = 200;
        $res['username'] = $userName;

        return json_encode($res);
    }

  // selects all the logged in users profile data
    private function json_userprofile()
    {
        $input = json_decode(file_get_contents("php://input"));

        $user_id = $input->user_id;

        $query = "SELECT * FROM UserProfile
         WHERE user_id = :user_id";
        $params = [":user_id" => $user_id];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }


    // this retrieves random generic posts where the user id isnt the same as the user logged in, works after user_id is posted
    private function Json_retrieving_random_posts()
    {

        $input = json_decode(file_get_contents("php://input"));
        $user_id = $input->user_id;
        $params = [":user_id" => $user_id];
        $query= "Select Post.post_id, Post.post_title, Post.post_image_url,Post.post_type, Post.post_description, Post.post_date, Post.user_id,UserProfile.firstname,UserProfile.lastname,UserProfile.avatar_url
                From post 
                INNER JOIN UserProfile on Post.user_id= UserProfile.user_id
                WHERE Post.user_id != :user_id
                AND post_type = 'generic'
                OR post_type = 'sponsorship'
                ORDER BY random()
                limit 10;
                 ";
       //

        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);


    }
    // this retrieves the users posts where the user_id isnt the same as the user logged in, works after user_id is posted
    private function Json_retrieve_user_posts()
    {

        $input = json_decode(file_get_contents("php://input"));
        $user_id = $input->user_id;
        $params = [":user_id" => $user_id];
        $query= "Select Post.post_id, Post.post_title, Post.post_image_url,Post.post_type, Post.post_description, Post.post_date, Post.user_id,UserProfile.firstname,UserProfile.lastname,UserProfile.avatar_url
                From post 
                INNER JOIN UserProfile on Post.user_id= UserProfile.user_id
                WHERE Post.user_id = :user_id
                limit 10;
                 ";


        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);


    }

    // this retrieves random tenancy posts where the user_id isnt the same as the user logged in, works after user_id is posted
    private function json_retrieving_random_tenancy_post()
    {
        $input = json_decode(file_get_contents("php://input"));
        $user_id = $input->user_id;

        $query= "Select Post.post_id, Post.post_title, Post.post_image_url,Post.post_type, Post.post_description, Post.post_date, Post.user_id,UserProfile.firstname,UserProfile.lastname,UserProfile.avatar_url
                From post 
                INNER JOIN UserProfile on Post.user_id= UserProfile.user_id
                WHERE Post.user_id != :user_id
                AND post_type = 'tenancy'
                ORDER BY random()
                limit 10;
                ";
        
        
        
        $params = [":user_id" => $user_id];
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);

    }

    Private function json_my_friends(){

        $input = json_decode(file_get_contents("php://input"));
        $user_id = $input->user_id;
        $query = "Select friends_id from Friends 
                where user_id = :user_id  
                 AND status = 'accepted'  ; ";
        $user_idparams = [":user_id" => $user_id];
        $resfriendid = json_decode($this->recordset->getJSONRecordSet($query, $user_idparams), true);

        if (isset($resfriendid['data'][0]['friends_id'])) {
        }








        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);



    }





















    //Admin Panel Endpoints
    //Admin Panel Endpoints
    //Admin Panel Endpoints
    //Admin Panel Endpoints

    /**
     *
     *
     * @author Rajan Makh
     *
     */


    private function json_admin_login()
    {
        $msg = "Invalid Request. email and Password Required";
        $status = 400;
        $token = null;
        $input = json_decode(file_get_contents("php://input"));
        $admin = "";

        if ($input) {


            if (isset($input->user_email) && isset($input->password)) {
                $query = "SELECT user_id, username, user_email, password FROM Users WHERE user_email LIKE :user_email AND user_admin = 1";
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

    //Display Data
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
        $query = "SELECT Users.user_id, Users.username, Post.post_id, Post.post_title, Post.post_description, Post.post_date 
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
//
        $query = "SELECT Post.post_id, Post.post_title, Post.post_description,Post.post_image_url, Post.post_date
            FROM Post 
            WHERE post_type = 'sponsorship';";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_admin_reported_posts()
    {
        $query = "SELECT Reports.report_id, Reports.report_title, Reports.report_text, Post.post_id, Post.post_title, Post.post_description, Users.username, Users.user_email
                  FROM Reports
                  JOIN Post on (Reports.post_id = Post.post_id)
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


    private function json_admin_contact_requests()
    {
        $query = "SELECT ContactForm.contact_id, ContactForm.contact_title, ContactForm.contact_text, Users.username, Users.user_email
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


    //Dashboard Data
    private function json_total_users()
    {
        $query = "SELECT COUNT(user_id) as totalusers FROM Users;";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_total_posts()
    {
        $query = "SELECT COUNT(post_id) as totalposts FROM Post;";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_total_reports()
    {
        $query = "SELECT COUNT(report_id) as totalreports FROM Reports;";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }

    private function json_total_requests()
    {
        $query = "SELECT COUNT(contact_id) as totalrequests FROM ContactForm;";
        $params = [];

        $nextpage = null;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $params), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        $res['next_page'] = $nextpage;
        return json_encode($res);
    }


    //Delete Data
    private function json_delete_posts()
    {
        $input = json_decode(file_get_contents("php://input"));
        $post_id = $input->post_id;

        $query = "DELETE FROM Post WHERE post_id = :post_id;";

        $deleted = [":post_id" => $post_id ] ;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $deleted, 'DELETE' ), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        return json_encode($res);
    }

    private function json_delete_user()
    {
        $input = json_decode(file_get_contents("php://input"));
        $user_id = $input->user_id;

        $query = "DELETE FROM Users WHERE user_id = :user_id;";

        $deleted = [":user_id" => $user_id ] ;

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $deleted, 'DELETE' ), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        return json_encode($res);
    }

    private function json_delete_sponsor()
    {
        $input = json_decode(file_get_contents("php://input"));
        $post_id = $input->post_id;

        $query = "DELETE FROM Post WHERE post_id = :post_id;";
        $deleted = [":post_id" => $post_id];

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $deleted, 'DELETE' ), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        return json_encode($res);
    }

    private function json_delete_request()
    {
        $input = json_decode(file_get_contents("php://input"));
        $contact_id = $input->contact_id;

        $query = "DELETE FROM ContactForm WHERE contact_id = :contact_id;";
        $deleted = [":contact_id" => $contact_id];

        // This decodes the JSON encoded by getJSONRecordSet() from an associative array
        $res = json_decode($this->recordset->getJSONRecordSet($query, $deleted, 'DELETE' ), true);

        $res['status'] = 200;
        $res['message'] = "ok";
        return json_encode($res);
    }



    private function json_create_sponsorships()
    {

        // First get the inputs
        $input = json_decode(file_get_contents("php://input"));
        $post_file = $_FILES['postimage']['name'];
        // $post_file = $_FILES['postimage']['tmp_name'];
        $post_title = $_POST['post_title'];
        $post_description = $_POST['post_description'];

        $post_user_id = $_POST['post_user_id'];

        
        // Second get the last post id
        $query = "SELECT post_id FROM Post ORDER BY post_id DESC LIMIT 1;";
        $params = [];
        $resLastPostId = json_decode($this->recordset->getJSONRecordSet($query, $params), true);
        $currentPostId = $resLastPostId['data']['0']['post_id'] + 1;

        // Create the uploads directory
        $uploadsDirectory = dirname(__FILE__, 2) . "/uploads";
        $userDirectory = $uploadsDirectory . "/" . $post_user_id;
        $postDirectory = $userDirectory . "/" . $currentPostId;


        if (!file_exists($postDirectory)) {
            mkdir($postDirectory, 0777, true);
        }


        $path = pathinfo($post_file);
        $filename = $path['filename'];
        $ext = $path['extension'];
        $temp_name = $_FILES['postimage']['tmp_name'];
        $path_filename_ext = $postDirectory . "/" . $filename . "." . $ext;
        if (file_exists($path_filename_ext)) {
            // echo "Sorry, file already exists.";
        } else {
            move_uploaded_file($temp_name, $path_filename_ext);
            // echo "Congratulations! File Uploaded Successfully.";
        }
        $post_image_url = "uploads/" . $post_user_id . "/" . $currentPostId . "/" . $_FILES['postimage']['name'];


        $createPostQuery = "INSERT INTO Post (post_title, post_image_url, post_type, post_description, user_id) VALUES (:post_title,
        :post_image_url, :post_type, :post_description, :user_id );";


        $createPostParams =
            [
                ":post_title" => $post_title,
                ":post_image_url" => $post_image_url,
                ":post_description" => $post_description,
                ":user_id" => $post_user_id,
                ":post_type" => "sponsorship"
            ];

        $resCreatePost = json_decode($this->recordset->getJSONRecordSet($createPostQuery, $createPostParams), true);


        $res['status'] = 200;
        $res['filename'] = $post_file;
        $res['dirname'] = dirname(__FILE__, 2);
        $res['lastPostId'] = $resLastPostId;
        $res['postDirectory'] = $postDirectory;
        // Third create the folders

        // Fourth is to insert into posts table

        return json_encode($res);
    }

}

?>
