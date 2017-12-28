# 说明
- sample-config/acc.xml 是权限资源配置文件，RoleResModel调用时需要用到
```
$accXml = 'sample-config/acc.xml';
$model = new RoleResModel();
$model->setResourceConfigFile($accXml);
$model->getResourceGroup();
```