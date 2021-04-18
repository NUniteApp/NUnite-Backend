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
            case 'register':
                $this->page =$this->json_register();
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
                    $msg = "User Authorised. Welcome " . $res['data'][0]['username'];
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




}
?>
