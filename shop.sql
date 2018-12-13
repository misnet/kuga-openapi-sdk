drop table if exists `t_order`;
CREATE TABLE `t_order` (
  `id` int(11) NOT NULL auto_increment,
  `mid` int(11) DEFAULT NULL COMMENT '会员id',
  `pay_amount` float(15,2) DEFAULT '0.00' COMMENT '总支付',
  `status` tinyint(4) DEFAULT '0' COMMENT '订单状态:0未核，1已核实，2已结帐，3已取消，4已关闭',
  `create_time` int(11) NOT NULL COMMENT '下单时间',
  `review_time` int(11) DEFAULT NULL COMMENT '核实时间',
  `pay_time` int(11) DEFAULT '0' COMMENT '支付时间',
  `close_time` int(11) DEFAULT '0' COMMENT '关闭时间',
  `store_id` int(11) DEFAULT '0' COMMENT '订单发生门店',
  `notes` varchar(200) DEFAULT NULL,
  `sn` varchar(20) DEFAULT NULL comment '订单编号',
  `cancel_time` int(11) DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `ship_fee` float(5,2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  `ship_time` int(11) NOT NULL COMMENT '发货时间',
  `products_fee` float(10,2) NOT NULL COMMENT '实付商品总价',
  `receive_time` int(11) DEFAULT NULL COMMENT '收货时间',
  PRIMARY KEY (`id`),
  index (`mid`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单表';

drop table if exists `t_order_extra`;
CREATE TABLE `t_order_extra` (
  `id` int(11) NOT NULL auto_increment,
  `order_id` bigint(19) UNSIGNED NOT NULL,
  `consignee` varchar(20) NOT NULL COMMENT '收件人',
  `country_id` int(10) UNSIGNED NOT NULL COMMENT '国家id',
  `province_id` int(10) UNSIGNED NOT NULL COMMENT '省份id',
  `city_id` int(10) UNSIGNED NOT NULL COMMENT '城市id',
  `region_name` varchar(100) NOT NULL COMMENT '地区名',
  `address` varchar(255) NOT NULL COMMENT '详细地址',
  `phone` varchar(60) NOT NULL COMMENT '电话号码',
  `ship_id` int(10) UNSIGNED NOT NULL COMMENT '物流公司id',
  `memo` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `express_number` varchar(40) NOT NULL DEFAULT '' COMMENT '运单号',
  `invoice_title` varchar(200) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `ship_fee` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '物流费用',
  primary key(`id`),
  index(`order_id`),
  index(`ship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单物流信息';

drop table if exists `t_order_items`;
CREATE TABLE `t_order_items` (
  `id` int(11) NOT NULL auto_increment,
  `order_id` int(11) NOT NULL COMMENT '订单id',
  `product_id` int(11) NOT NULL COMMENT '商品id',
  `qty` int(11) NOT NULL COMMENT '数量',
  `product_price` float(15,2) NOT NULL DEFAULT '0.00' COMMENT '商品销售价',
  `pay_price` float(15,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付价',
  `product_name` varchar(200) NOT NULL,
  `sku_name` varchar(200) NOT NULL COMMENT 'SKU名称',
  `sku_sn` varchar(30) NOT NULL COMMENT 'SKU编号',
  `sku_json` varchar(300) NOT NULL COMMENT 'sku对象集合json',
  `store_id` int(11) NOT NULL COMMENT '店仓id',
  PRIMARY key(`id`),
  index(`order_id`),
  index(`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单明细表';

drop table if exists `t_order_payments`;
CREATE TABLE `t_order_payments` (
  `id` int(11) NOT NULL auto_increment,
  `payway_id` int(11) NOT NULL COMMENT '对应付款方式id',
  `order_id` int(11) NOT NULL COMMENT '对应订单id',
  `pay_amount` float(15,2) DEFAULT '0.00' COMMENT '支付金额',
  PRIMARY key(`id`),
  index(`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单付款明细';

drop table if exists `t_order_statuslogs`;
CREATE TABLE `t_order_statuslogs` (
  `id` int(11) NOT NULL auto_increment,
  `order_id` int(11) NOT NULL COMMENT '订单id',
  `from_status` int(11) NOT NULL COMMENT '订单变化前状态',
  `to_status` int(11) NOT NULL COMMENT '订单变化后状态',
  `change_time` int(11) NOT NULL COMMENT '订单变化时间',
  primary key(`id`),
  index(`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单状态变化表';

drop table if exists `t_products`;
CREATE TABLE `t_products` (
  `id` int(11) NOT NULL auto_increment,
  `product_name` varchar(180) NOT NULL DEFAULT '' COMMENT '品名',
  `measure_id` varchar(200) NOT NULL DEFAULT '' COMMENT '单位',
  `isrelease` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否发布,1是,0不是',
  `shortdesc` varchar(500) DEFAULT NULL COMMENT '产品摘要',
  `price` float(15,2) NOT NULL DEFAULT '0.00' COMMENT '标准价',
  `promotion_price` float(15,2) DEFAULT '0.00' COMMENT '特价',
  `precost` float(15,2) DEFAULT '0.00' COMMENT '成本',
  `isgift` tinyint(4) DEFAULT '1' COMMENT '是否赠品',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `modify_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `display_weight` int(11) DEFAULT '0' COMMENT '排序权重',
  `barcode` varchar(60) DEFAULT NULL COMMENT '商品编码',
  `code_rule_id` int(11) DEFAULT NULL COMMENT '编码规则id',
  `origin_barcode` varchar(60) DEFAULT NULL COMMENT '原厂编码',
  `flow_no` int(11) DEFAULT NULL COMMENT '流水号',
  `specset_id` int(11) NOT NULL,
  primary key(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品档案';


--
-- 表的结构 `t_products_cates`
--
drop table if exists `t_products_cates`;
CREATE TABLE `t_products_cates` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(60) NOT NULL COMMENT '分类名',
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `display_weight` int(11) NOT NULL DEFAULT '0' COMMENT '显示权重',
  `left_position` int(11) NOT NULL DEFAULT '0' COMMENT '左位置',
  `right_position` int(11) NOT NULL DEFAULT '0' COMMENT '右位置',
  `isactive` int(11) NOT NULL DEFAULT '1' COMMENT '是否激活',
  `img_url` varchar(200) DEFAULT NULL COMMENT '图',
  primary key(`id`),
  index(`parent_id`),
  index(`left_position`,`right_position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品分类';

drop table if exists `t_products_cates_mapping`;
CREATE TABLE `t_products_cates_mapping` (
  `id` int(11) NOT NULL auto_increment,
  `product_id` int(11) NOT NULL COMMENT '商品id',
  `cate_id` int(11) NOT NULL COMMENT '分类id',
  PRIMARY key(`id`),
  index(`product_id`),
  index(`cate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品分类对应id';

drop table if exists `t_products_coderule`;
CREATE TABLE `t_products_coderule` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(60) NOT NULL COMMENT '规则名称',
  `specsid` int(11) NOT NULL COMMENT '类目id',
  `prefix` varchar(10) DEFAULT '' COMMENT '前辍字串',
  `current_flow_no` int(11) DEFAULT '0' COMMENT '当前流水号',
  `item_json` varchar(255) DEFAULT '' COMMENT '规则项',
  PRIMARY key(`id`),
  index(`specsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品编码规则';

drop table if exists `t_products_desc`;
CREATE TABLE `t_products_desc` (
  `id` int(11) NOT NULL  COMMENT '对应产品的id',
  `content` mediumtext NOT NULL COMMENT '产品描述',
  primary key(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品描述表';

drop table if exists `t_products_imgs`;
CREATE TABLE `t_products_imgs` (
  `id` int(11) NOT NULL auto_increment,
  `product_id` int(11) NOT NULL COMMENT '对应产品的id',
  `is_first` tinyint(11) NOT NULL COMMENT '封面',
  `imgurl` varchar(180) NOT NULL DEFAULT '' COMMENT '图片',
  primary key(`id`),
  index(`product_id`,`is_first`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品缩略图';
drop table if exists `t_products_sku`;

create table `t_products_sku`(
  `id` int(11) NOT NULL auto_increment,
  `product_id` int(11) NOT NULL COMMENT '对应产品的id',
  `price` float(15,2) DEFAULT NULL COMMENT '价格',
  `cost` float(15,2) DEFAULT NULL COMMENT '成本',
  `promotion_price` float(15,2) DEFAULT NULL COMMENT '促销价',
  `sku_sn` varchar(64) not null comment 'SKU编号',
  `sku_json` text not null comment 'SKU对象JSON串',
  primary key(`id`),
  index(`product_id`,`sku_sn`)
)comment='商品SKU定义表';

drop table if exists `t_products_inventory`;
CREATE TABLE `t_products_inventory` (
  `id` int(11) NOT NULL auto_increment,
  `product_id` int(11) NOT NULL COMMENT '对应产品的id',
  `store_id` int(11) NOT NULL COMMENT '店仓id',
  `stock_qty` int(11) NOT NULL COMMENT '实际库存数量',
  `preout_qty` int(11) NOT NULL COMMENT '在单数量',
  `prein_qty` int(11) NOT NULL COMMENT '将要入库数量',
  `sku_sn` varchar(60) DEFAULT NULL COMMENT 'sku条型码',
  primary key(`id`),
  index(`product_id`),
  index(`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品库存信息';

drop table if exists `t_products_propset_defined`;
CREATE TABLE `t_products_propset_defined` (
  `id` int(11) NOT NULL auto_increment,
  `prop_set_id` int(11) NOT NULL COMMENT '集合id',
  `prop_value` varchar(200) NOT NULL COMMENT '名称',
  `prop_value_alias` varchar(200) DEFAULT NULL COMMENT '别名',
  `prop_sn` varchar(40) NOT NULL COMMENT '编码',
  `shortdesc` varchar(200) DEFAULT NULL COMMENT '描述',
  `display_weight` int(11) DEFAULT NULL,
  primary key(`id`),
  index(`prop_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='属性集与值定义';


drop table if exists `t_products_props_defined`;
CREATE TABLE `t_products_props_defined` (
  `id` int(11) NOT NULL auto_increment,
  `spec_set_id` int(11) NOT NULL COMMENT '规格集合id',
  `prop_set_id` int(11) NOT NULL COMMENT '属性或属性集id',
  `is_sale_prop` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否是决定sku的关键属性',
  `form_type` varchar(20) DEFAULT NULL,
  `is_apply_code` tinyint(1) NOT NULL,
  `display_weight` int(11) NOT NULL DEFAULT '0' COMMENT '顺序权重',
  primary key(`id`),
  index(`specsetid`,`prop_set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类目的属性定义';

drop table if exists `t_products_prop_mapping`;
create table `t_products_prop_mapping`(
  `id` int(11) NOT NULL auto_increment,
  `product_id` int(11) not null comment '产品ID'
  `prop_set_id` int not null comment '属性集ID'
  `prop_set_name` varchar(64) not null comment '属性集名称'
  `prop_value_id` int not null comment '属性值ID',
  `prop_value` varchar(64) null comment '属性值',
  primary key(`id`),
  index(`product_id`)
)comment='商品各项自定义属性';

drop table if exists `t_products_specs_set`;
create table `t_products_specs_set`(
  `id` int not null auto_increment,
  `name` varchar(200) not null,
  primary key(`id`)
)comment='类目';

drop table if exists `t_address`;
CREATE TABLE `t_address` (
  `id` int(11) NOT NULL auto_increment,
  `receiver` varchar(60) NOT NULL COMMENT '收货人',
  `phone` varchar(30) NOT NULL COMMENT '收货人联系电话',
  `address` varchar(200) DEFAULT NULL COMMENT '收货详细地址',
  `province_id` int(11) NOT NULL COMMENT '省份',
  `city_id` int(11) NOT NULL COMMENT '城市',
  `mid` int(11) DEFAULT NULL COMMENT '会员id',
  `is_default` tinyint(1) NOT NULL,
  `last_used_time` int(11) DEFAULT NULL COMMENT '最后一次使用时间',
  `country_id` int(11) NOT NULL DEFAULT '1' COMMENT '国家id',
  primary key(`id`),
  index(`country_id`,`province_id`,`city_id`),
  index(`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

drop table if exists `t_cart`;
CREATE TABLE `t_cart` (
  `id` int(11) NOT NULL auto_increment,
  `mid` int(11) DEFAULT '0' COMMENT '会员id',
  `oauth_id` varchar(100) DEFAULT '' COMMENT '会员oauthid',
  `app_id` int(11) DEFAULT '0' COMMENT '应用id',
  `product_id` int(11) NOT NULL COMMENT '商品id',
  `qty` int(11) NOT NULL DEFAULT '1' COMMENT '商品数量',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '添加至购物车时间',
  `sn` varchar(20) NOT NULL,
  `store_id` int(11) NOT NULL COMMENT '店仓id',
  primary key(`id`),
  index(`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='购物车';

drop table if exists `t_shipping`;
CREATE TABLE `t_shipping` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(40) DEFAULT '' COMMENT '快递编号',
  `name` varchar(40) DEFAULT '' COMMENT '快递',
  `is_default` enum('1','0') NOT NULL DEFAULT '0' COMMENT '为默认快递',
  `description` varchar(200) DEFAULT NULL COMMENT '描述',
  primary key(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='快递表';

drop table if exists `t_shipping_template`;
CREATE TABLE `t_shipping_template` (
  `id` int(11) NOT NULL auto_increment,
  `shipping_id` int(11) NOT NULL COMMENT '对应快递id',
  `from_country_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应国家id',
  `from_province_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应省份id',
  `from_city_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应城市id',
  `to_country_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应国家id',
  `to_province_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应省份id',
  `to_city_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应城市id',
  `cost_price` float(15,2) DEFAULT '0.00' COMMENT '成本价',
  `sell_price` float(15,2) DEFAULT '0.00' COMMENT '公开价',
  `cash_on_delivery` enum('1','0') NOT NULL DEFAULT '0' COMMENT '是否支持货到付款:1是,0否',
  primary key(`id`),
  index(`shipping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='快递模板表';

#类目说明#
类目：服装
属性名：
  颜色：红、黄、绿
  尺码：S、L、XL、M、XXL
  年份：2018
  面料：棉花
  风格：潮牌、90后、淑女
属性值：
  属性名：颜色，值：红，编码：03


drop table if exists `t_mall_catalog`;
create table `t_mall_catalog`(
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(10) NOT NULL COMMENT '编码',
  `name` varchar(100) NOT NULL comment '名称',
  `create_time` int not null comment '创建时间',
  primary key(`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类目表';

drop table if exists `t_mall_prop_name`;
create table `t_mall_prop_name`(
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(10) NOT NULL COMMENT '编码',
  `name` varchar(100) NOT NULL comment '名称',
  `create_time` int not null comment '创建时间',
  `catalog_id` int not null comment '类目ID',
  `is_sale_prop` tinyint not null default 0 comment '是否是销售属性',
  `form_type` enum('int','text','varchar') not null comment '表单形式',
  primary key(`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类目属性名表';

drop table if exists `t_mall_prop_mapping`;
create table `t_mall_prop_mapping`(
  `id` int(11) NOT NULL auto_increment,
  `prop_name_id` int not null comment '属性名ID',
  `prop_value_id` int not null comment '属性值ID',
  `prop_value_type` enum('int','text','varchar') not null comment '属性值形式',
  primary key(`id`),
  unique(`prop_name_id`,`prop_value_id`,`prop_value_type`)
)comment='属性名与值关系表';

drop table if exists `t_mall_prop_value_text`;
create table `t_mall_prop_value_text`(
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(10) NOT NULL COMMENT '编码',
  `prop_value` text  NULL comment '属性值',
  `create_time` int not null comment '创建时间',
  `prop_name_id` int not null comment '类目属性名ID',
  primary key(`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类目属性值表-大文本';

drop table if exists `t_mall_prop_value_int`;
create table `t_mall_prop_value_int`(
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(10) NOT NULL COMMENT '编码',
  `prop_value` int  NULL comment '属性值',
  `create_time` int not null comment '创建时间',
  `prop_name_id` int not null comment '类目属性名ID',
  primary key(`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类目属性值表-数值';

drop table if exists `t_mall_prop_value_varchar`;
create table `t_mall_prop_value_varchar`(
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(10) NOT NULL COMMENT '编码',
  `prop_value` varchar(200)  NULL comment '属性值',
  `create_time` int not null comment '创建时间',
  `prop_name_id` int not null comment '类目属性名ID',
  primary key(`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类目属性值表-小文本';
