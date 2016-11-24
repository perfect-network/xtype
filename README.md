异云流控 2.0
===============

本程序采用ThinkPHP5架构，全新发布。无任何注入点，无任何漏洞，十分安全：

 + 在线人数及时显示
 + 管理灵活可靠
 + ~~~

> 异云VPN的运行环境要求PHP5.4以上。

详细开发文档参考 [xtype官网](http://c.xtype.cn/)

## 使用 git 安装 异云流控
~~~
git clone https://github.com/myxtype/xtype.git
~~~
> 因为目前 异云流控 几乎每天都会更新，建议大家采用git方式安装。

## 目录结构

初始的目录结构如下：

~~~
www  WEB部署目录（或者子目录）
├─README.md             README文件
├─LICENSE.txt           授权说明文件
├─web           		流控网页应用
│  ├─common             公共模块目录（可以更改）
│  ├─runtime            应用的运行时目录（可写，可定制）
│  ├─module_name        模块目录
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─ ...            更多类库目录
│  │
│  ├─common.php         公共函数文件
│  ├─config.php         公共配置文件
│  ├─route.php          路由配置文件
│  └─database.php       数据库配置文件
├─api           		APP和机器人接口应用
│  ├─common             公共模块目录（可以更改）
│  ├─runtime            应用的运行时目录（可写，可定制）
│  ├─module_name        模块目录
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─ ...            更多类库目录
│  │
│  ├─common.php         公共函数文件
│  ├─config.php         公共配置文件
│  ├─route.php          路由配置文件
│  └─database.php       数据库配置文件
│
├─public                WEB目录（对外访问目录）
│  ├─static          	静态文件目录
│  ├─tools          	探针工具目录
│  ├─phpMyAdmin         数据管理工具目录
│  ├─install          	流控安装目录
│  ├─index.php          WEB入口文件
│  ├─api.php          	API入口文件
│  └─.htaccess          用于apache的重写
│
├─thinkphp              框架系统目录
│  ├─lang               语言文件目录
│  ├─library            框架类库目录
│  │  ├─think           Think类库包目录
│  │  └─traits          系统Trait目录
│  │
│  ├─mode               应用模式目录
│  ├─tpl                系统模板目录
│  ├─tests              单元测试文件目录
│  ├─base.php           基础定义文件
│  ├─console.php        控制台入口文件
│  ├─convention.php     框架惯例配置文件
│  ├─helper.php         助手函数文件
│  ├─phpunit.xml        phpunit配置文件
│  └─start.php          框架入口文件
│
├─extend                扩展类库目录
├─vendor                第三方类库目录（Composer依赖库）
~~~

异云流控 遵循Apache2开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2006-2016 by xtype (http://xtype.cn)

All rights reserved。

XType® 商标和著作权所有者为中国异科技有限公司。

更多细节参阅 [LICENSE.txt](LICENSE.txt)