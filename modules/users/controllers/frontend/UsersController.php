<?php

namespace Modules\users\controllers\frontend;

use App\models\Image;
use Core\Registry;
use Modules\pages\controllers\frontend\PagesController;
use Modules\users\models\User;


class UsersController extends PagesController
{

    public $module = 'users';
    public $layout = 'inner';
    public $user;


    // Field validation. Prepare and save input data (REGISTRATION) (errors or success returned in JSON)
    public function index($params)
    {

        $asd = ['absolute' => true,];
        $response = [];

        if ($this->is_post()) {
            $required = array('username', 'first_name', 'last_name', 'password', 'confirm_password', 'email', 'address');
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $response['errors'][] = $this->localizer->get_label('DB_FIELDS', $field) . ' - ' . mb_strtolower($this->localizer->get_label('DB_SAVE_ERRORS', 'not_empty'));
                }
            }

            if (isset($response['errors'])) {
                return $this->tpl->render_json($response);
            }

            $data = array(
                'username' => Registry()->db->escape($_POST['username']),
                'first_name' => Registry()->db->escape($_POST['first_name']),
                'last_name' => Registry()->db->escape($_POST['last_name']),
                'password' => md5($_POST['password']),
                'email' => Registry()->db->escape($_POST['email']),
                'address' => Registry()->db->escape($_POST['address']),
                'post_code' => Registry()->db->escape($_POST['post_code']),
                'hash' => md5($_POST['email']),
            );

            // Verification email send on registration
            $user = new User;
            if ($user->save($data)) {
                Registry()->tpl->assign('post', $data);
                $html = Registry()->tpl->fetch(Config()->MODULES_PATH . 'users/views/frontend/users/email_verify.htm');

                $opts = array(
                    'mail' => $data['email'],
                    'html' => $html,
                    'subject' => 'Потвърждване на акаунт',
                );
                send_php_mail($opts);

                $response['success'] = $this->localizer->get_label('users', 'success');
                return $this->tpl->render_json($response);
            } else {
                $response['errors'] = $user->get_errors();
                return $this->tpl->render_json($response);
            }

        }
