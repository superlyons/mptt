MPTT; Modified Preorder Tree Traversal PHP Code Implement.

1.基于superlyons\idGenerator生成ID

2.以行为方式提供MPTT特性,以及MPTT的具体模型类
	./components/NestedSetBehavior.php
	./models/MpttNode.php
	./models/MpttSearch.php

3.实现了对MPTT可操作的管理界面
	./controllers/*
	./views/*

4.实现了基于rbac的route权限节点查询助手类
	./components/MpttRbacRoutesHelper.php

5.提供了MPTT的选择部件(Widget+Action)
	./widgets/SelectTreeInput.php
	./widgets/views/*
	./components/SelectTreeAjaxAction.php

6.中文支持
	./messages/*