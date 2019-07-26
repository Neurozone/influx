<?php

class User
{
    /*
     * `id` int(11) NOT NULL AUTO_INCREMENT,
                             `login` varchar(225) NOT NULL,
                             `hash` varchar(225) NOT NULL,
                             `email` varchar(225) NOT NULL,
                             `otpSecret` varchar(225) DEFAULT NULL,
     */

    private $id;
    private $login = '';
    private $hash = '';
    private $email = '';
    private $otpSecret = '';
    private $db;
    private $logger;

    public function __construct($db,$logger){

        $this->db = $db;
        $this->db->set_charset('utf8mb4');
        $this->db->query('SET NAMES utf8mb4');
        $this->logger = $logger;

    }

    public function createUser()
    {

    }

    private function loadUserFromDb()
    {

    }


    public function userExist()
    {

        $count = 0;

        if($stmt = $this->db->prepare("select count(login) as cn from user where login = ?")) {
            $stmt->bind_param("s", $_POST['login']);

            $stmt->execute();

            /* instead of bind_result: */
            $result = $stmt->get_result();

            while ($row = $result->fetch_array()) {
                $count = $row['cn'];
            }

            if($count == 1)
            {
                return true;
            }

            return false;
        }

    }

    public function checkPassword($password)
    {

        if($stmt = $this->db->prepare("select hash from user where login = ? and password = ?")) {

            $stmt->bind_param("s", $this->login);

            $stmt->execute();

            /* instead of bind_result: */
            $result = $stmt->get_result();

            while ($row = $result->fetch_array()) {
                $this->hash = $row['hash'];
            }

        }

        if (password_verify($password, $this->hash)) {
            return true;
        } else {
            return false;
        }
    }

    protected function createSynchronisationCode()
    {
        return substr(sha1(rand(0, 30) . time() . rand(0, 30)), 0, 10);
    }


    function resetPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function createHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getOtpSecret()
    {
        return $this->otpSecret;
    }

    /**
     * @param string $otpSecret
     */
    public function setOtpSecret($otpSecret)
    {
        $this->otpSecret = $otpSecret;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return mixed
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param mixed $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }


}