<?php
class User 
{
    private $_db, $_data, $_sessionName, $_cookieName, $_isLoggedIn;

    public function __construct($user = null) 
    {
        $this->_db = DB::getInstance();

        $this->_sessionName = Config::get('session/session_name');
        $this->_cookieName = Config::get('remember-me/cookie_name');

        if(!$user) 
        {
            if(Session::exists($this->_sessionName))
            {
                $user = Session::get($this->_sessionName);
                if($this->find($user))
                {
                    $this->_isLoggedIn = true;
                }
            }
        }
        else 
        {
            $this->find($user);
        }
    }

    public function create($fields = array()) 
    {
        if(!$this->_db->insert('users', $fields))
        {
            throw new Exception('There was a problem creating an account.');
        }
    }

    public function find($user = null)
    {
        if($user)
        {
            $field = (is_numeric($user)) ? 'id' : 'email';
            $data = $this->_db->get('users', array($field, '=', $user));

            if($data->count()) 
            {
                $this->_data = $data->first();
                return true;
            }
        }
        return false;
    }

    public function login($email = null, $password = null, $remember = false)
    {
        if(!$email && !$password && $this->exists()) 
        {
            Session::put($this->_sessionName, $this->data()->id);
        } 
        else 
        {
            $user = $this->find($email);
            if($user) 
            {
                if($this->data()->Password === Hash::make($password, $this->data()->salt)) 
                {
                    Session::put($this->_sessionName, $this->data()->id);
                    if($remember)
                    {
                        $hash = Hash::unique();
                        $hashCheck = $this->_db->get('users_session', array('user_id', '=', $this->data()->id)); 
                        
                        if(!$hashCheck->count())
                        {
                            $this->_db->insert('users_session', array(
                                'user_id' => $this->data()->id,
                                'hash' => $hash
                            ));
                        } 
                        else 
                        {
                            $hash = $hashCheck->first()->hash;
                        }
                        Cookie::put($this->_cookieName, $hash, Config::get('remember-me/cookie_expiry'));
                    }
                    return true;   
                }
            }
        } 
        return false;
    }

    public function exists() 
    {
        return (!empty($this->_data)) ? true : false;
    }

    public function logout() 
    {
        $this->_db->delete('users_session', array('user_id', '=', $this->data()->id));
        Session::delete($this->_sessionName);
        Cookie::delete($this->_cookieName);
    }

    public function data() 
    {
        return $this->_data;
    }

    public function isLoggedIn() 
    {
        return $this->_isLoggedIn;
    }
}
