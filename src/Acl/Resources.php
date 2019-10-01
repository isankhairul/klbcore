<?php namespace Klb\Core\Acl;

use Kalbe\Model\Permissions;
use Kalbe\Model\PartnerPermissions;
use Phalcon\DiInterface;
/**
 * Class Resources
 * @package Klb\Core\Acl
 */
class Resources {
    /**
     * @var array
     */
    private $resource = [];
    /**
     * @var Acl
     */
    private $acl;

    /**
     * @param $name
     * @param array $resource
     * @return $this
     */
    public function addResource($name, array $resource = []){
        $this->resource[$name] = $resource;
        return $this;
    }

    /**
     * @param $resource
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return array
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param DiInterface $di
     * @return array
     */
    public function getMenu(DiInterface $di){
        if(!$identity = $di->get('auth')->getIdentity()){
            return [];
        }
        $this->acl = $di->get('acl');
        /** @var \Phalcon\Http\Request $request */
        $request = $di->get('request');
        $requestUri = ltrim($request->getURI(), '/');
        $this->acl->setIdentity($identity);
//        $router = $di->get('router');
        $nodes = $this->resource;
        $menu = [];
        foreach ( $nodes as $module => $node ) {
            if(isset($node['display']) && $node['display'] === false){
                continue;
            }
            $prefix = null;
            if(isset($node['prefix_uri'])){
                if($node['prefix_uri'] === true){
                    $prefix = $module;
                } else {
                    $prefix = $node['prefix_uri'];
                }
            }
            $module = '*';
            $key = $module.':*.*:*';

            $currentUri = ltrim(str_replace([':', '*'], ['/', ''], $module), '/');
            if($prefix !== null) {
                $currentUri = $prefix.'/'.$currentUri;
            }
            $active = strncmp($requestUri, $currentUri, strlen($currentUri)) === 0;
            $menuItems = [
                'source' => $key,
                'name' => $node['label'],
                'icon' => $node['icon'],
                'active'=> $active,
                'submenu' => [],
                'uri' => $currentUri,
                'nactive' => []
            ];

            foreach ( $node['permissions'] as $moduleController => $permission ){
                if(!empty($permission['children'])){
                    $this->buildPermissionWithChildren($menuItems, $permission, $moduleController, $requestUri, $prefix);
                } else {
                    $this->buildPermission($menuItems, $permission, $moduleController, $requestUri, $prefix);
                }

            }
            $nCount = count($menuItems['submenu']);
            if($nCount > 0) {
                if ($nCount <= 1) {
                    $menuItems['name'] = $menuItems['submenu'][0]['name'];
                    $menuItems['uri'] = $menuItems['submenu'][0]['uri'];
                    $menuItems['submenu'] = [];
                }
                if(in_array(true, $menuItems['nactive'])){
                    $menuItems['active'] = true;
                } else {
                    $menuItems['active'] = false;
                }
                $menu[] = $menuItems;
            }
        }
//        pre(/*$this->_dump(), */$menu);
        return $menu;
    }

    private function buildPermissionWithChildren(array & $menuItems, array $permission, $moduleController, $requestUri, $prefix){
        foreach ( $permission['children'] as $key => $child ){
            $newModuleName = $moduleController . '/' . $key;
            $this->buildPermission($menuItems, $child, $newModuleName, $requestUri, $prefix);
        }
    }

    /**
     * @param array $menuItems
     * @param array $permission
     * @param $moduleController
     * @param $requestUri
     * @param $prefix
     */
    private function buildPermission(array & $menuItems, array $permission, $moduleController, $requestUri, $prefix){
        if(isset($permission['display']) && $permission['display'] === false){
            return;
        }
        $key  = $moduleController;
        $ikey = $key . '.*:*';
        $ikey2 = $key . '.index:view';
        $ikey3 = $key . '.index:*';

        $currentUri = ltrim(str_replace([':', '*'], ['/', ''], $moduleController), '/');
        $active = strncmp($requestUri, $currentUri, strlen($currentUri)) === 0;
        if($prefix !== null && (!isset($permission['prefix_uri']) || (isset($permission['prefix_uri']) && $permission['prefix_uri'] === true))) {
            $currentUri = $prefix.'/'.$currentUri;
        }
        if(!$active && $currentUri === 'dashboard' && $requestUri === 'admin'){
            $active = true;
        }
        $menuItems['nactive'][] = $active;
        $subMenu = [
            'source' => $ikey,
            'name' => $permission['label'],
            'icon' => isset($permission['icon']) ? $permission['icon'] : 'fa fa-cube',
            'active'=> $active,
            'submenu' => [],
            'uri' => $currentUri,
            'ikey' => $ikey,
            'allow' => $this->acl->isAllowedSource($ikey) || $this->acl->isAllowedSource($ikey2) || $this->acl->isAllowedSource($ikey3)
        ];

        if(isset($permission['actions'])) {
            foreach ($permission['actions'] as $action => $actionRule) {
                if (isset($actionRule['display']) && $actionRule['display'] === false) {
                    continue;
                }
                $ikey = $key . '.' . $action . ':*';
                $ikey2 = $key . '.index:view';
                if ($action !== 'index') {
                    $uri = $subMenu['uri'] . '/' . $action;
                } else {
                    $uri = $subMenu['uri'];
                }
                $active = strncmp($requestUri, $uri, strlen($uri)) === 0;
                $menuItems['nactive'][] = $active;
                $innerSubmenu = [
                    'source' => $ikey,
                    'name' => $actionRule['label'],
                    'icon' => isset($actionRule['icon']) ? $actionRule['icon'] : 'fa fa-cubes',
                    'active' => $active,
                    'add_class' => $active ? ' current-page' : '',
                    'submenu' => [],
                    'uri' => $uri,
                    'ikey' => $ikey,
                    'allow' => $this->acl->isAllowedSource($ikey) || $this->acl->isAllowedSource($ikey2)
                ];

                if (isset ($actionRule['sub_actions'])) {
                    foreach ($actionRule['sub_actions'] as $sub => $label) {
                        $ikey = $key . '.' . $action . ':' . $sub;
                        if ($this->acl->isAllowedSource($ikey)) {
                            $innerSubmenu['allow'] = 1;
                        }
                    }
                }

                if ($innerSubmenu['allow']) {
                    $subMenu['allow'] = 1;
                    $subMenu['submenu'][] = $innerSubmenu;
                }
            }
        }
        if(intval($subMenu['allow']) === 1){
            $menuItems['submenu'][] = $subMenu;
        }
    }

