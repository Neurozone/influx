<?php


namespace Influx;


class User
{
    /*
     * `id` int(11) NOT NULL AUTO_INCREMENT,
                             `login` varchar(225) NOT NULL,
                             `password` varchar(225) NOT NULL,
                             `email` varchar(225) NOT NULL,
                             `otpSecret` varchar(225) DEFAULT NULL,
     */

    private $id;
    private $login = '';
    private $password = '';
    private $email = '';
    private $otpSecret = '';
    private $salt = '';
    private $db;

    function __construct($db){

    }

    function createUser()
    {

    }

    function resetPassword($resetPassword, $salt = '')
    {
        $this->setPassword($resetPassword, $salt);
        $this->otpSecret = '';
        $this->save();
    }

    static function encrypt($password, $salt = '')
    {
        return sha1($password . $salt);
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