<?php
require '../src/Validator.php';

$data = [
    'name'  => 'zhangsan',
    'age'   => 26,
    'sex'   => 1,
    'email' => '123@qq.com'
];

$rules = [
    'name'  => 'require|length:1,5|chinese',
    'age'   => 'require|positiveInt|between:1,150',
    'sex'   => 'require|in:0,1,2',
    'email' => 'require|email'
];

$message = [
    'name.require'    => 'name必填',
    'name.length'     => 'name长度只能在1~5之间',
    'name.chinese'    => 'name必须是中文的',
    'age.require'     => 'age必填',
    'age.positiveInt' => 'age必须是正整数',
    'age.between'     => 'age必须是1~150之间的正整数',
    'sex.require'     => 'sex必填',
    'sex.in'          => 'sex只能是0,1,2之中的值',
    'email.require'   => 'email必填',
    'email.email'     => 'email必须是一个合法的邮箱地址'
];

try{
    // 默认情况使用单个验证模式，即某个字段验证错误立即返回，不继续验证其他字段
    $validator = new \Xjw\Validator($data, $rules, $message);
    // 批量验证
    //$validator = new \Xjw\Validator($data, $rules, $message, \Xjw\Validator::ERR_RETURN_MODE_ALL);
    $result = $validator->isValid(); // 数据验证结果
    //var_dump($result);
    $errorMessage = $validator->getErrorMessage(); // 获取验证错误信息
    //var_dump($errorMessage);
    $help = $validator->getHelp(); // 获取验证规则说明
    //var_dump($help);
}catch (Exception $e){
    var_dump($e->getMessage());
}