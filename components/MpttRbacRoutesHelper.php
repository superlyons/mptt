<?php

namespace superlyons\mptt\components;

use Yii;
use yii\base\InvalidConfigException;
use superlyons\mptt\models\MpttNode;
use yii\helpers\Url;

class MpttRbacRoutesHelper{

	public static function getRoutePermissionsByUser($userID, &$routes){
		$routes = (array) $routes;
		$manager = Yii::$app->getAuthManager();
		if( $manager == null ) {
			 throw new InvalidConfigException("Call getAssignedRouteNodes() Must Config AuthManager Component!");
		}
        //获得$userId的所有Route Permissions, 如果以/*结尾则去掉*
        if ($userId !== null) {
            foreach ($manager->getPermissionsByUser($userId) as $name => $value) {
                if ($name[0] === '/') {
                    $routes[] = $name;
                }elseif($name == 'http'){
                	$routes[] = $name;
                }
            }
        }
        //获得所有默认权限的Route Permissions, 如果以/*结尾则去掉*
        foreach ($manager->defaultRoles as $role) {
            foreach ($manager->getPermissionsByRole($role) as $name => $value) {
                if ($name[0] === '/') {
                    $routes[] = $name;
                }elseif($name == 'http'){
                	$routes[] = $name;
                }
            }
        }
        $routes = array_unique($routes);
        return true;
        /* 
        routes存储所有为userId分配的 Route Permissions
        */
	}

	public static function getRoutePermissionsByUser_test($userID, &$routes){
			$routes[]="/language/java/*"; 
			$routes[]="/language/php/ci";
			$routes[]="/language/php/yii";
			$routes[]='/database/mysql';
			$routes[]="/any/node";
			$routes[]="http";
			//$routes=[];
	}

	public static function getAssignedRouteNodes($userId, $roots = null, $tidy=true, $callback = null, $columns = null, $refresh = false){
		$rootstr =  $roots==null ? 'null' : implode(" ; ",$roots);
		$token = "userid=".$userId." ; roots = ".$rootstr;
		Yii::beginProfile($token, __METHOD__);
		if($columns == null){
			$columns = ["id","lft","rgt","parent","name","value","data","root","level"];
		}else{
			$columns = array_unique(array_merge((array)$columns, ["id","lft","rgt","parent","name","value","data"]));
		}
		
		$routes=[];
		$result = static::getRoutePermissionsByUser_test($userId, $routes);

		if(empty($routes)){
			return false;
		}

		if($roots == null){
			$rootnodes = MpttNode::find()->select($columns)->where("[[lft]] = 1")->all();
		}else{
			$roots = (array)$roots;
			$rootnodes = MpttNode::find()->select($columns)->where(['id'=>$roots])->all();
		}

		$assigneds=[];
		$nodes=[];
		$parents=[];
		foreach($rootnodes as $rootnode){
			if($rootnode['parent'] == 0){
				$parents[$rootnode['id']] = 0;
			}else{
				$parents[$rootnode['id']] = $rootnode['parent'];
			}
			$nodes[$rootnode['id']] = MpttNode::find()->select($columns)->andWhere(['>=', 'lft', $rootnode['lft']])
				->andWhere(['<=', 'rgt', $rootnode['rgt']])->andWhere(['root'=>$rootnode['root']])->orderBy('lft')->asArray()->all();
			if( count($nodes[$rootnode['id']]) > 0 ){
				Yii::beginProfile($token, "_requiredParent");
				$assigneds[$rootnode['id']] = static::requiredParent($routes, $nodes[$rootnode['id']]);
				Yii::endProfile($token, "_requiredParent");
			}
		}

		if($tidy){
			foreach($assigneds as $key => $value){
				Yii::beginProfile($token, "_convertToArray");
				$assigneds[$key] = static::convertToArray($value, $callback, $parents[$key]);
				Yii::endProfile($token, "_convertToArray");
			}
		}
		Yii::endProfile($token, __METHOD__);
		return $assigneds;

	}
	private static function requiredParent(&$routes, &$nodes){
		$result=[];
		$nodekeys = array_flip(array_keys($nodes)); 

		foreach($nodes as $key => $node){
			//是route节点
			if ($node['value'][0] === '/' || stripos(trim($node['value']), 'http') === 0) {
				//当前route节点是授权的
				if ( in_array($node['value'], $routes) || static::inRoutes($node['value'], $routes) ){
					$current = $result[$node['lft']] = $node;
					unset($nodekeys[$key]);
					foreach($nodekeys as $inx => $val){ //foreach不受unset影响 见/test/test.php
						if( $nodes[$inx]['lft'] < $current['lft'] && $nodes[$inx]['rgt'] > $current['rgt'] ){
							$result[$nodes[$inx]['lft']] = $nodes[$inx];
							unset($nodekeys[$inx]);
						}
					}
				}
            }
		}
		ksort($result);
		return $result;
	}

	private static function inRoutes($value, &$routes){
		foreach ($routes as $route) {
			if (substr($route, -2) === '/*') {
				$name = substr($route, 0, -2);
			}else{
				$name = $route;
			}
			if( stripos(trim($value), $name) === 0 ){
				return true;
			}
		}
		return false;
	}

	public static function convertToArray(&$assigned, $callback, $parent=0){
		$result = [];
		$order = [];
        foreach($assigned as $node){
        	if($node['parent'] == $parent){
        		$children = static::convertToArray($assigned, $callback, $node['id']);
        		if($callback !== null){
        			$item = call_user_function($callback, $node, $children);
        		}else{
	        		$item = [
	        			'id' => $node['id'],
	        			'label' => $node['name'],
	        			'data'	=> $node['data'],
	        			'url'	=> static::parseRoute($node['value']),
	        		];
	        		if($children != []){
	        			$item['items'] = $children;
	        		}
	        	}
        		$result[] = $item;
        		$order[] = $node['lft'];
        	}
        }
        if($result != []){
        	array_multisort($order, $result);
        }
        return $result;
	}

    public static function parseRoute($route){
        if (!empty($route)) {
        	if(stripos(trim($route), 'http') === 0){
        		return $route;
        	}else{
	            $url = [];
	            $author=false;
	            if( ($i = strpos($route, "#")) !== false){
	            	$author = mb_substr ($route, $i+1);
	            	$route = mb_substr ($route,0,$i);
	            }
	            $r = explode('?', $route);
	            $url[0] = $r[0];
	            if($author) $url['#'] = $author;
	            if(count($r) > 1){
		            $r = explode('&', $r[1]);
		            foreach ($r as $part) {
		                $part = explode('=', $part);
		                $url[$part[0]] = isset($part[1]) ? $part[1] : '';
		            }
	            }
	            return $url;
	        }       
        }
        return '#';
    }
}