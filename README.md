## API网关使用说明

- API网关处理程序在src/Core/Api/ApiService.php中
- 不同的接口处理约定在api的json说明文件中约定了，具体见api-json-samle这个例子目录
- 处理API的程序是根据json文件的约定，在src/Api目录中
- api.json文件的规范及相关api的约定见[http://github.com/misnet/apidocs]
- API网关会根据json文件的约定，对json文件中request部分进行校验，也会根据文件约定是否
需要accessToken进行校验；同时json文件中约定了API处理接口的命名空间及处理的类和方法。

API网关调用示例：
```
$requestObject = new Request($_POST);
$requestObject->setOrigRequest($_POST);
ApiService::setDi($this->getDI());
ApiService::initApiJsonConfigFile('路径/api.json');
$result = ApiService::response($requestObject);
echo json_encode($result);
```