    private function _dump(){
        return di()->get('acl')->getCacheData();
    }
    /**
     * @param null $id
     * @return array
     */
    public function getTree($id = null, $is_partner = false){
        $nodes = $this->resource;
        $tree = [];
        $selected = [];
        if($id){
            if(!$is_partner){
                $roles = Permissions::findByRoleId($id);
            }else{
                $roles = PartnerPermissions::findByRoleId($id);
            }
            foreach ( $roles as $role ){
                $selected[$role->resource . '.' . $role->action] = true;
            }
        }
        $state = [
            'opened' => true,
            'checked' => false,
        ];
        foreach ( $nodes as $module => $node ) {
            $n = [
                'name' => '*:' . $module . '.*:*',
                'text' => $node['label'],
//                'icon' => $node['icon'],
                'state'=> $state,
                'children' => []
            ];
            $nChecked = [];
            if(isset($selected[$n['name']])){
                $n['state']['checked'] = $selected[$n['name']];
                $nChecked[] = $selected[$n['name']];
            } else {
                $nChecked[] = false;
            }
            foreach ( $node['permissions'] as $moduleController => $permission ){
                $key  = $moduleController;
                $ikey = $key . '.*:*';
                $countAction = count($permission['actions']);
                $children = [
                    'name' => $ikey,
                    'text' => $this->displayAsMenu($permission) . $permission['label'] . ( $countAction === 0 ? ' <strong>(All Action Can Access?)</strong>': ' <strong>('. $countAction .' Actions)</strong>'),
                    'icon' => 'fa fa-folder icon-state-danger',
                    'state'=> $state,
                    'children' => []
                ];
                if(isset($selected[$ikey])){
                    $children['state']['checked'] = $selected[$ikey];
                    $nChecked[] = $selected[$ikey];
                } else {
                    $nChecked[] = false;
                }
                if(!isset($permission['actions'])){
                    $permission['actions'] = [];
                }
                foreach ( $permission['actions'] as $action => $actionRule ){
                    $ikey = $key . '.'.$action.':*';
                    if(!isset($actionRule['sub_actions'])) $actionRule['sub_actions'] = [];
                    if(!isset($actionRule['label'])) $actionRule['label'] = "";
                    $countSubAction = count($actionRule['sub_actions']);
                    $ichildren = [
                        'name' => $ikey,
                        'text' => $this->displayAsMenu($actionRule) . $actionRule['label'] . ( $countSubAction <= 0 ? ' <strong>(All Sub Action Can Access?)</strong>': ' <strong>('. ($countSubAction + (!array_key_exists('view', $actionRule['sub_actions']) ? 1 : 0)) .' Sub Actions)</strong>'),
                        'icon' => 'fa fa-folder icon-state-success',
                        'state'=> $state,
                        'children' => []
                    ];
                    if(isset($selected[$ikey])){
                        $ichildren['state']['checked'] = $selected[$ikey];
                        $nChecked[] = $selected[$ikey];
                    } else {
                        $nChecked[] = false;
                    }
                    if(count($actionRule['sub_actions']) > 0 && !array_key_exists('view', $actionRule['sub_actions'])){
                        $defaultView = $key . '.'.$action.':view';
                        $defaultState = $state;
                        if(isset($selected[$defaultView])){
                            $defaultState['checked'] = $selected[$defaultView];
                            $nChecked[] = $selected[$defaultView];
                        } else {
                            $nChecked[] = false;
                        }
                        $ichildren['children'][] = [
                            'name' => $defaultView,
                            'text' => '<small><i>Default</i></small>',
                            'icon' => 'fa fa-check icon-state-info',
                            'state'=> $defaultState,
                            'children' => []
                        ];
                    }
                    foreach( $actionRule['sub_actions'] as $sub => $label ){
                        $ikey = $key . '.'.$action.':'.$sub;
                        $istate = $state;
                        if(isset($selected[$ikey])){
                            $istate['checked'] = $selected[$ikey];
                            $nChecked[] = $selected[$ikey];
                        } else {
                            $nChecked[] = false;
                        }
                        $ichildren['children'][] = [
                            'name' => $ikey,
                            'text' => '<small>'.$label.'</small>',
                            'icon' => 'fa fa-check icon-state-info',
                            'state'=> $istate,
                            'children' => []
                        ];
                    }
                    $children['children'][] = $ichildren;
                }
                $n['children'][] = $children;
            }
            $n['state']['checked'] = !in_array(false, $nChecked) ? true : false;
            $tree[] = $n;
        }

        return $tree;
    }

    /**
     * @param $what
     * @return string
     */
    private function displayAsMenu($what){
        if((isset($what['display']) && $what['display'] === true) || !isset($what['display'])){
            return "<span class=\"label label-sm label-info\">M</span> ";
        }
        return "";
    }
}
