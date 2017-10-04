<?php

    Router()->add('users_xhr', '/users/xhr', array('controller'=> 'users', 'module'=>'users', 'action'=>'xhr'));
    Router()->add('users_index', '/registracija', array('controller'=> 'users', 'module'=>'users', 'action'=>'index'));
    Router()->add('users_login', '/vpisvane', array('controller'=> 'users', 'module'=>'users', 'action'=>'login'));
    Router()->add('users_home', '/profil', array('controller'=> 'users', 'module'=>'users', 'action'=>'home'));
    Router()->add('users_update', '/profil/redaktirane', array('controller'=> 'users', 'module'=>'users', 'action'=>'update'));
    Router()->add('users_password', '/profil/smjana-na-parola', array('controller'=> 'users', 'module'=>'users', 'action'=>'password'));
    Router()->add('users_logout', '/logout', array('controller'=> 'users', 'module'=>'users', 'action'=>'logout'));
    Router()->add('users_verify', '/verify', array('controller'=> 'users', 'module'=>'users', 'action'=>'verify'));
    Router()->add('users_send_email_forgotten_pass', '/send_email_forgotten_pass', array('controller'=> 'users', 'module'=>'users', 'action'=>'send_email_forgotten_pass'));
    Router()->add('users_forgotten_pass', '/forgotten_pass', array('controller'=> 'users', 'module'=>'users', 'action'=>'forgotten_pass'));

