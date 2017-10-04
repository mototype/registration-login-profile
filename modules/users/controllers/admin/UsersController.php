<?php

namespace Modules\users\controllers\admin;

use App\controllers\admin\AdminController;
use App\helpers\ListHelper;

class UsersController extends AdminController {

    public $module = 'users';
    public $models = [
        'Modules\users\models\User'
    ];

    // Admin's fields and filters + edit + delete
    function index ($params)
    {

        $list = new ListHelper($params);
        if($this->admin_helper->can('edit')){
            $list->add_action('edit', url_for(array('action'=>'edit', 'id' => ':id')));
        }
        if($this->admin_helper->can('delete')){
            $list->add_action('delete', 'javascript:confirm_delete(:id);');
        }

        $list->add_column('id');
        $list->add_column('username', 300);
        $list->add_column('first_name', 300);
        $list->add_column('last_name', 300);
//        $list->add_column('password', 300);
        $list->add_column('email', 300);
        $list->add_column('post_code', 50);
        $list->add_column('address', 500);
        $list->add_column('created_at', 200);
//        $list->add_column('updated_at', 200);

        $list->add_filter('username','null','text');
        $list->add_filter('email','null','text');

        $items = $this->User->find_all($list->to_sql(), 'created_at DESC', 30);
        // Action button 'Add' -> false; for show
        $list->hide_main_actions = false;
        $list->hide_default_main_actions = false;

        $list->data($items);
        $this->render($list);
        $this->session->admin_return_to = $this->request->server('REQUEST_URI');
    }

    function getList_visibility ($v)
    {
        return $this->localizer->get('VISIBILITY',(int)$v);
    }


}