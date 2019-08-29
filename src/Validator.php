<?php
/**
 * Created by PhpStorm.
 * User: xiajunwei
 * Date: 2019/4/24
 * Time: 11:28
 */

namespace Xjw;


class Validator
{
    //需要验证的数据
    private $data = [];
    //验证规则
    private $rules = [];
    //提示信息
    private $message = [];
    //错误信息
    private $errorMessage = [];
    //验证结果
    private $isValid = true;
    //错误信息返回模式，single=验证不通过，立即返回
    const ERR_RETURN_MODE_SINGLE = 'single';
    //错误信息返回模式，all=验证所有参数，一次性返回所有错误信息
    const ERR_RETURN_MODE_ALL= 'all';
    //错误信息返回模式
    private $errorReturnMode;

    public function __construct($data, $rules, $messages, $errorReturnMode = self::ERR_RETURN_MODE_SINGLE)
    {
        if(in_array($errorReturnMode, [self::ERR_RETURN_MODE_SINGLE, self::ERR_RETURN_MODE_ALL])){
            $this->errorReturnMode = $errorReturnMode;
        }else{
            $this->errorReturnMode = self::ERR_RETURN_MODE_SINGLE;
        }
        $this->setData($data);
        $this->setRules($rules);
        $this->setMessage($messages);
        $this->validate();
    }

    /**
     * 获取验证规则使用说明
     */
    public function getHelp()
    {
        return [
            'require'          => '字段必填',
            'numeric'          => '字段必须是数字(包含正负数，小数)',
            'integer'          => '字段必须是整数(包含正负整数)',
            'positiveInt'      => '字段必须是正整数',
            'email'            => '字段必须是邮箱',
            'phoneNumber'      => '字段必须是手机号码',
            'ip'               => '字段必须是ip地址',
            'timeFormat'       => '字段值必须是时间格式[Y-m-d H:i:s]',
            'dateFormat'       => '字段值必须是日期格式[Y-m-d]',
            'idStr'            => '字段值必须是使用逗号拼接的id字符串[1,2,3]',
            'max:12'           => '验证数字的最大值',
            'min:12'           => '验证数字的最小值',
            'length:1,3'       => '字段长度在1至3之间, 其中1和3是自定义的',
            'between:1,10'     => '字段值必须在1至10之间，其中1和10是自定义的。该规则用于检测数值范围，请勿用于检测字符',
            'in:1,a,2'         => '字段值必须在限定选项内',
            'notIn:1,a,2'      => '字段值不能在限定选项内',
            'chinese'          => '字段值必须是全中文',
            'fileType:mp3,mp4' => '限定文件类型'
        ];
    }

    /**
     * @param $params
     */
    private function setData(array $params = [])
    {
        $this->data = $params;
    }