//        return $this->tpl->render_json(['success' => 'OK']);
    }

    // Login + session (error and success return JSON)
    public function login()
    {
        $response = [];

        if ($this->is_post()) {
            $login = new User;
            $user = $login->find_by_username_and_password($_POST['username'], md5($_POST['password']));
            if ($user instanceof User) {
                if ($user->active != 1) {
                    $response['errors'] = $this->localizer->get('users', ['PUBLIC', 'invalid_verification']);
                    return $this->tpl->render_json($response);
                }
                Registry()->session->logged_in = 1;
                Registry()->session->userinfo = serialize($user);
                $response['success'] = url_for(['route_name' => 'users_home']);
                return $this->tpl->render_json($response);
            } else {
                $response['errors'] = $this->localizer->get('KEYWORDS', 'invalid_login');
                return $this->tpl->render_json($response);
            }

        }

    }

    // Checking for existence (login data). When no login data -> redirect to login page
    public function home()
    {
        $user = unserialize(Registry()->session->userinfo);
        if (isset ($user->id)) {
            $this->obj = $user->find_by_id($user->id);
        }
        if (!isset ($user->id) || !$this->obj) {
            $this->redirect_to(url_for(['route_name' => 'users_login']));
        }
    }

    // Data update
    public function update()
    {
        $user = unserialize(Registry()->session->userinfo);
        $errors = [];

        if (isset ($user->id)) {
            $this->obj = $user->find_by_id($user->id);
        }
        if ($this->is_post()) {
            if(isset($_FILES['main_image']) && $_FILES['main_image']['error'] == UPLOAD_ERR_OK && $_FILES['main_image']['tmp_name']){
                $file_size = $_FILES['main_image']['size'];
                $max_size = (1024 * 1024)*5;
                if($file_size > $max_size){
                    $errors['main_image'] = Registry()->localizer->get_label('DB_SAVE_ERRORS', 'file_too_large');
                    return $this->tpl->render_json(['errors' => $errors]);
                }
                $file_type = $_FILES['main_image']['type'];
                $image_type = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif',
                ];
                if(!in_array($file_type, $image_type)){
                    $errors['main_image'] =  Registry()->localizer->get_label('DB_SAVE_ERRORS', 'invalid_type');
                    return $this->tpl->render_json(['errors' => $errors]);
                }
                $file_name = $_FILES['main_image']['name'];
                $extension =  explode(".", $file_name);
                $return    = array_pop($extension);
                $image_name = [
                    'jpeg',
                    'jpg',
                    'png',
                    'gif',
                ];
                if(!in_array($return, $image_name)){
                    $errors['main_image'] =  Registry()->localizer->get_label('DB_SAVE_ERRORS', 'invalid_format');
                    return $this->tpl->render_json(['errors' => $errors]);
                }

                $location = Config()->FILES_ROOT."users/{$user->id}/main_image/";
                if(!is_dir($location)){
                    mkdir($location, 0777, true);
                }

                $image = new Image();

                if ($user->main_image instanceof Image) {
                    $user->main_image->delete();
                }

                move_uploaded_file($_FILES['main_image']['tmp_name'],$location . 'main_image.' . $return);

                $profile_pic = $image->save([
                    'module_id' => $user->id,
                    'module' => 'users',
                    'keyname' => 'main_image',
                    'filename' => "main_image.$return",
                    'filesize' => filesize("$location/main_image.$return"),
                    'ord' => 1,
                ],true);


            }
            $update = $this->obj->save([
                'username' => Registry()->db->escape($_POST['username']),
                'first_name' => Registry()->db->escape($_POST['first_name']),
                'last_name' => Registry()->db->escape($_POST['last_name']),
                'email' => Registry()->db->escape($_POST['email']),
                'address' => Registry()->db->escape($_POST['address']),
                'post_code' => Registry()->db->escape($_POST['post_code']),
            ]);
            if ($update) {
                $response['success'] = url_for(['route_name' => 'users_home']);
                return $this->tpl->render_json($response);
            } else {
                $response['errors'] = $this->obj->get_errors();
                return $this->tpl->render_json($response);
            }

        }


    }

    // Password update
    public function password()
    {
        $user = unserialize(Registry()->session->userinfo);
        $user->change_pass = true;
        if ($this->is_post()) {
            $update = $user->save([
                'password' => md5($_POST['password'])
            ]);
            if ($update) {
                $response['success'] = $this->localizer->get_label('users', 'success_update_pass');
                return $this->tpl->render_json($response);
            } else {
                $response['errors'] = $user->get_errors();
                return $this->tpl->render_json($response);
            }
        }

    }

    // THE logout
    public function logout()
    {
        unset(Registry()->session->logged_in);
        unset(Registry()->session->userinfo);
        session_write_close();
        $this->redirect_to(url_for(['route_name' => 'users_login']));
    }

    // Verification link confirmation (via email)
    public function verify()
    {
        $confirm = new User;
        $this->result = $confirm->find_by_hash(Registry()->db->escape($_GET['hash']));
        if ($this->result) {
            $save = $this->result->save([
                'hash' => null,
                'active' => 1,
            ], true);
            if ($save) {
                $this->success = 'Вие успешно активирахте акаунта си. Моля, продължете към Вписване';
            }
        } else {
            $this->invalid = 'Невалиден хеш';
        }

    }

    // Send mail with forgotten password link
    public function send_email_forgotten_pass()
    {
        $response = [];
        $asd = ['absolute' => true,];

        if ($this->is_post()) {
            $user = new User;
            $this->user = $user->find_by_email(Registry()->db->escape($_POST['email']));
            if ($this->user) {
                $data = [
                    'hash_date' => date('Y-m-d H:i:s'),
                    'hash_random' => md5(openssl_random_pseudo_bytes(32)),
                ];
                $hash = $this->user->save($data, true);
                if ($hash) {
                    Registry()->tpl->assign('post', $data);
                    Registry()->tpl->assign('user', $this->user);

                    $html = Registry()->tpl->fetch(Config()->MODULES_PATH . 'users/views/frontend/users/email_forgotten_pass.htm');

                    $opts = array(
                        'mail' => $this->user->email,
                        'html' => $html,
                        'subject' => 'Забравена парола',
                    );
                    if (send_php_mail($opts)) {
                        $response['success'] = $this->localizer->get_label('users', 'success_mail_forgotten_pass');
                        return $this->tpl->render_json($response);
                    } else {
                        $response['errors'] = $this->user->get_errors();
                        return $this->tpl->render_json($response);

                    }

                }
            } else {
//                $this->invalid = 'Не сте регистриран потребител';
                $response['errors'] = $user->get_errors();
                return $this->tpl->render_json($response);
            }
        }

    }

    public function forgotten_pass()
    {

        $response = [];

        if (isset($_GET['hash_random'])) {
            $user = new User;
            $this->user = $user->find_by_hash_random(Registry()->db->escape($_GET['hash_random']));
            $this->user->forgotten_password = true;
            if ($this->user) {
                $current_time = time();
                $email_time = $this->user->hash_date;
                $email_time = strtotime($email_time);
                $expire_time = abs($current_time - $email_time) / 60;
                if ($expire_time <= 15) {
                    if ($this->is_post()) {
                        $data = [
                            'password' => md5($_POST['password'])];
                        $update = $this->user->save($data);
                        if ($update) {
                            $response['success'] = $this->localizer->get_label('users', 'success_update_pass');
                            return $this->tpl->render_json($response);
                        } else {
                            $response['errors'] = $this->user->get_errors();
                            return $this->tpl->render_json($response);
                        }
                    }

                } else {
                    $this->invalid_time = 'Изминали са повече от 15мин. след получването на имейла. Промяна на парола - невъзможна';
                }

            } else {
                $this->invalid = 'Невалиден хеш';
            }

        }

    }

    public function facebook_login()
    {
        $json_response = [];
        $new_reg = true;

        if($this->is_post()){

            $access_token = [
                'access_token' => $_POST['accessToken'],
                'fields' => 'id,first_name,last_name,email'
            ];
            $base = "https://graph.facebook.com/me?".http_build_query($access_token);

            $fb_id = isset($_POST['userID']) ? $_POST['userID'] : (isset($_POST['id']) ? $_POST['id'] : null);

            if (null !== $fb_id) {
                $ch = curl_init($base);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);

                $responseData = json_decode($response);

                $user = new User();
                $user = $user->find_by_facebook_id_or_email($responseData->id, $responseData->email);
                if (!$user instanceof User) {
                    $fb_data = [
                        'username' => Registry()->db->escape('user' . $responseData->id),
                        'facebook_id' => Registry()->db->escape($responseData->id),
                        'first_name' => Registry()->db->escape($responseData->first_name),
                        'last_name' => Registry()->db->escape($responseData->last_name),
                        'email' => Registry()->db->escape($responseData->email),
                        'password' => md5(openssl_random_pseudo_bytes(12)),
                        'active' => 1,
                        'address' => '-',
                    ];

                    $user = new User();
                    $new_reg = $user->save($fb_data, true);

                    $base = "https://graph.facebook.com/$responseData->id/picture?width=300&height=280&redirect=false";
                    $ch = curl_init($base);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    $responseImage = json_decode($response);
                    $fb_picture = fopen($responseImage->data->url, 'rb');

                    $location = Config()->FILES_ROOT."users/{$user->id}/main_image/";
                    mkdir($location, 0777, true);
                    $file_image = fopen("$location/main_image.jpg", 'wb');
                    while(!feof($fb_picture)){
                        $buffer = fread($fb_picture, 128);
                        fwrite($file_image, $buffer);
                    }

                    fclose($fb_picture);
                    fclose($file_image);

                    $image = new Image();
                    $profile_pic = $image->save([
                        'module_id' => $user->id,
                        'module' => 'users',
                        'keyname' => 'main_image',
                        'filename' => 'main_image.jpg',
                        'filesize' => filesize("$location/main_image.jpg"),
                        'ord' => 1,
                    ],true);

                }
                if ($new_reg) {
                    Registry()->session->logged_in = 1;
                    Registry()->session->userinfo = serialize($user);
                    $json_response['success'] = url_for(['route_name' => 'users_home']);
                    return $this->tpl->render_json($json_response);
                }

            }

        }


    }

}
