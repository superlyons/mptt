MPTT Manage for Yii 2
======================
GUI Manage MPTT (Modified Preorder Tree Traversal) for Yii2. :smile:.

> 这是**&lt;Joe Celko's Trees And Hierarchies In SQL For Smarties>**一书中第4章* Nested Set Model Of Hierarchis *的一个实现

![Mptt Nested](/images/1.jpg)
![Mptt Tree](/images/2.jpg)

学名: 预排序遍历树算法(modified preorder tree traversal algorithm)

几乎每个应用都离不开"树"数据结构, Mptt的优点在于可以非常快速的查找嵌套层级关系, 而无惧层级的深度!

这是一个Yii2-Extension, 它不仅实现了MPTT算法还提供了:
*	对Mptt树的完整管理: 增删改查 和 Mptt树视图
*	提供了Mptt Widget类. 以方便你在别处使用! 
*	提供支持Yii RBAC的助手类, 将Mptt与权限结合. 例如你可以很方便的返回当前登录用户可访问的Mptt节点
*	使用`superlyons\idGenerator`生成ID

你可以在Github上找到类似的实现, 但是它们仅仅实现了算法(通过Behavior), 没有提供完善的管理和应用功能, 要想将它们应用到你的项目中还需要做大量的工作.
而且算法有一些问题, 例如只更新当前节点实例(例如改变lft和rgt值)而不考虑其它已获得的节点实例, 你必须很小心的使用否则将导致错误! 而在本扩展中没有此类问题.

Installation
------------

### Install With Composer

安装这个扩展的首选方式是通过 [composer](http://getcomposer.org/download/). 

```
php composer.phar require superlyons/mptt "dev-master"
```
或者, 你也可以添加下面的代码到你的`composer.json`文件中

```
"superlyons/mptt": "dev-master"
```

### Install MpttTable

```
yii migrate --migrationPath=@superlyons/mptt/migrations
```
