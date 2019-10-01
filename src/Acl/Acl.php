<?php

namespace Klb\Core\Acl;

use Exception;
use Phalcon\Acl\Exception as AclException;
use Phalcon\Cache\Backend;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\User\Component;
use Phalcon\Acl\Adapter\Memory as AclMemory;
use Phalcon\Acl\Role as AclRole;
use Phalcon\Acl\Resource as AclResource;
use Kalbe\Model\Roles;

/**
 * Klb\Core\Acl\Acl
 */
class Acl extends Component
{

    /**
     * The ACL Object
     *
     * @var \Phalcon\Acl\Adapter\Memory
     */
    private $acl;

    /**
     * The file path of the ACL cache file.
     *
     * @var string
     */
    private $filePath;

    /**
     * Define the resources that are considered "private". These controller => actions require authentication.
     *
     * @var array
     */
    private $privateResources = [];

    /**
     * Human-readable descriptions of the actions used in {@see $privateResources}
     *
     * @var array
     */
    private $actionDescriptions = [
        'index'          => 'Access',
        'search'         => 'Search',
        'create'         => 'Create',
        'edit'           => 'Edit',
        'delete'         => 'Delete',
        'changePassword' => 'Change password',
    ];
    /**
     * @var string
     */
    private $module;
    /**
     * @var string
     */
    private $controller;
    /**
     * @var string
     */
    private $action;
    /**
     * @var string
     */
    private $separator = ':';
    /**
     * @var string
     */
    private $allowAll = '*';

    private $prefixCache = 'kalbe-acl-';

    private $keyCache = '';

    private $resourcesClass;
    /**
     * @var array
     */
    private $identity;
    /**
     * @var \Phalcon\Mvc\Dispatcher
     */
    private $dispatcher;

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return \Phalcon\Mvc\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param \Phalcon\Mvc\Dispatcher $dispatcher
     * @return Acl
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $moduleName = $dispatcher->getModuleName() ?: '*';
        $controllerName = $dispatcher->getControllerName();
        $actionName = $dispatcher->getActionName();
        $controllerReplace = [ 'Kalbe\\Controller\\' => '', 'Controller' => '', '/' => '\\' ];
        $replace = strtolower(str_replace(array_keys($controllerReplace), array_values($controllerReplace), $dispatcher->getControllerClass()));
        if(false !== \strpos($controllerName, '_')){
            $replace = \str_replace(\str_replace('_', '', $controllerName), $controllerName, $replace);
        }
        if ($replace !== $controllerName) {
            $controllerName = $replace;
        }
        $this
            ->setModule($moduleName)
            ->setController($controllerName)
            ->setAction($actionName);

