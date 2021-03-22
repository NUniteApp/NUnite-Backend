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
            case 'login';
                $this->page =$this->json_login();
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
        $msg = array("message"=>"welcome", "user"=>"John smith");
        return json_encode($msg);
    }

    private function json_error() {
        $msg = array("message"=>"error");
        return json_encode($msg);
    }



    private function json_login() {
        $msg = "Invalid request. Username and password required";
        $status = 400;
        $token = null;
        $input = json_decode(file_get_contents("php://input"));

        if ($input) {

            if (isset($input->email) && isset($input->password)) {
                $query  = "SELECT firstname, lastname, password FROM users WHERE email LIKE :email";
                $params = ["email" => $input->email];
                $res = json_decode($this->recordset->getJSONRecordSet($query, $params),true);
                $password = ($res['count']) ? $res['data'][0]['password'] : null;

                if (password_verify($input->password, $password)) {
                    $msg = "User authorised. Welcome ". $res['data'][0]['firstname'] . " " . $res['data'][0]['lastname'];
                    $status = 200;
                    $token = "1234";
                } else {
                    $msg = "username or password are invalid";
                    $status = 401;
                }
            }
        }

        return json_encode(array("status" => $status, "message" => $msg, "token" => $token));
    }




}
?>