    /**
     * 设置验证规则
     * @param $rules
     */
    private function setRules(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * 保存传入的提示信息
     * @param array $message
     */
    private function setMessage(array $message = [])
    {
        $this->message = $message;
    }

    /**
     * 验证数据
     * $data = ['name' => 'zhangsan']
     * $rules = ['name' => 'require|length:1,5']
     * $message = ['name.require' => '姓名必填']
     */
    private function validate()
    {
        $result = '';
        foreach ($this->rules as $name => $ruleStr) {
            if(empty($ruleStr)) continue;
            //拆分规则
            $ruleArr = explode('|', $ruleStr);
            $isRequire = in_array('require', $ruleArr);
            //设置了require规则的情况下，优先调用require验证
            if($isRequire){
                $result = $this->_require($name);
                if($result === false){
                    $this->isValid = false;
                    if($this->errorReturnMode == self::ERR_RETURN_MODE_SINGLE){
                        return ;
                    }else{
                        continue;
                    }
                }
            }
            //调用对应规则进行验证
            foreach ($ruleArr as $ruleMethod) {
                //提取验证方法名
                if(strpos($ruleMethod, ':') !== false){
                    //拆分包含冒号的验证规则  length:1,5 => ['length', '1,5']
                    $tmpArr = explode(':', $ruleMethod);
                    $ruleMethod = '_' . $tmpArr[0];
                    if(!method_exists(__CLASS__, $ruleMethod)){
                        throw new \Exception('验证规则不存在:' . substr($ruleMethod, 1), 400);
                    }
                    //判断是否设置了require规则，如果设置了，则必须验证，如果没设置，则字段存在才会验证
                    if($isRequire){
                        $result = $this->$ruleMethod($name, $tmpArr[1]);
                    }else{
                        if(isset($this->data[$name])){
                            $result = $this->$ruleMethod($name, $tmpArr[1]);
                        }
                    }
                }else{
                    $ruleMethod = '_' . $ruleMethod;
                    if(!method_exists(__CLASS__, $ruleMethod)){
                        throw new \Exception('验证规则不存在:' . substr($ruleMethod, 1), 400);
                    }
                    //判断是否设置了require规则，如果设置了，则必须验证，如果没设置，则字段存在才会验证
                    if($isRequire){
                        $result = $this->$ruleMethod($name);
                    }else{
                        if(isset($this->data[$name])){
                            $result = $this->$ruleMethod($name);
                        }
                    }
                }
                if($result === false){
                    $this->isValid = false;
                    if($this->errorReturnMode == self::ERR_RETURN_MODE_SINGLE) return ;
                }
            }
        }
    }

    /**
     * 保存需要返回的错误信息
     * @param string $message
     */
    private function setErrorMessage($message = '')
    {
        if($this->errorReturnMode == self::ERR_RETURN_MODE_ALL){
            $this->errorMessage[] = $message;
        }else{
            $this->errorMessage = $message;
        }
    }

    /**
     * 获取错误信息
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * 获取验证结果
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * 验证字段必填
     * @param $name 字段名
     */
    private function _require($name)
    {
        if(isset($this->data[$name])){
            return true;
        }
        $defaultErrorMessage = $name . '字段为必填选项';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.require']) ? $this->message[$name . '.require'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 验证字符串长度是否在规定范围内
     * @param $name 字段名
     * @param $param 参数
     */
    private function _length($name, $param)
    {
        $value = (string)$this->data[$name];
        $len = strlen($value);
        $tmpArr = explode(',', $param);
        $minLen = $tmpArr[0];
        $maxLen = $tmpArr[1];
        if($len >= $minLen && $len <= $maxLen){
            return true;
        }
        $defaultErrorMessage = $name . '字段长度必须在' . $minLen . '至' . $maxLen . '之间';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.length']) ? $this->message[$name . '.length'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段必须是数字(包含正负数，小数)
     * @param $name
     * @return bool
     */
    private function _numeric($name)
    {
        if(is_numeric($this->data[$name])){
            return true;
        }
        $defaultErrorMessage = $name . '字段必须是数字(包含正负数，小数)';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.numeric']) ? $this->message[$name . '.numeric'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段必须是邮箱
     * @param $name
     */
    private function _email($name)
    {
        if(filter_var($this->data[$name], FILTER_VALIDATE_EMAIL)){
            return true;
        }
        $defaultErrorMessage = $name . '字段必须是邮箱';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.email']) ? $this->message[$name . '.email'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段必须是手机号码
     * @param $name
     */
    private function _phoneNumber($name)
    {
        if(preg_match('/^1[3456789]\d{9}$/', $this->data[$name])){
            return true;
        }
        $defaultErrorMessage = $name . '字段必须是手机号码';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.phoneNumber']) ? $this->message[$name . '.phoneNumber'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段必须是ip地址
     * @param $name
     */
    private function _ip($name)
    {
        if(filter_var($this->data[$name], FILTER_VALIDATE_IP)){
            return true;
        }
        $defaultErrorMessage = $name . '字段必须是ip地址';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.ip']) ? $this->message[$name . '.ip'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段必须在某个数值范围内
     * @param $name
     * @param $param
     */
    private function _between($name, $param)
    {
        $tmpArr = explode(',', $param);
        $min = (int)$tmpArr[0];
        $max = (int)$tmpArr[1];
        if($this->data[$name] >= $min && $this->data[$name] <= $max){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值必须在' . $min . '和' . $max . '之间';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.between']) ? $this->message[$name . '.between'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段值必须在限定选项内
     * @param $name
     * @param $param
     */
    private function _in($name, $param)
    {
        $tmpArr = explode(',', $param);
        if(in_array($this->data[$name], $tmpArr)){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值只能是' . $param . '之中的值';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.in']) ? $this->message[$name . '.in'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段值不能在限定选项内
     * @param $name
     * @param $param
     */
    private function _notIn($name, $param)
    {
        $tmpArr = explode(',', $param);
        if(!in_array($this->data[$name], $tmpArr)){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值不能是' . $param . '之中的值';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.notIn']) ? $this->message[$name . '.notIn'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段值必须是时间格式 Y-m-d H:i:s
     * @param $name
     */
    private function _timeFormat($name)
    {
        $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1]) ([0-1]\d|2[0-4]):([0-5]\d):([0-5]\d)$/';
        if(preg_match($preg, $this->data[$name])){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值只能是合法的时间格式[YYYY-mm-dd HH:ii:ss]';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.timeFormat']) ? $this->message[$name . '.timeFormat'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段值必须是日期格式 Y-m-d
     * @param $name
     */
    private function _dateFormat($name)
    {
        $preg = '/^([12]\d\d\d)-(0?[1-9]|1[0-2])-(0?[1-9]|[12]\d|3[0-1])$/';
        if(preg_match($preg, $this->data[$name], $parts)){
            if(checkdate($parts[2], $parts[3], $parts[1])){
                $result = true;
            }else{
                $result = false;
            }
        }else{
            $result = false;
        }
        if($result === true){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值只能是合法的日期格式[YYYY-mm-dd]';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.dateFormat']) ? $this->message[$name . '.dateFormat'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 字段值必须是全中文
     * @param $name
     */
    private function _chinese($name)
    {
        if(!preg_match('/[^\x80-\xff]/', $this->data[$name])){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值必须是全中文';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.chinese']) ? $this->message[$name . '.chinese'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 验证数字的最大值
     * @param $name
     * @param $param
     */
    private function _max($name, $param)
    {
        $value = (int)$this->data[$name];
        $max = (int)$param;
        if($value <= $max){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值不能大于' . $max;
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.max']) ? $this->message[$name . '.max'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 验证数字的最小值
     * @param $name
     * @param $param
     */
    private function _min($name, $param)
    {
        $value = (int)$this->data[$name];
        $min = (int)$param;
        if($value >= $min){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值不能小于' . $min;
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.min']) ? $this->message[$name . '.min'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 必须是整数(包含正负整数，不包含小数)
     * @param $name
     */
    private function _integer($name)
    {
        if(filter_var($this->data[$name], FILTER_VALIDATE_INT)){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值必须是整数(包含正负整数，不包含小数)';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.integer']) ? $this->message[$name . '.integer'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 必须是正整数
     * @param $name
     */
    private function _positiveInt($name)
    {
        if(preg_match("/^[1-9][0-9]*$/", $this->data[$name])){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值必须是正整数';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.positiveInt']) ? $this->message[$name . '.positiveInt'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 必须是逗号拼接的id字符串
     * @param $name
     */
    private function _idStr($name)
    {
        if(preg_match('/^[1-9]+(,\d+)*$/', $this->data[$name])){
            return true;
        }
        $defaultErrorMessage = $name . '字段的值必须是逗号拼接数字[例如：1,2,3,4]';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.idStr']) ? $this->message[$name . '.idStr'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

    /**
     * 限定文件类型
     * @param $name
     */
    private function _fileType($name, $param)
    {
        $ext = pathinfo($this->data[$name], PATHINFO_EXTENSION);
        $allowType = explode(',', $param);
        if(in_array($ext, $allowType)){
            return true;
        }
        $defaultErrorMessage = $name . '字段必须是一个文件名，且后缀名只能是' . $param . '之中的值';
        //添加错误信息
        $errorMessage = isset($this->message[$name . '.fileType']) ? $this->message[$name . '.fileType'] : $defaultErrorMessage;
        $this->setErrorMessage($errorMessage);
        return false;
    }

}