        return $this;
    }


    /**
     * @param string $controller
     * @return Acl
     */
    public function setController($controller)
    {
        $this->controller = $this->normalizeController($controller);

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return Acl
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResourcesClass()
    {
        return $this->resourcesClass;
    }

    /**
     * @param mixed $resourcesClass
     */
    public function setResourcesClass($resourcesClass)
    {
        $this->resourcesClass = $resourcesClass;
    }

    /**
     * @return array
     */
    public function getIdentity()
    {
        return $this->identity ?: $this->getDI()->get('auth')->getIdentity();
    }

    /**
     * @param array $identity
     * @return Acl
     */
    public function setIdentity(array $identity)
    {
        $resourcesClass = $this->getResourcesClass();
        $this->identity = $identity;
        $this->keyCache = $resourcesClass::CACHE_KEY . $this->prefixCache . $identity['id'];

        return $this;
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @return string
     */
    public function getAllowAll()
    {
        return $this->allowAll;
    }

    /**
     * @return mixed
     */
    public function getModule()
    {
        return '*';
//        return $this->module;
    }

    /**
     * @param mixed $module
     * @return Acl
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Checks if a controller is private or not
     *
     * @param string $controllerName
     * @return boolean
     */
    public function isPrivate($controllerName)
    {
        $controllerName = $this->normalizeController($controllerName);

        return isset($this->privateResources[$this->getModule() . $this->getSeparator() . $controllerName . '.*:*']) || isset($this->privateResources[$this->getModule() . $this->getSeparator() . '*' . '.*:*']);
    }

    /**
     * @return mixed
     */
    public function isAdmin()
    {
        $role = $this->getIdentity();

        return intval($role['superadmin']) > 0;
    }

    /**
     * @param $n
     * @return string
     */
    private function _normalize($n)
    {
        if ($n === ($this->getAllowAll() . $this->getSeparator() . $this->getAllowAll())) {
            return '*';
        }

        return $n;
    }

    /**
     * @param string $subAction
     * @return bool
     */
    public function can($subAction = '*')
    {
        if (strpos($subAction, $this->getSeparator()) !== false) {
            return $this->isAllowedSource(
                $this->getModule() .
                $this->getSeparator() .
                $this->getController() .
                '.' .
                $subAction
            );
        }

        return $this->isAllowedSource(
            $this->getModule() .
            $this->getSeparator() .
            $this->getController() .
            '.' .
            $this->getAction() .
            $this->getSeparator() .
            $subAction
        );
    }

    /**
     * @param $controller
     * @return mixed
     */
    private function normalizeController($controller, $deep = false)
    {
        if (true === $deep) {
            $controller = str_replace('\\\\', '\\', $controller);
        }

        return strtolower($controller);
    }

    /**
     * Checks if the current role is allowed to access a resource
     *
     * @param string $controller
     * @param string $action
     * @return boolean
     */
    public function isAllowed($controller, $action)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $role = $this->identity['role'];

        $controller = $this->normalizeController($controller);

        $allow = $this->_aclAllowed($role, $this->getModule() . $this->getSeparator() . $this->getAllowAll(), $this->getAllowAll() . $this->getSeparator() . $this->getAllowAll());

        if ($allow) {
            return $allow;
        }

        $allow = $this->_aclAllowed($role, $this->getModule() . $this->getSeparator() . $controller, $this->getAllowAll() . $this->getSeparator() . $this->getAllowAll());

        if ($allow) {
            return $allow;
        }
        $allow = $this->_aclAllowed($role, $this->getModule() . $this->getSeparator() . $controller, $action . $this->getSeparator() . $this->getAllowAll());

        if ($allow) {
            return $allow;
        }

        return $this->_aclAllowed($role, $this->getModule() . $this->getSeparator() . $controller, $action . $this->getSeparator() . 'view');
    }

    /**
     * @param $role
     * @param $resource
     * @param $action
     * @return bool
     */
    private function _aclAllowed($role, $resource, $action)
    {

        $allow = $this->getAcl()->isAllowed($role, $this->_normalize($resource), $this->_normalize($action));
        if (!$allow) {
            di()->get('logger')->debug(json_encode([
                'resource' => $this->_normalize($resource),
                'action'   => $this->_normalize($action),
            ]));
        }

        return $allow;
    }

    /**
     * @param $source
     * @return bool
     */
    public function isAllowedSource($source)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $role = $this->identity['role'];
        $exp = explode('.', $source);
        $allow = $this->_aclAllowed($role, $exp[0], $exp[1]);
        $lallow = $allow ? 'true' : 'false';
//        di()->get('logger')->debug("ROLE: $role\tALLOW: $lallow\tSOURCE: $source");
        if (!$allow) {
            di()->get('logger')->debug(json_encode(compact('role', 'source', 'exp', 'allow')));
        }

        return $allow;
    }

    /**
     * Returns the ACL list
     *
     * @return \Phalcon\Acl\Adapter\Memory
     */
    public function getAcl()
    {
        // Check if the ACL is already created
        if (is_object($this->acl)) {
            return $this->acl;
        }
        if (null === ($acl = $this->getCacheData())) {
            $acl = $this->rebuild();
        }
        $this->acl = $acl;

        return $this->acl;
    }

    /**
     * @param $acl
     */
    public function saveCacheData($acl)
    {
        try {
            $resourcesClass = $this->getResourcesClass();
            $keyCache = $resourcesClass::CACHE_KEY;
            $keys = $this->cache()->queryKeys($keyCache);
            di()->get('logger')->log('info', 'DELETE ALL RESOURCE: ' . $keyCache . ' => ' . count($keys));
            if(!empty($keys)){
                foreach ( $keys as $key ){
                    di()->get('logger')->log('info', 'DELETE ALL RESOURCE: ' . $key);
                    $this->cache()->delete($key);
                }
            }

            if(!empty($this->keyCache)) {
                $this->cache()->save($this->keyCache, $acl);
            }

        } catch (\Exception $e) {
            di()->get('logger')->log('error', 'EXCEPTION: ' . $e->getMessage());
        }
    }

    /**
     * @return mixed|null
     */
    public function getCacheData()
    {
        $cache = $this->cache();
        $data = $cache->get($this->keyCache);
        if (empty($data)) {
            return null;
        }

        return is_string($data) ? unserialize($data) : $data;
    }

    /**
     * Returns the permissions assigned to a role
     *
     * @param Roles $role
     * @return array
     */
    public function getPermissions(Roles $role)
    {
        $permissions = [];
        foreach ($role->getPermissions() as $permission) {
            $permissions[$permission->resource . '.' . $permission->action] = true;
        }

        return $permissions;
    }

    /**
     * Returns all the resources and their actions available in the application
     *
     * @return array
     */
    public function getResources()
    {
        return $this->privateResources;
    }

    /**
     * Returns the action description according to its simplified name
     *
     * @param string $action
     * @return string
     */
    public function getActionDescription($action)
    {
        if (isset($this->actionDescriptions[$action])) {
            return $this->actionDescriptions[$action];
        } else {
            return $action;
        }
    }

    /**
     * Rebuilds the access list into a file
     *
     * @return \Phalcon\Acl\Adapter\Memory
     */
    public function rebuild()
    {
        $acl = new AclMemory();

        $acl->setDefaultAction(\Phalcon\Acl::DENY);

        // Register roles

        $roles = Roles::find([
            'active = :active:',
            'bind' => [
                'active' => 'Y',
            ],
        ]);

        foreach ($roles as $role) {
            $acl->addRole(new AclRole($role->name));
        }

        foreach ($this->privateResources as $resource => $actions) {
            $acl->addResource(new AclResource($actions[0]), $actions[1]);
        }
        // Grant access to private area to role Users
        foreach ($roles as $role) {
            // Grant permissions in "permissions" model
            foreach ($role->getPermissions() as $permission) {
                try {
                    $acl->allow($role->name, $this->_normalize($permission->resource), $this->_normalize($permission->action));
                } catch (AclException $e) {
                    di()->get('logger')->log('error', 'ACL-EXCEPTION: ' . $e->getMessage());
                } catch (Exception $e) {
                    di()->get('logger')->log('error', 'EXCEPTION: ' . $e->getMessage());
                }
            }

        }
        $this->saveCacheData($acl);

        /*
        $filePath = $this->getFilePath();

        if (touch($filePath) && is_writable($filePath)) {

            file_put_contents($filePath, serialize($acl));

            // Store the ACL in APC
            if (function_exists('apc_store')) {
                apc_store($this->keyCache, $acl);
            }
        } else {
            $this->flash->error(
                'The user does not have write permissions to create the ACL list at ' . $filePath
            );
        }*/

        return $acl;
    }

    /**
     * @return Backend
     */
    private function cache()
    {
        return $this->getDI()->get('cache');
    }

    /**
     * Set the acl cache file path
     *
     * @return string
     */
    protected function getFilePath()
    {
        if (!isset($this->filePath)) {
            $this->filePath = STORAGE_PATH . '/cache/acl/data.' . date('Ymd');
        }

        return $this->filePath;
    }

    /**
     * Adds an array of private resources to the ACL object.
     *
     * @param array $resources
     */
    public function addPrivateResources(array $resources)
    {
        if (count($resources) > 0) {
            foreach ($resources as $resource) {
                $keys = $resource['module'] . $this->getSeparator() . $resource['controller'] . '.' . $resource['action'] . $this->getSeparator() . $resource['subAction'];
                $this->privateResources[$keys] = [ $resource['module'] . $this->getSeparator() . $resource['controller'], $resource['action'] . $this->getSeparator() . $resource['subAction'] ];
                $this->actionDescriptions[$keys] = $resource['description'];
            }
            if (is_object($this->acl)) {
                $this->acl = $this->rebuild();
            }
        }
    }
}
