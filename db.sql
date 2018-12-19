

--
-- 表的结构 `t_api_logs`
--

CREATE TABLE `t_api_logs` (
  `id` int(11) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `mid` int(11) NOT NULL DEFAULT '0',
  `duration` float(11,2) DEFAULT '0.00',
  `request_time` double(15,4) DEFAULT NULL,
  `response_time` double(15,4) DEFAULT '0.0000',
  `user_ip` varchar(15) DEFAULT NULL,
  `params` text,
  `result` mediumtext,
  `redis_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='API日志';

-- --------------------------------------------------------

--
-- 表的结构 `t_menu`
--

CREATE TABLE `t_menu` (
  `id` int(11) NOT NULL,
  `name` varchar(40) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '' comment '菜单地址',
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `sort_by_weight` int(11) NOT NULL DEFAULT '0' COMMENT '显示顺序',
  `display` enum('1','0') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `t_role`
--

CREATE TABLE `t_role` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `name` varchar(40) NOT NULL COMMENT '角色名称',
  `role_type` tinyint(1) NOT NULL DEFAULT '3' COMMENT '角色类型：1管理员,2基础角色',
  `assign_policy` tinyint(1) NOT NULL DEFAULT '0' COMMENT '自动分配：0不自动,1自动给登陆会员,2自动给未登陆会员',
  `priority` smallint(6) NOT NULL DEFAULT '0',
  `default_allow` tinyint(4) DEFAULT '0' COMMENT '默认权限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色表';

-- --------------------------------------------------------

--
-- 表的结构 `t_role_menu`
--

CREATE TABLE `t_role_menu` (
  `rid` int(11) NOT NULL COMMENT '角色id',
  `mid` int(11) NOT NULL COMMENT '菜单id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色菜单分配表';

-- --------------------------------------------------------

--
-- 表的结构 `t_role_res`
--

CREATE TABLE `t_role_res` (
  `id` int(11) NOT NULL,
  `rid` int(11) NOT NULL COMMENT '角色id',
  `rescode` varchar(50) NOT NULL COMMENT '资源code',
  `opcode` varchar(50) DEFAULT NULL COMMENT '操作code',
  `is_allow` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否允许'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色资源分配表';

-- --------------------------------------------------------

--
-- 表的结构 `t_role_user`
--

CREATE TABLE `t_role_user` (
  `id` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色用户分配表';

-- --------------------------------------------------------

--
-- 表的结构 `t_sendmsg_logs`
--

CREATE TABLE `t_sendmsg_logs` (
  `id` int(11) NOT NULL,
  `msg_to` varchar(255) NOT NULL DEFAULT '' COMMENT '消息接收者',
  `msg_body` text COMMENT '消息内容',
  `msg_id` varchar(64) DEFAULT '' COMMENT '消息id',
  `msg_sender` varchar(40) DEFAULT '' COMMENT '消息发送者',
  `error_info` varchar(255) DEFAULT '' COMMENT '结果提示信息',
  `send_state` int(11) DEFAULT '1' COMMENT '发送结果状态值',
  `send_time` int(11) DEFAULT '0' COMMENT '发送时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='发送的消息日志';

-- --------------------------------------------------------

--
-- 表的结构 `t_sysparams`
--

CREATE TABLE `t_sysparams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '参数名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '参数描述',
  `keyname` varchar(100) NOT NULL DEFAULT '' COMMENT 'Key名',
  `value_type` tinyint(4) DEFAULT '1' COMMENT '1字符串2布尔值3日期4数字',
  `current_value` varchar(255) NOT NULL DEFAULT '' COMMENT '当前值'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统配置';

-- --------------------------------------------------------

--
-- 表的结构 `t_user`
--

CREATE TABLE `t_user` (
  `uid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(70) NOT NULL DEFAULT '' COMMENT '密码',
  `mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT 'EMAIL',
  `create_time` int(11) DEFAULT '0' COMMENT '注册时间',
  `last_visit_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '最近一次访问IP',
  `last_visit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最近一次访问时间',
  `gender` tinyint(4) NOT NULL DEFAULT '1' COMMENT '性别',
  `realname` varchar(50) null default '' comment '姓名',
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

--
-- 转存表中的数据 `t_user`
--

INSERT INTO `t_user` (`uid`, `username`, `password`, `mobile`, `email`,  `create_time`, `last_visit_ip`, `last_visit_time`, `gender`) VALUES
(1, 'admin', '$2y$10$n2Vz/L5Fa9C1rA14Go5KkOShcivgDQCSq8G0UkepfrP2OIrsAUh.O', '13000011111', 'dony@larkair.com',  NULL, '', 1512526971, 1),
(4, 'dony', '$2y$10$s.taaqnsgE1UNNBuIrbN5.ffR6cVymro96foLShQT1FQIMiNeFgIK', '15000333999', 'dony@tapy.org', 1405906107, '127.0.0.1', 1513663170, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `t_api_logs`
--
ALTER TABLE `t_api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_time` (`request_time`),
  ADD KEY `redis_id` (`redis_id`),
  ADD KEY `mid` (`mid`);

--
-- Indexes for table `t_menu`
--
ALTER TABLE `t_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_role`
--
ALTER TABLE `t_role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `priority` (`priority`);

--
-- Indexes for table `t_role_menu`
--
ALTER TABLE `t_role_menu`
  ADD PRIMARY KEY (`rid`,`mid`);

--
-- Indexes for table `t_role_res`
--
ALTER TABLE `t_role_res`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rid` (`rid`,`rescode`,`opcode`);

--
-- Indexes for table `t_role_user`
--
ALTER TABLE `t_role_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`uid`),
  ADD KEY `rid` (`rid`);

--
-- Indexes for table `t_sendmsg_logs`
--
ALTER TABLE `t_sendmsg_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_sysparams`
--
ALTER TABLE `t_sysparams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_user`
--
ALTER TABLE `t_user`
  ADD PRIMARY KEY (`uid`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `t_api_logs`
--
ALTER TABLE `t_api_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `t_menu`
--
ALTER TABLE `t_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `t_role`
--
ALTER TABLE `t_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';
--
-- 使用表AUTO_INCREMENT `t_role_res`
--
ALTER TABLE `t_role_res`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `t_role_user`
--
ALTER TABLE `t_role_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `t_sendmsg_logs`
--
ALTER TABLE `t_sendmsg_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `t_sysparams`
--
ALTER TABLE `t_sysparams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `t_user`
--
ALTER TABLE `t_user`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

drop table if exists `t_mall_marketcatalogs`;
create table `t_mall_marketcatalogs`(
  `id` int not null auto_increment,
  `name` varchar(100) not null comment '类目名称',
  `parent_id` int not null default 0 comment '上级类目ID',
  `create_time` int not null default 0 comment '创建时间',
  `left_position` int not null default 0 comment '左边位',
  `right_position` int not null default 0 comment '右边位',
  `sort_weight` int not null default 0 comment '显示权重',
  primary key(`id`),
  index(`parent_id`),
  index(`left_position`,`right_position`,`sort_weight`)
)comment='前台类目表';


drop table if exists  `t_mall_itemcatalogs`;
create table `t_mall_itemcatalogs`(
  `id` int not null auto_increment,
  `name` varchar(100) not null comment '类目名称',
  `parent_id` int not null default 0 comment '上级类目ID',
  `left_position` int not null default 0 comment '左边位',
  `right_position` int not null default 0 comment '右边位',
  `sort_weight` int not null default 0 comment '显示权重',
  `propset_id` int not null default 0 comment '使用的属性模板',
  `is_deleted` tinyint default 0 comment '是否删除',
  `create_time` int not null default 0 comment '创建时间',
  `update_time` int not null default 0 comment '修改时间',
  primary key(`id`),
  index(`parent_id`,`is_deleted`),
  index(`propset_id`),
  index(`left_position`,`right_position`,`sort_weight`)
)comment='后台类目表';

drop table if exists `t_mall_marketcatalog_mapping`;
create table `t_mall_marketcatalog_mapping`(
  `id` int not null auto_increment,
  `market_catalog_id` int not null comment '前台类目id',
  `item_catalog_id` int not null comment '后台类目id',
  primary key(`id`),
  unique(`market_catalog_id`,`item_catalog_id`)
)comment='前后台类目对应';

drop table if exists `t_mall_propset`;
create table `t_mall_propset`(
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) not null comment '属性模板名称',
  `is_deleted` tinyint default 0 comment '是否删除',
  `create_time` int not null default 0 comment '创建时间',
  `update_time` int not null default 0 comment '修改时间',
  primary key(`id`)
)comment='属性模板';

drop table if exists `t_mall_propset_keys`;
create table `t_mall_propset_keys`(
  `id` int(11) NOT NULL auto_increment,
  `propset_id` int not null comment '属性集id',
  `propkey_id` int not null comment '属性名称id',
  `used_for_search` tinyint default 0 comment '是否应用于搜索',
  `is_sale_prop` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否是决定sku的销售属性',
  `is_apply_code` tinyint(1) NOT NULL comment '是否应用于编码',
  `disabled` tinyint not null default 0 comment '禁用',
  `sort_weight` int not null default 0 comment '显示权重',
  `is_required` tinyint not null default 0 comment '是否必填',
  primary key(`id`),
  index(`propset_id`,`propkey_id`)
)comment='属性集合的属性列表';


drop table if exists `t_mall_propkey`;
CREATE TABLE `t_mall_propkey` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) not null comment '属性名称',
  `form_type` tinyint DEFAULT 0 comment '表单控件形式',
  `is_color` tinyint default 0 comment '是否是颜色',
  `summary` varchar(200) DEFAULT NULL COMMENT '描述',
  `is_deleted` tinyint default 0 comment '是否删除',
  `create_time` int not null default 0 comment '创建时间',
  `update_time` int not null default 0 comment '修改时间',
  primary key(`id`),
  index(`is_deleted`)
) COMMENT='类目属性';

drop table if exists `t_mall_propvalue`;
CREATE TABLE `t_mall_propvalue` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(40) NOT NULL COMMENT '编码',
  `propkey_id` int(11) NOT NULL COMMENT '属性id',
  `sort_weight` int(11) DEFAULT NULL,
  `propvalue` varchar(200) DEFAULT NULL,
  `color_hex_value` varchar(30) default '' comment '颜色16进制值，最多4组，可以逗号分隔',
  `is_deleted` tinyint default 0 comment '是否删除',
  `create_time` int not null default 0 comment '创建时间',
  `update_time` int not null default 0 comment '修改时间',
  primary key(`id`),
  index(`code`),
  index(`propkey_id`,`is_deleted`),
  index(`sort_weight`)
) COMMENT='属性值';