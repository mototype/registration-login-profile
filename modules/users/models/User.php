<?php
    namespace Modules\users\models;

    use Core\ActiveRecord;

    class User extends ActiveRecord
    {

        public $module = 'users';
        protected $is_i18n = false;
        protected $has_mirror = false;
        public $table_name = 'users';
        public $change_pass = false;
        public $forgotten_password = false;

        // Relationship with 'images' table for image upload
        protected $has_one = array(
            'main_image' => array(
                'association_foreign_key' => 'id',
                'foreign_key' => 'module_id',
                'class_name' => 'App\models\Image',
                'join_table' => 'users',
                'conditions' => "module = 'users' and keyname='main_image'"
            ),
        );

        // Fields validation (rules in after_validation())
        public function validation($rules, $data)
        {
            $errors = array();

            foreach ($rules as $rule => $fields){

                if($rule == 'valid-email'){
                    foreach ($fields as $field){
                        if(!filter_var($data[$field], FILTER_VALIDATE_EMAIL)){
                            $errors[$field] = 'Field "' . $field . '" is not a valid email';
                        }
                    }
                }elseif($rule == 'unique'){
                    foreach ($fields as $field ){
                        $condition = sprintf('`%s` = \'%s\' AND `id` != %d', $field, $data[$field], $this->id);
                        $object = $this->find_first($condition);
                        if($object){
                            $errors[$field] = " $field " . 'already exists';
                        }
                    }
                }elseif($rule == 'match-string'){
                    if ($data[$fields[0]] !== $data[$fields[1]]){
                        $errors[$fields[1]] = 'Passwords do not match';
                    }
                }elseif($rule == 'match-string-db'){
                    foreach($fields as $field){
                        $old_password = $this->find_by_username_and_password($this->username, md5($_POST[$field]));
                        if(!$old_password){
                            $errors[$field] = " $field " . 'is wrong';
                        }
                    }
                }elseif($rule == 'min-length'){
                    foreach($fields as $field => $length){
                        if(mb_strlen($data[$field]) < $length){
                            $errors[$field] = 'Fields "' . $field . '" must be at least '. $length .' symbols.';

                        }
                    }
                }elseif($rule == 'recaptcha'){
                    foreach($fields as $field){
                        $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify";
                        $recaptcha_secret = Config()->recaptcha_secret;

                        if(isset($_POST[$field]) && !empty($_POST[$field])){
                            $datacapt = array('secret' => $recaptcha_secret, 'response' => $_POST[$field], 'remoteip' => $_SERVER['REMOTE_ADDR']);

                            $ch = curl_init($recaptcha_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datacapt));
                            $response = curl_exec($ch);
                            curl_close($ch);

                            $responseData = json_decode($response);
                               if(!$responseData || $responseData->success !== true){
                                   $errors[$field] = 'Invalid reCAPTCHA';
                               }
                        }else{
                            $errors[$field] = 'Please click the checkbox';
                        }
                    }
                }
            }
            return $errors;
        }

        // Rules + errors joining
        public function after_validation()
        {
            $rules = array(
                'required' => array(
                    'username',
                    'first_name',
                    'last_name',
                    'email',
                    'address',
                ),
                'unique' => array(
                    'username',
                    'email',
                ),
                'valid-email' => array(
                    'email',
                ),

            );
            // Adding more validation rules for new record in DB
            if($this->is_new_record() && (Registry()->is_frontend == 1)){
                $rules += array(
                    'required' => array(
                        'password',
                        'confirm_password',
                    ),
                    'unique' => array(
                        'username',
                        'email',
                    ),
                    'match-string' => array(
                        'password',
                        'confirm_password',
                    ),
                    'min-length' => array(
                        'password' => 8,
                        'confirm_password' => 8,
                    ),
                    'recaptcha' => array(
                        'g-recaptcha-response',
                    ),
                );
            }
            // Validation rules for data update
            if(!$this->is_new_record() && (Registry()->is_frontend == 1)){
                $rules += array(
                    'unique' => array(
                        'username',
                        'email',
                    ),
                );
            }
            // Validation rules for password update
            if(!$this->is_new_record() && (Registry()->is_frontend == 1 && $this->change_pass !== false)){
                $rules = array(
                    'required' => array(
                        'old_password',
                        'password',
                        'confirm_password',
                    ),
                    'match-string-db' => array(
                        'old_password',
                    ),
                    'match-string' => array(
                        'password',
                        'confirm_password',
                    ),
                    'min-length' => array(
                        'old_password' => 8,
                        'password' => 8,
                        'confirm_password' => 8,
                    ),

                );
            }
            // Validation rules for forgotten pass
            if(!$this->is_new_record() && $this->forgotten_password !== false){
                $rules = array(
                    'required' => array(
                        'password',
                        'confirm_password',
                    ),
                    'match-string' => array(
                        'password',
                        'confirm_password',
                    ),
                );
            }
            $errors = $this->validation($rules, $_POST);

            if(Registry()->is_frontend == 1){
                $errors = array_values($errors);
                $this->errors = array_values($this->errors);
            }

          $this->errors = array_merge($this->errors,$errors);

        }

    }