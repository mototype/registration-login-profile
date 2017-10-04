<?php

Router()->add('users', '/users/:action/:id', array('controller'=> 'users', 'module'=>'users', 'action'=>'index', 'id'=>OPTIONAL));


