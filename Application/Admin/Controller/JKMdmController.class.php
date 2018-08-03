<?php
/**
 * 所属项目 jkapp.
 * 开发者: luoj
 * 创建日期: 2016年5月26日
 * 创建时间: 下午4:04:09
 * 版权所有 重庆艾锐森科技有限责任公司(www.irosn.com)
 */

namespace Admin\Controller;
use Think\Model;
use Think\Controller;
/***********************************************************************
Class:        Mht File Maker
Version:      1.2 beta
Date:         02/11/2007
Author:       Wudi <wudicgi@yahoo.de>
Description:  The class can make .mht file.
 ***********************************************************************/

class JKMdmController extends Controller{
    public $soap;
    public $ws;
    protected function _initialize()
    {
        //$ws = "http://192.168.9.52:9099/S-MDM4Service/getCode?wsdl";
//        $this->ws = "http://192.168.9.52:9099/S-MDM4Service/getCode?wsdl";
        $this->ws = "http://192.168.9.48:9090/S-MDM4Service/getCode?wsdl";

        dump(111);
        $this->soap = new \SoapClient ($this->ws);

        dump($this->soap);
//        exit;
//         dump($this->soap->__getFunctions ());
//         $ws = "http://218.70.38.34:9090/S-MDM4Service/getCode?wsdl";
//         $this->soap = new \SoapClient ($ws);
//         dump($this->soap->__getFunctions ());
//         exit;
    }
    public function testMdm(){
        $auth_header = array( 'OperationCode'=>'mdmMasterDataregistration', 'ClientId'=>'com.jinke.esb.consumer.irosn' );
        // 下面的RequestSOAPHeader 对应 wsdl 定义里面的 <xsd:element name="RequestSOAPHeader">.....
        $authvalues = new \SoapVar($auth_header, SOAP_ENC_OBJECT,"Header",$this->ws);
        $header = new \SoapHeader($this->ws, 'OperationCode', 'mdmMasterDataregistration');

        $header1 = new \SoapHeader($this->ws, 'ClientId', 'com.jinke.esb.consumer.irosn');
        $this->soap->__setSoapHeaders(array($header,$header1));
        dump($this->soap);exit;

        set_time_limit(60);
        $json_data = '[{"PROJSTATUS":"在建","CREATERCODE":"","USESTATUS":"","VERSIONNUMBER":"","OCCUPYAREA":"100","OCCUPYAREAFA":"0","LHLFA":"0","LANDNATUREFACODE":"","LANDNATUREFA":"","LANDNATURECODE":"","LANDNATURE":"商住","BUILDDENSITYFA":"0","RJLFA":"0","RJL":"100","BUILDDENSITY":"100","ISGTCODE":"","ISGT":"0","PROJECTNUMBER":"Z102017203","PROJNAME":"MDM测试项目","PLOTNAME":"","PROJADDRESS":"重庆渝中区","PROJSTATUSNO":"","ONCENAME":"","SPREADNAME":"","STAGESNAME":"工程测试分期变更","STAGESCODE":"Z10201720301","DIST_DESC":"","IS_AUTO":"","DIST_STATE":"2","DIST_CONFIRM":"","AIID":"","PIID":"","EXT_F":"","EXT_E":"","EXT_D":"","EXT_C":"","EXT_B":"","EXT_A":"","S_REMARK":"","IS_ENABLE":"","BUSINESS_TYPE":"2","ARCH_VERSION":"5.0","ARCH_STATE":"checkin","STORAGE_TYPE":"distwork","M_CLASS_NAME":"","MASTER_CLASS":"001003","BUSINESS_ID":"68242eb43e49496f9d50fa7f483d4902","DIST_ID":"a2960a790c64452f93656f84edb773ce","ISUSEDCODE":"0","ISUSED":"启用","PROJGUID":"9C610B6B-AACA-4778-8563-9A6F36F5A214","UPDATERCODE":"","CREATETIME":"","GETLANDDATE":"2017-01-01","ENDDATE":"2017-12-31","BEGINDATE":"2017-01-01","CREATEDATATIME":"2017-08-17","UPDATETIME":"","DIST_TIME":"","DIST_NO":"","S_UPDATETIME":"2017-08-17 16:21:55","S_CREATETIME":"2017-08-17 16:21:55","ARCH_TIME":"2017-08-17 16:21:55","LHL":"100","BUILDAREA":"0","PRINCIPALNAME":"","PRINCIPAL":""}]';
        //$json_data = '[{"S_REMARK":"","ISUSED":"作废","S_UPDATETIME":"2017-08-18 00:00:00","M_CLASS_NAME":"分期主数据","LANDNATURE":"","CREATETIME":"","MASTER_CLASS":"001002","OPT_MASTER_CODE":"","ISUSEDCODE":"2","LANDNATURECODE":"","CREATEDATATIME":"","SPREADNAME":"","BUILDDENSITY":"0","VERSIONNUMBER":"","PLOTNAME":"","STORAGE_TYPE":"distwork","BEGINDATE":"","LANDNATUREFA":"","PROJADDRESS":"","ARCH_TIME":"2017-08-18 00:00:00","OCCUPYAREA":"0","PROJNAME":"阳光小镇","PROJSTATUS":"","LHLFA":"0","OCCUPYAREAFA":"0","ENDDATE":"","BUSINESS_ID":"db6551ab54ce4aad8bc5a00b399d3e81","ARCH_VERSION":"9.0","STAGESCODE":"Z10201703104","BUILDDENSITYFA":"0","IS_ENABLE":"","UPDATERCODE":"","LANDNATUREFACODE":"","GETLANDDATE":"","ISGTCODE":"","LHL":"0","BUILDAREA":"0","RJL":"0","PROJGUID":"1B004501-D2D6-477D-B222-36BC94FB2631","ONCENAME":"","STAGESNAME":"阳光小镇-6期超市","CREATERCODE":"","S_CREATETIME":"2017-08-18 00:00:00","ARCH_STATE":"checkin","ISGT":"0","USESTATUS":"","PROJECTNUMBER":"Z102017031","BUSINESS_TYPE":"1","RJLFA":"0","UPDATETIME":"","PROJSTATUSNO":""},{"S_REMARK":"","ISUSED":"启用","S_UPDATETIME":"2017-08-18 00:00:00","M_CLASS_NAME":"分期主数据","LANDNATURE":"商住","CREATETIME":"","MASTER_CLASS":"001002","OPT_MASTER_CODE":"","ISUSEDCODE":"0","LANDNATURECODE":"","CREATEDATATIME":"2017-08-18","SPREADNAME":"","BUILDDENSITY":"2","VERSIONNUMBER":"","PLOTNAME":"","STORAGE_TYPE":"distwork","BEGINDATE":"2017-08-18","LANDNATUREFA":"","PROJADDRESS":"MDM0818CS02","ARCH_TIME":"2017-08-18 00:00:00","OCCUPYAREA":"10000","PROJNAME":"MDM测试0818","PROJSTATUS":"在建","LHLFA":"0","OCCUPYAREAFA":"0","ENDDATE":"2017-08-31","BUSINESS_ID":"e979f086dda941dfb33f15da4df1e3f1","ARCH_VERSION":"10.0","STAGESCODE":"H10201720502","BUILDDENSITYFA":"0","IS_ENABLE":"","UPDATERCODE":"","LANDNATUREFACODE":"","GETLANDDATE":"2017-08-18","ISGTCODE":"","LHL":"2","BUILDAREA":"0","RJL":"20","PROJGUID":"8B705CA3-D4DB-40CE-B69B-7FB8E689232E","ONCENAME":"","STAGESNAME":"MDM测试0818-MDM0818CS02","CREATERCODE":"","S_CREATETIME":"2017-08-18 00:00:00","ARCH_STATE":"checkin","ISGT":"0","USESTATUS":"","PROJECTNUMBER":"H102017205","BUSINESS_TYPE":"1","RJLFA":"0","UPDATETIME":"","PROJSTATUSNO":""},{"S_REMARK":"","ISUSED":"作废","S_UPDATETIME":"2017-08-18 00:00:00","M_CLASS_NAME":"分期主数据","LANDNATURE":"住宅","CREATETIME":"","MASTER_CLASS":"001002","OPT_MASTER_CODE":"","ISUSEDCODE":"2","LANDNATURECODE":"","CREATEDATATIME":"","SPREADNAME":"","BUILDDENSITY":"31","VERSIONNUMBER":"","PLOTNAME":"","STORAGE_TYPE":"distwork","BEGINDATE":"2014-08-30","LANDNATUREFA":"住宅","PROJADDRESS":"五家渠市","ARCH_TIME":"2017-08-18 00:00:00","OCCUPYAREA":"188144.1","PROJNAME":"新疆金科.世界城","PROJSTATUS":"在建","LHLFA":"0","OCCUPYAREAFA":"0","ENDDATE":"2017-12-30","BUSINESS_ID":"ba884e7c8c224bc58e5c3d9c3c47a5b8","ARCH_VERSION":"8.0","STAGESCODE":"Z10201706204","BUILDDENSITYFA":"0","IS_ENABLE":"","UPDATERCODE":"","LANDNATUREFACODE":"","GETLANDDATE":"2014-05-14","ISGTCODE":"","LHL":"31.5","BUILDAREA":"0","RJL":"1.4","PROJGUID":"B13FFCC0-446A-44AE-9004-DA08EFCF349C","ONCENAME":"","STAGESNAME":"新疆金科.世界城-廊桥水乡二期（紫院）","CREATERCODE":"","S_CREATETIME":"2017-08-18 00:00:00","ARCH_STATE":"checkin","ISGT":"0","USESTATUS":"","PROJECTNUMBER":"Z102017062","BUSINESS_TYPE":"1","RJLFA":"0","UPDATETIME":"","PROJSTATUSNO":""},{"S_REMARK":"","ISUSED":"作废","S_UPDATETIME":"2017-08-18 00:00:00","M_CLASS_NAME":"分期主数据","LANDNATURE":"住宅","CREATETIME":"","MASTER_CLASS":"001002","OPT_MASTER_CODE":"","ISUSEDCODE":"2","LANDNATURECODE":"","CREATEDATATIME":"","SPREADNAME":"","BUILDDENSITY":"10","VERSIONNUMBER":"","PLOTNAME":"","STORAGE_TYPE":"distwork","BEGINDATE":"2015-09-23","LANDNATUREFA":"","PROJADDRESS":"111","ARCH_TIME":"2017-08-18 00:00:00","OCCUPYAREA":"10","PROJNAME":"测试项目","PROJSTATUS":"在建","LHLFA":"0","OCCUPYAREAFA":"0","ENDDATE":"2015-11-24","BUSINESS_ID":"8703e5da6650400aac049e0b0bf1c357","ARCH_VERSION":"7.0","STAGESCODE":"Z10201705401","BUILDDENSITYFA":"0","IS_ENABLE":"","UPDATERCODE":"","LANDNATUREFACODE":"","GETLANDDATE":"2015-09-30","ISGTCODE":"","LHL":"10","BUILDAREA":"0","RJL":"10","PROJGUID":"64F13C40-625B-4670-A1F6-883167EE3985","ONCENAME":"","STAGESNAME":"测试项目-一期","CREATERCODE":"","S_CREATETIME":"2017-08-18 00:00:00","ARCH_STATE":"checkin","ISGT":"0","USESTATUS":"","PROJECTNUMBER":"Z102017054","BUSINESS_TYPE":"1","RJLFA":"0","UPDATETIME":"","PROJSTATUSNO":""},{"S_REMARK":"","ISUSED":"启用","S_UPDATETIME":"2017-08-18 00:00:00","M_CLASS_NAME":"分期主数据","LANDNATURE":"住宅","CREATETIME":"","MASTER_CLASS":"001002","OPT_MASTER_CODE":"","ISUSEDCODE":"0","LANDNATURECODE":"","CREATEDATATIME":"2017-08-18","SPREADNAME":"","BUILDDENSITY":"24","VERSIONNUMBER":"","PLOTNAME":"","STORAGE_TYPE":"distwork","BEGINDATE":"2016-11-25","LANDNATUREFA":"","PROJADDRESS":"项目位于南宁市西乡塘区高新相思湖片，可利大道北侧，广西艺术学院相思湖校区东侧（091地块）。","ARCH_TIME":"2017-08-18 00:00:00","OCCUPYAREA":"44800","PROJNAME":"南宁项目","PROJSTATUS":"在建","LHLFA":"0","OCCUPYAREAFA":"0","ENDDATE":"2020-12-31","BUSINESS_ID":"c8d450a84cbc44c3b76e7f0e357bc2a1","ARCH_VERSION":"9.0","STAGESCODE":"Z10201709902","BUILDDENSITYFA":"0","IS_ENABLE":"","UPDATERCODE":"","LANDNATUREFACODE":"","GETLANDDATE":"2016-11-25","ISGTCODE":"","LHL":"35","BUILDAREA":"0","RJL":"4","PROJGUID":"C32A49BC-85AB-46C8-AE76-8346F33B677F","ONCENAME":"","STAGESNAME":"南宁项目-南宁金科观天下A区(宗地一)","CREATERCODE":"","S_CREATETIME":"2017-08-18 00:00:00","ARCH_STATE":"checkin","ISGT":"0","USESTATUS":"","PROJECTNUMBER":"Z102017099","BUSINESS_TYPE":"1","RJLFA":"0","UPDATETIME":"","PROJSTATUSNO":""}]';
        dump($this->mdmMasterSetData($json_data)) ;die;
        $json_data = file_get_contents("ceshi.txt");
        dump($json_data);


        $json_data = json_decode(trim($json_data,chr(239).chr(187).chr(191)),true);
        dump($json_data);

        $result=json_decode($json_data);

        dump($result[0]);die;
        $soap = $this->soap;
        $masterCode=array();
        $result = $soap->mdmMasterDataGenCode(array(
            'masterDateJson'=>'{"masterCategory":"001001","data":[{"applyinfo":{"APPLICANT":"1-","APPLY_REASON":"\u751f\u6210\u7f16\u7801\u7533\u8bf7"},"bussinessdata":{"PROJECTNO":"Z","PLATECODE":"10","CREATEDATATIME":"2017-07-25"}}]}',

        ));
        dump($result);

    }
    public function testMdm1(){
        set_time_limit(60);
        //组织数据
        //$json_data = '[{"PERSONLIABLETEL":"","CREATEDATATIME":"2017-11-03","ISUSED":"0","HIGORGZATION":"","ISUSEDCODE":"0","HIGORGZATIONCODE":"ceshi","ADMINADDRESS":"","BUSINESS_TYPE":"1","PLATECODE":"10","PERSONLIABLE":"","OPT_MASTER_CODE":"","SIMPLENAME":"HR测试组织","FAX":"","PLATE":"地产","FULLNAME":"HR测试组织全称","ORGANIZATIONCODE":"20170013","MASTER_CLASS":"002001"}]';
        //账号数据

        //人员数据
        $json_data = '[{"PERACCOUNTPASS":"","NATIONALITYCODE":"","HOMEPHONE":"","EMAIL":"","OLDNAME":"","INDEXOF":"","SIMPLENAME":"","BIRTHDAY":"","MASTER_CLASS":"002002","BUSINESS_ID":"fb6539ae5e6547d388e215c71c5c83fb","DELETEDSTATUS":"","DIST_ID":"4d78afee75b647228749308defde56d6","FOLK":"","DISTRIBUTIONSYSTEMCODE":"01,02,04","DEPARTMENT":"","CREATEDATATIME":"","HJADDRESS":"","EMPLOYTECHPOST":"","ISUSED":"","PASSPORTNO":"","NATIVEPLACE":"","PERCREATEDATATIME":"","WED":"","OPT_MASTER_CODE":"","DISTRIBUTIONSYSTEM":"","NATIONALITY":"","IDCARDNO":"","EMPLOYEETYPE":"","BUSINESS_TYPE":"1","GENDER":"","PERSONNELID":"1002","GENDERCODE":"","PERACCOUNTCODE":"","PERSONNELNAME":"","ADDRESSTX":"","IDCARDADDRESS":"","PERSONTYPE":"","SIMPLENAMEPINGYIN":"","FULLNAMEPINGYIN":"","REGRESIDENCE":"","HEIGHT":"","PERSONNELNUM":"千寻","M_CLASS_NAME":"人员主数据","EXT_B":"1","ISUSEDCODE":"","POLITICALFACE":"","PERACCOUNTNAME":"","CELL":"","NATIVEPLACECODE":""},{"PERACCOUNTPASS":"","NATIONALITYCODE":"","HOMEPHONE":"","EMAIL":"","OLDNAME":"","INDEXOF":"","SIMPLENAME":"","BIRTHDAY":"","MASTER_CLASS":"002002","BUSINESS_ID":"116b331a53404a6da45f6a63ccaa15b0","DELETEDSTATUS":"","DIST_ID":"e297801d6c2d4689bc8c8594fb2a63fb","FOLK":"","DISTRIBUTIONSYSTEMCODE":"01,02,03","DEPARTMENT":"","CREATEDATATIME":"","HJADDRESS":"","EMPLOYTECHPOST":"","ISUSED":"","PASSPORTNO":"","NATIVEPLACE":"","PERCREATEDATATIME":"","WED":"","OPT_MASTER_CODE":"","DISTRIBUTIONSYSTEM":"","NATIONALITY":"","IDCARDNO":"","EMPLOYEETYPE":"","BUSINESS_TYPE":"1","GENDER":"","PERSONNELID":"1001","GENDERCODE":"","PERACCOUNTCODE":"","PERSONNELNAME":"","ADDRESSTX":"","IDCARDADDRESS":"","PERSONTYPE":"","SIMPLENAMEPINGYIN":"","FULLNAMEPINGYIN":"","REGRESIDENCE":"","HEIGHT":"","PERSONNELNUM":"玲珑","M_CLASS_NAME":"人员主数据","EXT_B":"1","ISUSEDCODE":"","POLITICALFACE":"","PERACCOUNTNAME":"","CELL":"","NATIVEPLACECODE":""}]';

        $json_data = '[{"ADDRESSTX":"null","HOMEPHONE":"null","PASSPORTNO":"765865867876","ISUSED":"启用","PERACCOUNTPASS":"666666","HEIGHT":"0","M_CLASS_NAME":"人员主数据","PERSONNELNUM":"118959","MASTER_CLASS":"002002","NATIONALITY":"null","OPT_MASTER_CODE":"","ISUSEDCODE":"0","CREATEDATATIME":"2017-11-24","WED":"null","INDEXOF":"10000189","DELETEDSTATUS":"有效","EMPLOYTECHPOST":"null","CELL":"null","DISTRIBUTIONSYSTEMCODE":"06_1,01_1,03_1,04_1,05_1","EMAIL":"null","IDCARDADDRESS":"null","PERSONTYPE":"A类人员","NATIVEPLACECODE":"","IDCARDNO":"null","PERACCOUNTNAME":"118959","DEPARTMENT":"4xEAAAAAC9zM567U","NATIVEPLACE":"null","PERSONNELNAME":"孟坤","NATIONALITYCODE":"","REGRESIDENCE":"null","PERCREATEDATATIME":"","BIRTHDAY":"","OLDNAME":"null","EMPLOYEETYPE":"正式员工","GENDERCODE":"","HJADDRESS":"null","DISTRIBUTIONSYSTEM":"OA_启用,ERP_启用,金品质_启用,商业系统_启用,HR_启用","PERACCOUNTCODE":"EHv0cJQuSuKhu4gmd0AdQRO33n8=","SIMPLENAMEPINGYIN":"mk","SIMPLENAME":"null","GENDER":"null","PERSONNELID":"4xEAAAANDCCA733t","FOLK":"null","FULLNAMEPINGYIN":"mengkun","BUSISS_TYPE":"1","POLITICALFACE":"null"}]';
        $this->mdmMasterSetData($json_data);

    }
    /**
     * 函数用途描述：被动接受增量主数据
     * @date: 2017年8月14日 上午9:53:09
     * @author: tanjiewen
     * @param: $type类别代码  $json_data 传入数据,json串
     * @return:
     */
    public function mdmMasterSetData($json_data='001') {

        if($json_data=='001'){
            $json_data=file_get_contents('1510709.txt');
        }

        //获取当前时间戳存入文件
        $file_name = "./Uploads/".time().'.txt';

        $encode = mb_detect_encoding($json_data, array('ASCII','UTF-8','GB2312','GBK','BIG5'));

        if($encode == 'UTF-8'){

        }else{
            file_put_contents($file_name, $json_data);
            $json_data = file_get_contents($file_name);
            $json_data= iconv($encode, 'UTF-8', $json_data);
        }

        $result = json_decode(trim($json_data,chr(239).chr(187).chr(191)),true);

        $type=$result[0]['MASTER_CLASS'];

        $new_file_name = $this->getname($type,$file_name);
        file_put_contents($new_file_name, $json_data);
        //之前的文件先销毁

        //存到对应文件中
        // dump($file_name);

        // dump($file_name);

        if (!unlink($file_name))//销毁文件
        {
            echo ("Error deleting $file_name");
        }
        else
        {
            echo ("Deleted $file_name");
        }

        //die;
        if($type=='001001'){
            return $this->mdmSetSomeProMasterData($json_data);
        }elseif($type=='001002'){
            return $this->mdmSetSomeStageMasterData($json_data);
        }elseif($type=='001003'){
            return $this->mdmSetSomeBuildMasterData($json_data);
        }elseif($type=='002001'){
            return $this->mdmSetOrganize($json_data);
        }elseif($type=='002002'){
            return $this->mdmSetPerson($json_data);
        }elseif($type=='002002001'){
            return $this->mdmSetAccount($json_data);
        }elseif($type=='003001'){
            return $this->mdmSetProvider($json_data);
        }
    }

    /**
     * 函数用途描述：设置文件路径
     * @date: 2017年8月21日 上午13:35:09
     * @author: tanjiewen
     * @param: $param 传入数据,json串
     * @return:
     */
    public function getname($type,$file_name){

        if($type=='001001'){
            $dir = "./Uploads/Project/";
        }elseif($type=='001002'){
            $dir = "./Uploads/Stage/";
        }elseif($type=='001003'){
            $dir = "./Uploads/Build/";
        }elseif($type=='002001'){
            $dir = "./Uploads/Organiza/";
        }elseif($type=='002002'){
            $dir = "./Uploads/Person/";
        }elseif($type=='002002001'){
            $dir = "./Uploads/Account/";
        }elseif($type=='003001'){
            $dir = "./Uploads/Provider/";
        }

        $i=1;
        if(!is_dir($dir)){
            mkdir($dir,0777);
        }


        return $dir.time().".txt";
    }
    /**
     * 函数用途描述：生成房间标准码
     * @date: 2017年8月1日 下午5:03:09
     * @author: luojun
     * @param: $param 传入数据,json串
     * @return:
     */
    public function mdmMasterDataGenCode($param) {


//        $auth_header = array( 'OperationCode'=>'mdmMasterDataGenCode', 'ClientId'=>'com.jinke.esb.consumer.irosn' );
//        // 下面的RequestSOAPHeader 对应 wsdl 定义里面的 <xsd:element name="RequestSOAPHeader">.....
//        $authvalues = new \SoapVar($auth_header, SOAP_ENC_OBJECT,"Header",$this->ws);
//        $header = new \SoapHeader($this->ws, 'Header', $authvalues);
//        $this->soap->__setSoapHeaders(array($header));

        $header = new \SoapHeader($this->ws, 'OperationCode', 'com.jinke.esb.provider.mdm.getCode.DataGenCode');

        $header1 = new \SoapHeader($this->ws, 'ClientId', 'com.jinke.esb.consumer.irosn');
        $this->soap->__setSoapHeaders(array($header,$header1));

        $soap = $this->soap;
        //dump($param);
        file_put_contents('soaptest.log', json_encode($soap));
        $result = $soap->mdmMasterDataGenCode(array(
            'masterDateJson'=>$param,

        ));
        file_put_contents('soaptest.log', $result);
        return $result;
    }
    /**
     * 函数用途描述：注册房间标准码
     * @date: 2017年8月1日 下午5:03:09
     * @author: luojun
     * @param: $param 传入数据,json串
     * @return:
     */
    public function mdmMasterDataregistration($param) {

//        $auth_header = array( 'OperationCode'=>'mdmMasterDataregistration', 'ClientId'=>'com.jinke.esb.consumer.irosn' );
//        // 下面的RequestSOAPHeader 对应 wsdl 定义里面的 <xsd:element name="RequestSOAPHeader">.....
//        $authvalues = new \SoapVar($auth_header, SOAP_ENC_OBJECT,"Header",$this->ws);
//        $header = new \SoapHeader($this->ws, 'Header', $authvalues);
//        $this->soap->__setSoapHeaders(array($header));

        $header = new \SoapHeader($this->ws, 'OperationCode', 'com.jinke.esb.provider.mdm.getCode.Dataregistration');

        $header1 = new \SoapHeader($this->ws, 'ClientId', 'com.jinke.esb.consumer.irosn');
        $this->soap->__setSoapHeaders(array($header,$header1));

        $soap = $this->soap;
        // file_put_contents('rdata1.txt', json_encode($soap)."\n", FILE_APPEND);
        $result = $soap->mdmMasterDataregistration(array(
            'masterDateJson'=>$param,
        ));
        return $result;
    }
    /**
     * 函数用途描述：调用MDM分发供应商服务功能
     * @date: 2017年11月21日 下午3:30:09
     * @author: tanjiewen
     * @param: $param 传入数据,json串
     * @return:
     */
    public function mdmGetProviderMasterData($masterClass='003001') {
        //获取项目主数据
        $soap = $this->soap;
        $result = $soap->mdmGetMasterData(array(
            'masterClass'=>$masterClass,
            'masterCode'=>'',

        ));

        $this->mdmActiveSetProvider($result);
        //dump($this->mdmSetProMasterData($result));
    }
    /**
     * 函数用途描述：调用MDM分发供应商服务功能
     * @date: 2017年11月21日 下午3:30:09
     * @author: tanjiewen
     * @param: $param 传入数据,json串
     * @return:
     */
    public function mdmCheckProvider($Providernumber,$masterClass='003001') {
        //获取项目主数据
        $soap = $this->soap;
        $result = $soap->mdmGetMasterData(array(
            'masterClass'=>$masterClass,
            'masterCode'=>'',

        ));

        //主动调用覆盖一次
        $result=json_decode($result->return);
        $result=json_decode(json_encode($result),true);

        $return['info'] = '未找到对应供应商';
        foreach ($result['returnData'] as $v){
            $data = array();
            $v = $v['data'][0];

            //如果供应商编码相同
            if($v['PROVIDERNUMBER']==$Providernumber){
                $return['info'] = '已找到对应供应商';
                $data['ProviderName']            = $v['PROVIDERNAME'];
                $data['ProviderType']            = $v['PROVIDERTYPE'];
                $data['UniformSocialCreditCode'] = $v['UNIFORMSOCIALCREDITCODE'];
                $data['Corporation']             = $v['CORPORATION'];
                $data['RegistrationAuthority']   = $v['REGISTRATIONAUTHORITY'];
                $data['RegistrationStatus']      = $v['REGISTRATIONSTATUS'];
                $data['RegisterFund']            = $v['REGISTERFUND'];
                $data['WorkAddress']             = $v['WORKADDRESS'];
                $data['BusinessScope']           = $v['BUSINESSSCOPE'];
                $data['EstablishDate']           = $v['ESTABLISHDATE'];
                $data['BusinessDateFrom']        = $v['BUSINESSDATEFROM'];
                $data['BusinessDateTo']          = $v['BUSINESSDATETO'];
                $data['RegistrationDate']        = $v['REGISTRATIONDATE'];
                $data['LicenceCode']             = $v['LICENCECODE'];
                $data['IsUsed']                  = $v['ISUSED'];
                $data['IsUsedCode']              = $v['ISUSEDCODE'];
                $data['CreateDataTime']          = $v['CREATEDATATIME'];
                $res = M('jk_provider_mdm')->where("Providernumber='".$v['PROVIDERNUMBER']."'")->save($data);
                if($res){
                    $return['status'] = 1;
                }else{
                    $return['status'] = 0;
                }

                break;
            }
        }
        dump($return);
        return json_encode($return);
    }
    /**
     * 函数用途描述：主动接收供应商主数据
     * @date: 2017年11月21日 下午 15:31:05
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function mdmActiveSetProvider($json_data){
        //获取组织主数据
        //返回值json转数组
        //file_put_contents('ceshi.txt', time().":".$json_data);

        set_time_limit(120);

        $result=json_decode($json_data->return);
        $result=json_decode(json_encode($result),true);
        $ret['status']=1;
        //遍历楼栋主数据，同步楼栋主数据信息

        foreach ($result['returnData'] as $v){
            $data = array();
            $v = $v['data'][0];

            //查看对应的OrganizationCode的本地组织表是否存在，如果不存在则添加
            $map['Providernumber'] = $v['PROVIDERNUMBER'];
            $is_find = M('jk_provider_mdm')->where($map)->getField('Providernumber');
            if(!$is_find){
                //组装新增组织架构的数组
                $data['Providernumber']          = $v['PROVIDERNUMBER'];
                $data['ProviderName']            = $v['PROVIDERNAME'];
                $data['ProviderType']            = $v['PROVIDERTYPE'];
                $data['UniformSocialCreditCode'] = $v['UNIFORMSOCIALCREDITCODE'];
                $data['Corporation']             = $v['CORPORATION'];
                $data['RegistrationAuthority']   = $v['REGISTRATIONAUTHORITY'];
                $data['RegistrationStatus']      = $v['REGISTRATIONSTATUS'];
                $data['RegisterFund']            = $v['REGISTERFUND'];
                $data['WorkAddress']             = $v['WORKADDRESS'];
                $data['BusinessScope']           = $v['BUSINESSSCOPE'];
                $data['EstablishDate']           = $v['ESTABLISHDATE'];
                $data['BusinessDateFrom']        = $v['BUSINESSDATEFROM'];
                $data['BusinessDateTo']          = $v['BUSINESSDATETO'];
                $data['RegistrationDate']        = $v['REGISTRATIONDATE'];
                $data['LicenceCode']             = $v['LICENCECODE'];
                $data['IsUsed']                  = $v['ISUSED'];
                $data['IsUsedCode']              = $v['ISUSEDCODE'];
                $data['CreateDataTime']          = $v['CREATEDATATIME'];

                $res = M('jk_provider_mdm')->add($data);

                if(!$res){
                    $ret['status']=0;
                    echo "出错语句为：".M()->getLastSql();die;
                }
            }else{
                $data['Providernumber']          = $v['PROVIDERNUMBER'];
                $data['ProviderName']            = $v['PROVIDERNAME'];
                $data['ProviderType']            = $v['PROVIDERTYPE'];
                $data['UniformSocialCreditCode'] = $v['UNIFORMSOCIALCREDITCODE'];
                $data['Corporation']             = $v['CORPORATION'];
                $data['RegistrationAuthority']   = $v['REGISTRATIONAUTHORITY'];
                $data['RegistrationStatus']      = $v['REGISTRATIONSTATUS'];
                $data['RegisterFund']            = $v['REGISTERFUND'];
                $data['WorkAddress']             = $v['WORKADDRESS'];
                $data['BusinessScope']           = $v['BUSINESSSCOPE'];
                $data['EstablishDate']           = $v['ESTABLISHDATE'];
                $data['BusinessDateFrom']        = $v['BUSINESSDATEFROM'];
                $data['BusinessDateTo']          = $v['BUSINESSDATETO'];
                $data['RegistrationDate']        = $v['REGISTRATIONDATE'];
                $data['LicenceCode']             = $v['LICENCECODE'];
                $data['IsUsed']                  = $v['ISUSED'];
                $data['IsUsedCode']              = $v['ISUSEDCODE'];
                $data['CreateDataTime']          = $v['CREATEDATATIME'];

                $res = M('jk_provider_mdm')->where("Providernumber='".$v['PROVIDERNUMBER']."'")->save($data);
                /* if(!$res){
                 $ret['status']=0;
                 echo M()->getLastSql();die;
                } */

            }
        }
        echo json_encode($ret);
        return json_encode($ret);

    }
    /**
     * 函数用途描述：主数据接收项目增量分发服务的接口
     * @date: 2017年8月7日 下午6:04:09
     * @author: tanjiewen
     * @param: $data 传入数据,json串
     * @return:
     */
    public function mdmSetProMasterData($json_data) {
        //获取项目主数据
        //返回值json转数组
        $result=json_decode($json_data->return);
        $result=json_decode(json_encode($result),true);
        //dump($result);die;

        //遍历项目主数据，同步项目主数据信息
        foreach ($result['returnData'] as $v){
            //查看对应的projecnumber的本地项目表是否存在，如果不存在则添加

            $is_find = M('jk_project')->where("ProjectNumber='".$v['data'][0]['PROJECTNUMBER']."'")->getField('id');
            if(!$is_find){
                //组装新增项目的数组
                $data['PROJECTNUMBER'] = $v['data'][0]['PROJECTNUMBER'];
                $data['name']          = $v['data'][0]['PROJNAME'];
                $data['other_name']    = $v['data'][0]['PROJSHORTNAME'];
                $data['ProjectNumber'] = $v['data'][0]['PROJECTNUMBER'];
                $data['create_time']   = strtotime($v['data'][0]['CREATEDATATIME']);
                $data['pid'] = 24;//集团总部
                if($v['data'][0]['ISUSEDCODE']==0){
                    $data['status'] = 1;
                }else if($v['data'][0]['ISUSEDCODE']==1){
                    $data['status'] = 0;
                }else if($v['data'][0]['ISUSEDCODE']==2){
                    $data['status'] = -1;
                }

                M('jk_project')->add($data);
                //dump($data);die;
            }else{

                $new_data['PROJECTNUMBER'] = $v['data'][0]['PROJECTNUMBER'];
                $new_data['name']          = $v['data'][0]['PROJNAME'];
                $new_data['other_name']    = $v['data'][0]['PROJSHORTNAME'];
                $new_data['ProjectNumber'] = $v['data'][0]['PROJECTNUMBER'];
                $new_data['create_time']   = strtotime($v['data'][0]['CREATEDATATIME']);
                if($v['data'][0]['ISUSEDCODE']==0){
                    $new_data['status'] = 1;
                }else if($v['data'][0]['ISUSEDCODE']==1){
                    $new_data['status'] = 0;
                }else if($v['data'][0]['ISUSEDCODE']==2){
                    $new_data['status'] = -1;
                }
                //更新该项
                M('jk_project')->where("id='".$is_find."'")->save($new_data);
                //dump($v['data'][0]);
                //die;
            }
        }
        $ret['status']=1;
        return json_encode($ret);

    }
    /**
     * 函数用途描述：被动接收项目增量分发服务的接口
     * @date: 2017年8月7日 下午6:04:09
     * @author: tanjiewen
     * @param: $data 传入数据,json串
     * @return:
     */
    public function mdmSetSomeProMasterData($json_data) {
        //获取项目主数据
        //返回值json转数组
        file_put_contents('ceshi.txt', $json_data);
        $result=json_decode($json_data);
        $result=json_decode(json_encode($result),true);
        //dump($result);die;

        //遍历项目主数据，同步项目主数据信息
        foreach ($result as $v){
            //查看对应的projecnumber的本地项目表是否存在，如果不存在则添加

            $is_find = M('jk_project')->where("ProjectNumber='".$v['PROJECTNUMBER']."'")->getField('id');
            if(!$is_find){
                //组装新增项目的数组
                //$data['PROJECTNUMBER'] = $v['PROJECTNUMBER'];
                $data['name']          = $v['PROJNAME'];
                $data['other_name']    = $v['PROJSHORTNAME'];
                $data['ProjectNumber'] = $v['PROJECTNUMBER'];
                $data['create_time']   = strtotime($v['CREATEDATATIME']);
                $data['update_time']   = strtotime($v['CREATEDATATIME']);
                if($v['ISUSEDCODE']==0){
                    $data['status'] = 1;
                }else if($v['ISUSEDCODE']==1){
                    $data['status'] = 0;
                }else if($v['ISUSEDCODE']==2){
                    $data['status'] = -1;
                }

                M('jk_project')->add($data);
                //dump($data);die;
            }else{

                //$new_data['PROJECTNUMBER'] = $v['PROJECTNUMBER'];
                $new_data['name']          = $v['PROJNAME'];
                $new_data['other_name']    = $v['PROJSHORTNAME'];
                $new_data['ProjectNumber'] = $v['PROJECTNUMBER'];
                $new_data['create_time']   = strtotime($v['CREATEDATATIME']);
                $new_data['update_time']   = time();
                if($v['ISUSEDCODE']==0){
                    $new_data['status'] = 1;
                }else if($v['ISUSEDCODE']==1){
                    $new_data['status'] = 0;
                }else if($v['ISUSEDCODE']==2){
                    $new_data['status'] = -1;
                }
                //更新该项
                M('jk_project')->where("id='".$is_find."'")->save($new_data);
                //dump($v['data'][0]);
                //die;
            }
        }
        $ret['status']=1;
        return json_encode($ret);

    }
    /**
     * 函数用途描述：调用MDM分发分期服务功能
     * @date: 2017年8月78日 上午9:45:09
     * @author: tanjiewen
     * @param: 分期类别代码
     * @return:
     */
    public function mdmGetStageMasterData($masterClass='001002') {
        //获取项目主数据
        $soap = $this->soap;
        $result = $soap->mdmGetMasterData(array(
            'masterClass'=>$masterClass,
            'masterCode'=>'',

        ));
        dump($result);die;
        //$this->mdmMasterSetData('001002',$result);
        dump($this->mdmSetStageMasterData($result));
    }
    /**
     * 函数用途描述：被动接收分期增量分发服务的接口
     * @date: 2017年8月8日 上午9:54:09
     * @author: tanjiewen
     * @param: $data 传入数据,json串
     * @return:
     */
    public function mdmSetStageMasterData($json_data) {
        //获取项目主数据
        //返回值json转数组
        $result=json_decode($json_data->return);
        $result=json_decode(json_encode($result),true);

        //遍历分期主数据，同步分期主数据信息
        foreach ($result['returnData'] as $v){
            //查看对应的projecnumber的本地项目表是否存在，如果不存在则添加
            $is_find = M('jk_stage')->where("StagesCode='".$v['data'][0]['STAGESCODE']."'")->getField('id');
            if(!$is_find){
                //组装新增分期的数组
                $data['StagesCode']     = $v['data'][0]['STAGESCODE'];
                $data['StagesName']     = $v['data'][0]['STAGESNAME'];
                $data['ProjName']       = $v['data'][0]['PROJNAME'];
                $data['ParentCode']     = $v['data'][0]['PROJECTNUMBER'];
                $data['CreateDataTime'] = $v['data'][0]['CREATEDATATIME'];
                if($v['data'][0]['ISUSEDCODE']==0){
                    $data['status'] = 1;
                }else if($v['data'][0]['ISUSEDCODE']==1){
                    $data['status'] = 0;
                }else if($v['data'][0]['ISUSEDCODE']==2){
                    $data['status'] = -1;
                }
                M('jk_stage')->add($data);
                //dump($data);die;
            }else{

                $new_data['StagesCode']     = $v['data'][0]['STAGESCODE'];
                $new_data['StagesName']     = $v['data'][0]['STAGESNAME'];
                $new_data['ProjName']       = $v['data'][0]['PROJNAME'];
                $new_data['ParentCode']     = $v['data'][0]['PROJECTNUMBER'];
                $new_data['CreateDataTime'] = $v['data'][0]['CREATEDATATIME'];
                if($v['data'][0]['ISUSEDCODE']==0){
                    $new_data['status'] = 1;
                }else if($v['data'][0]['ISUSEDCODE']==1){
                    $new_data['status'] = 0;
                }else if($v['data'][0]['ISUSEDCODE']==2){
                    $new_data['status'] = -1;
                }
                //更新该项
                M('jk_stage')->where("id='".$is_find."'")->save($new_data);
                //dump($new_data);die;
            }
        }
        $ret['status']=1;
        return json_encode($ret);
    }
    /**
     * 函数用途描述：被动接收分期增量分发服务的接口
     * @date: 2017年8月8日 上午9:54:09
     * @author: tanjiewen
     * @param: $data 传入数据,json串
     * @return:
     */
    public function mdmSetSomeStageMasterData($json_data) {
        //获取项目主数据
        //返回值json转数组
        $result=json_decode($json_data);
        $result=json_decode(json_encode($result),true);
        // dump($result);die;
        //遍历分期主数据，同步分期主数据信息
        foreach ($result as $v){
            //查看对应的projecnumber的本地项目表是否存在，如果不存在则添加
            $is_find = M('jk_stage')->where("StagesCode='".$v['STAGESCODE']."'")->getField('id');
            if(!$is_find){
                //组装新增分期的数组
                $data['StagesCode']     = $v['STAGESCODE'];
                $data['StagesName']     = $v['STAGESNAME'];
                $data['ProjName']       = $v['PROJNAME'];
                $data['ParentCode']     = $v['PROJECTNUMBER'];
                $data['CreateDataTime'] = $v['CREATEDATATIME'];
                $data['UpdateDataTime'] = date("Y-m-d H:i:s");;
                if($v['ISUSEDCODE']==0){
                    $data['status'] = 1;
                }else if($v['ISUSEDCODE']==1){
                    $data['status'] = 0;
                }else if($v['ISUSEDCODE']==2){
                    $data['status'] = -1;
                }
                M('jk_stage')->add($data);
                //dump($data);die;
            }else{

                $new_data['StagesCode']     = $v['STAGESCODE'];
                $new_data['StagesName']     = $v['STAGESNAME'];
                $new_data['ProjName']       = $v['PROJNAME'];
                $new_data['ParentCode']     = $v['PROJECTNUMBER'];
                $new_data['CreateDataTime'] = $v['CREATEDATATIME'];
                $new_data['UpdateDataTime'] = date("Y-m-d H:i:s");;
                if($v['ISUSEDCODE']==0){
                    $new_data['status'] = 1;
                }else if($v['ISUSEDCODE']==1){
                    $new_data['status'] = 0;
                }else if($v['ISUSEDCODE']==2){
                    $new_data['status'] = -1;
                }
                //更新该项
                M('jk_stage')->where("id='".$is_find."'")->save($new_data);
                //dump($new_data);die;
            }
        }
        $ret['status']=1;
        return json_encode($ret);
    }
    /**
     * 函数用途描述：调用MDM分发楼栋服务功能
     * @date: 2017年8月8日 上午9:45:09
     * @author: tanjiewen
     * @param: 楼栋类别代码
     * @return:
     */
    public function mdmGetBuildMasterData($masterClass='001003') {
        //获取项目主数据
        $soap = $this->soap;
        $result = $soap->mdmGetMasterData(array(
            'masterClass'=>$masterClass,
            'masterCode'=>'',

        ));
        dump($this->mdmSetBuildMasterData($result));
    }
    /**
     * 函数用途描述：调用MDM分发组织服务功能
     * @date: 2017年10月31日
     * @author: tanjiewen
     * @param: 组织类别代码
     * @return:
     */
    public function mdmGetOrganizeMasterData($masterClass='002002') {
        //获取项目主数据
        set_time_limit(60);
        $soap = $this->soap;
        $result = $soap->mdmGetMasterData(array(
            'masterClass'=>$masterClass,
            'masterCode'=>'',

        ));
        //$this->mdmSetOrganize($result);
        //dump($result);
        if($masterClass=='002001'){
            dump($this->mdmSetOrganize($result));
        }elseif($masterClass=='002002'){
            dump($this->mdmSetPerson($result));
        }elseif($masterClass=='002002001'){
            dump($this->mdmSetAccount($result));
        }

    }

    public function mdmSetBuildtest(){
        $map['pid']        = 0;
        $map['masterCode'] = 'Z102017185050088';
        $is_find = M('jk_floor')->where($map)->getField('id');
        if(!$is_find){dump('1');}
        dump($is_find);

    }
    /**
     * 函数用途描述：被动接收楼栋增量分发服务的接口
     * @date: 2017年8月8日 上午10:54:09
     * @author: tanjiewen
     * @param: $data 传入数据,json串
     * @return:
     */
    public function mdmSetBuildMasterData($json_data) {
        //获取项目主数据
        //返回值json转数组
        set_time_limit(120);
        $result=json_decode($json_data->return);
        $result=json_decode(json_encode($result),true);
        //遍历楼栋主数据，同步楼栋主数据信息
        foreach ($result['returnData'] as $v){

            //查看对应的masterCode的本地楼栋表是否存在，如果不存在则添加
            $map['pid']        = 0;
            $map['masterCode'] = $v['data'][0]['BUILDNUMBER'];
            $is_find = M('jk_floor')->where($map)->getField('id');
            if(!$is_find){
                //组装新增楼栋的数组
                $data['title']       = $v['data'][0]['BLDNAME'];
                $data['create_time'] = $v['data'][0]['CREATEDATATIME'];
                $data['pid']         = 0;
                $data['sort']        = 0;
                $data['masterCode']  = $v['data'][0]['BUILDNUMBER'];
                $data['StagesCode']  = $v['data'][0]['STAGESCODE'];
                $data['StagesName']  = $v['data'][0]['STAGESNAME'];
                $data['examine']     = 0;
                if($v['data'][0]['ISUSEDCODE']==0){
                    $data['status'] = 1;
                }else if($v['data'][0]['ISUSEDCODE']==1){
                    $data['status'] = 0;
                }else if($v['data'][0]['ISUSEDCODE']==2){
                    $data['status'] = -1;
                }
                //找出楼栋所属项目并绑定
                $ParentCode = M('jk_stage')->where("StagesCode='".$data['StagesCode']."'")->getField("ParentCode");
                $projectid  = M('jk_project')->where("ProjectNumber='".$ParentCode."'")->getField('id');
                if($projectid){

                    //dump($data);
                    $data['projectid']=$projectid;
                    M('jk_floor')->add($data);
                }
            }else{

                $new_data['title']       = $v['data'][0]['BLDNAME'];
                $new_data['create_time'] = $v['data'][0]['CREATEDATATIME'];
                $new_data['pid']         = 0;
                $new_data['sort']        = 0;
                $new_data['masterCode']  = $v['data'][0]['BUILDNUMBER'];
                $new_data['StagesCode']  = $v['data'][0]['STAGESCODE'];
                $new_data['StagesName']  = $v['data'][0]['STAGESNAME'];
                $new_data['examine']     = 0;
                if($v['data'][0]['ISUSEDCODE']==0){
                    $new_data['status'] = 1;
                }else if($v['data'][0]['ISUSEDCODE']==1){
                    $new_data['status'] = 0;
                }else if($v['data'][0]['ISUSEDCODE']==2){
                    $new_data['status'] = -1;
                }
                //更新该项
                //找出楼栋所属项目并绑定
                $ParentCode = M('jk_stage')->where("StagesCode='".$new_data['StagesCode']."'")->getField("ParentCode");
                $projectid  = M('jk_project')->where("ProjectNumber='".$ParentCode."'")->getField('id');
                if($projectid){
                    $new_data['projectid']=$projectid;
                    M('jk_floor')->where("id='".$is_find."'")->save($new_data);
                }


            }
        }
        $ret['status']=1;
        return json_encode($ret);
    }
    /**
     * 函数用途描述：被动接收楼栋增量分发服务的接口
     * @date: 2017年8月8日 上午10:54:09
     * @author: tanjiewen
     * @param: $data 传入数据,json串
     * @return:
     */
    public function mdmSetSomeBuildMasterData($json_data) {
        //获取项目主数据
        //返回值json转数组
        file_put_contents('ceshi.txt', $json_data);
        set_time_limit(120);
        $result=json_decode($json_data);
        $result=json_decode(json_encode($result),true);
        $ret['status']=1;
        //遍历楼栋主数据，同步楼栋主数据信息
        foreach ($result as $v){
            //查看对应的masterCode的本地楼栋表是否存在，如果不存在则添加
            $map['pid']        = 0;
            $map['masterCode'] = $v['BUILDNUMBER'];
            $is_find = M('jk_floor')->where($map)->getField('id');
            if(!$is_find){
                //组装新增楼栋的数组
                $data['title']       = $v['BLDNAME'];
                $data['create_time'] = $v['CREATEDATATIME'];
                $data['pid']         = 0;
                $data['sort']        = 0;
                $data['masterCode']  = $v['BUILDNUMBER'];
                $data['StagesCode']  = $v['STAGESCODE'];
                $data['StagesName']  = $v['STAGESNAME'];
                $data['create_time'] = time();
                $data['update_time'] = time();
                $data['up_date']     = date('Y-m-d H:i:s',time());
                $data['examine']     = 0;
                if($v['ISUSEDCODE']==0){
                    $data['status'] = 1;
                }else if($v['ISUSEDCODE']==1){
                    $data['status'] = 0;
                }else if($v['ISUSEDCODE']==2){
                    $data['status'] = -1;
                }
                //找出楼栋所属项目并绑定
                $ParentCode = M('jk_stage')->where("StagesCode='".$data['StagesCode']."'")->getField("ParentCode");
                $projectid  = M('jk_project')->where("ProjectNumber='".$ParentCode."'")->getField('id');
                if($projectid){
                    //dump($data);
                    $data['projectid']=$projectid;
                    M('jk_floor')->add($data);
                }else{
                    M('jk_floor')->add($data);
                    $ret['status']=0;
                }
            }else{

                $new_data['title']       = $v['BLDNAME'];
                $new_data['create_time'] = $v['CREATEDATATIME'];
                $new_data['pid']         = 0;
                $new_data['sort']        = 0;
                $new_data['masterCode']  = $v['BUILDNUMBER'];
                $new_data['StagesCode']  = $v['STAGESCODE'];
                $new_data['StagesName']  = $v['STAGESNAME'];
                $new_data['create_time'] = time();
                $new_data['update_time'] = time();
                $new_data['up_date']     = date('Y-m-d H:i:s',time());

                if($v['ISUSEDCODE']==0){
                    $new_data['status'] = 1;
                }else if($v['ISUSEDCODE']==1){
                    $new_data['status'] = 0;
                }else if($v['ISUSEDCODE']==2){
                    $new_data['status'] = -1;
                }
                //更新该项
                //找出楼栋所属项目并绑定
                $ParentCode = M('jk_stage')->where("StagesCode='".$new_data['StagesCode']."'")->getField("ParentCode");
                $projectid  = M('jk_project')->where("ProjectNumber >'' and ProjectNumber='".$ParentCode."'")->getField('id');
                if($projectid){
                    $new_data['projectid']=$projectid;
                    M('jk_floor')->where("id='".$is_find."'")->save($new_data);
                }else{
                    M('jk_floor')->where("id='".$is_find."'")->save($new_data);
                    $ret['status']=0;
                }


            }
        }

        return json_encode($ret);
    }
    /**
     * 函数用途描述：变更主数据信息
     * @date: 2017年8月10日 上午9:43:09
     * @author: tanjiewen
     * @param: $param 传入数据,json串
     * @return:
     */
    public function mdmMasterDataChang($param) {
        $header = new \SoapHeader($this->ws, 'OperationCode', 'com.jinke.esb.provider.mdm.getCode.mdmMasterDataChang');

        $header1 = new \SoapHeader($this->ws, 'ClientId', 'com.jinke.esb.consumer.irosn');
        $this->soap->__setSoapHeaders(array($header,$header1));
        $soap = $this->soap;

        $result = $soap->mdmMasterDataChang(array(
            'masterDateJson'=>$param,
        ));
        return $result;
    }
    /**
     * 函数用途描述：获取楼栋新增的下属房间，并生成房间编码->注册房间编码
     * @date: 2017年08月01日 下午 14:03:05
     * @author: tanjiewen
     * @param: id：楼栋id
     * @return:
     */
    public function generate_register_code($id=0){
        set_time_limit(0);//数据较大费时较久
        //实例化mdm类
        $soap = new JKMdmController();
        //返回值数组
        $back['status'] = 0;
        $back['reason'] = '';
        $masterDataJson = array();
        $masterDataJson['masterCategory']='001004';
        $masterDataJson['data']=array();
        $BuildNumber = M('jk_floor')->where('id='.$id)->getField('masterCode');
        if(!$BuildNumber){
            $back['reason'] = '楼栋编码有误,请联系管理员';
            return $back;
        }
        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("(RoomNumber IS NULL || RoomNumber ='') and IsUsedCode=0 and build_id=".$id)->select();
        if(count($rooms)==0){

            $back['reason'] = '已新增该房间';
            return $back;
        }

        $i=0;
        //构造数组
        foreach ($rooms as $room){
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT']=$room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON']='生成编码申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOM']=$room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER']=$BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR']=$room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR']=$room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT']=$room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME']=$room['CreateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED']=$room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE']=$room['IsUsedCode'];

            $i++;
        }
        //数组转json
        $masterDataJson=json_encode($masterDataJson);

        //dump($masterDataJson);
        $last_time=time();
        $result = $soap->mdmMasterDataGenCode($masterDataJson);
        //json转数组
        //$new_time=time();
        //echo $new_time-$last_time.'<br />';
        //echo $BuildNumber."<br />";
        //dump($result);die;
        $result = json_decode($result->return);
        if($result->state!=1){
            $back['reason'] = '编码未全部生成成功';
            return $back;
        }
        $ids = array();//记录生成的编码
        $j=0;
        //遍历返回数组
        //file_put_contents('ceshi.txt', json_encode($rooms));
        //file_put_contents('ceshi1.txt',json_encode($result->data));
        foreach ($result->data as $v){
            //该房间编码生成成功
            if($v->state==1){
                $ids[] = $rooms[$j]['id'];
                $save['RoomNumber'] = $v->data->ROOMNUMBER;
                M('jk_room_mdm')->where("id='".$rooms[$j]['id']."'")->save($save);
            }
            $j++;
        }
        //注册房间信息
        //查询该楼栋下的已生成编码的房间
        $masterDataJson = array();
        $masterDataJson['masterCategory']='001004';
        $masterDataJson['data']=array();
        //查询出对应的房间信息
        $where['build_id'] = $id;
        $where['IsUsedCode'] = 0;
        $where['id']       = array('in', $ids);
        $rooms = M('jk_room_mdm')->where($where)->select();
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i=0;
        //构造数组
        foreach ($rooms as $room){
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT']=$room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON']='注册房间信息申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER']=$room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM']=$room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO']=$room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER']=$BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR']=$room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR']=$room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT']=$room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO']=$room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME']=$room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED']=$room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE']=$room['IsUsedCode'];

            //     		if($i==0)
            //     			break;
            $i++;
        }
        //数组转json
        $masterDataJson=json_encode($masterDataJson);
        $last_time=time();
        $result = $soap->mdmMasterDataregistration($masterDataJson);
        //json转数组
        $result = json_decode($result->return) ;
        //全部注册成功
        $save = array();
        if($result->state==1){
            $save['examine'] = 2;
            $save['update_time'] = time();
            $ret=M('jk_floor')->where("id=".$id)->save($save);
            if($ret){
                $back['status'] = 1;
            }else{
                $back['reason'] = '房间编码注册成功,楼栋状态更改失败' ;
            }
        }else{
            $back['reason'] = '未全部注册成功，请查看是否已生成房间编码' ;

        }
        dump($back);
        return $back;

    }
    /**
     * 函数用途描述：获取楼栋所有下属房间，并生成房间编码->注册房间编码
     * @date: 2017年08月01日 下午 14:03:05
     * @author: tanjiewen
     * @param: id：楼栋id
     * @return:
     */
    public function main_generate_register_code($id=0){
        set_time_limit(0);//数据较大费时较久
        $time1 = time();
        file_put_contents('rdata1.txt', "in main_generate_register_code\n", FILE_APPEND);
        //实例化mdm类
        $soap = new JKMdmController();

        //返回值数组
        $back['status'] = 0;
        $back['reason'] = '';
        $masterDataJson = array();
        $masterDataJson['masterCategory']='001004';
        $masterDataJson['data']=array();
//     	$BuildNumber = M('jk_floor')->where('id='.$id)->getField('masterCode');
//     	if(!$BuildNumber){
//     		$back['reason'] = '楼栋编码有误,请联系管理员';
//     		return $back;
//     	}
        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("(RoomNumber IS NULL || RoomNumber ='') and IsUsedCode=0  and build_id in(".$id.")")->select();

        file_put_contents('sql.txt', M()->getLastSql(), FILE_APPEND);

        if(count($rooms)==0){
            $back['reason'] = '编码已生成';
            //return $back;
        }else{
            $i=0;
            //构造数组
            foreach ($rooms as $room){
                $masterDataJson['data'][$i]['applyinfo']['APPLICANT']=$room['id'];
                $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON']='生成编码申请';
                $masterDataJson['data'][$i]['bussinessdata']['ROOM']=$room['Room'];
                $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER']=$room['BuildNumber'];
                $masterDataJson['data'][$i]['bussinessdata']['FLOOR']=$room['Floor'];
                $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR']=$room['AbsolutelyFloor'];
                $masterDataJson['data'][$i]['bussinessdata']['UNIT']=$room['Unit'];
                $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME']=$room['CreateDataTime'];
                $masterDataJson['data'][$i]['bussinessdata']['ISUSED']=$room['IsUsed'];
                $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE']=$room['IsUsedCode'];
                $i++;
            }
            //数组转json
            $masterDataJson=json_encode($masterDataJson);
            // file_put_contents('soaptest.log', '222');

            $result = $soap->mdmMasterDataGenCode($masterDataJson);
            //$this->error('测试');
            //json转数组
            $result = json_decode($result->return);
            //dump($result);die;
            if($result->state!=1){
                $back['reason'] = '编码未全部生成成功';
                return $back;

            }
        }

        $ids = array();//记录生成的编码
        $j=0;
        //遍历返回数组

        foreach ($result->data as $v){
            //该房间编码生成成功
            if($v->state==1){
                $ids[] = $rooms[$j]['id'];
                $save=array();
                $save['masterCode'] = $v->data->ROOMNUMBER;
                M('jk_floor')->where("id='".$rooms[$j]['id']."'")->save($save);
            }
            $j++;
        }

        //注册房间信息
        //查询该楼栋下的已生成编码的房间
        $masterDataJson = array();
        $masterDataJson['masterCategory']='001004';
        $masterDataJson['data']=array();
        //查询出对应的房间信息
        //$where['build_id'] = $id;
        $where['build_id'] = array('in', $id);
        $where['IsUsedCode'] = 0;
        $where['isR'] = 0;
        $where['RoomNumber'] = array('gt','');
        $rooms = M('jk_room_mdm')->where($where)->select();

        if(!$rooms){
            file_put_contents('ceshido_room_send.txt', M('jk_room_mdm')->_sql()."\n", FILE_APPEND);
            file_put_contents('rdata1.txt', "allR\n", FILE_APPEND);
            $save=array();
            $save['examine']     = 2;
            $save['update_time'] = time();
            $ret=M('jk_floor')->where("id in(".$id.")")->save($save);

            $back['status'] = $ret;

            return $back;
        }
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i=0;
        //构造数组

        foreach ($rooms as $room){
            $roomNumber=$room['RoomNumber'];
            $hcount=M('jk_room_m')->where("ROOMNUMBER='$roomNumber'")->count();
            //file_put_contents('mdmh.txt', M('jk_room_m')->_sql()."\n");
            if($hcount>0){
                //file_put_contents('mdmh.txt', "$hcount\n", FILE_APPEND);
                //continue;
            }
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT']=$room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON']='注册房间信息申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER']=$room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM']=$room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO']=$room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER']=$room['BuildNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR']=$room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR']=$room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT']=$room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO']=$room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME']=$room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED']=$room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE']=$room['IsUsedCode'];

            //     		if($i==0)
            //     			break;
            $i++;
        }

        if(!$masterDataJson['data']){
            file_put_contents('rdata1.txt', "3\n", FILE_APPEND);
            $ids=array_column($rooms, 'id');
            $wc['id'] = array('in',$ids);
            file_put_contents('rdata1.txt', "all\n", FILE_APPEND);
            M('jk_floor')->where($wc)->save(array('isR'=>1));

            // M('jk_room_mdm')->where($where)->save(array('isR'=>1));

            file_put_contents('rdata1.txt', M('jk_floor')->_sql()."\n", FILE_APPEND);
            $save=array();
            $save['examine']     = 2;
            $save['update_time'] = time();
            $ret=M('jk_floor')->where("id in(".$id.")")->save($save);

            $back['status'] = $ret;

            return $back;
        }
        file_put_contents('rdata1.txt', "1\n", FILE_APPEND);
        //数组转json
        $masterDataJson=json_encode($masterDataJson);
        file_put_contents('rdata.txt', $masterDataJson."\n", FILE_APPEND);
        $last_time=time();

        $result = $soap->mdmMasterDataregistration($masterDataJson);
        // dump($result);
        //json转数组
        file_put_contents('rdata1.txt', "2\n", FILE_APPEND);

        $result = json_decode($result->return) ;

        //全部注册成功
        // dump($result);
        // dump($masterDataJson);

        // die;
        $save = array();

        if($result->state==1||$result->errocode=='000004'){
            $ids=array_column($rooms, 'id');
            $wc['id'] = array('in',$ids);
            M('jk_floor')->where($wc)->save(array('isR'=>1));
            if($result->state==1){
                file_put_contents('rdata1.txt', $result->state."notall\n", FILE_APPEND);
            }
            else{
                file_put_contents('rdata1.txt', json_encode($result)."notall\n", FILE_APPEND);
            }

            //file_put_contents('rdata1.txt', "notall\n", FILE_APPEND);
            file_put_contents('rdata1.txt', M('jk_floor')->_sql()."\n", FILE_APPEND);
            // M('jk_room_mdm')->where($where)->save(array('isR'=>1));
            $save['examine']     = 2;
            $save['update_time'] = time();
            $ret=M('jk_floor')->where("id in(".$id.")")->save($save);
            $time2 = $time1-time();
            file_put_contents('time.log', $time2);
            if($ret){
                $back['status'] = 1;

                return $back;
            }else{
                $back['reason'] = '房间编码注册成功,楼栋状态更改失败';
                return $back;

            }
        }else{
            file_put_contents('rdata1.txt', json_encode($result)."\n", FILE_APPEND);
            $back['reason'] = '未全部注册成功，请查看是否已生成房间编码';
            return $back;

        }
        //return $back;
    }
    /**
     * 函数用途描述：房间信息变更
     * @date: 2017年08月11日 上午 11:23:05
     * @author: tanjiewen
     * @param: id：楼栋id ， $ids ；变更房间ID
     * @return:
     */
    public function update_examine_floor($id)
    {
        set_time_limit(0);
        //查询该楼栋下的所有房间
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        //查询出删除的房间信息
        $rooms = M('jk_room_mdm')->where("RoomNumber > '' and IsUsedCode=2  and build_id in(".$id.")")->select();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');
//     	$where['IsUsedCode'] = 2;
//     	$where['RoomNumber'] = array('gt', '');
//     	$rooms = M('jk_room_mdm')->where($where)->select();
        if (count($rooms) == 0 || !$rooms) {
            return true;
        }
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            //mastecode
            $masterDataJson['data'][$i]['masterCode'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '信息变更申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO'] = $room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO'] = $room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];
            $i++;
        }
        //数组转json

        $masterDataJson = json_encode($masterDataJson);
        file_put_contents('delete_log.log', $masterDataJson);
        $soap = new JKMdmController();

        $result = $soap->mdmMasterDataChang($masterDataJson);

        $result = json_decode($result->return);
        file_put_contents('ceshi5.txt', json_encode($result));
        //全部变更成功
        if ($result->state == 1) {
            //return true;
        } else {
            //return false;
        }
    }

    public function update_building($id){
        set_time_limit(0);
        //查询该楼栋下的所有房间
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        //查询出删除的房间信息
        $rooms = M('jk_room_mdm')->where("RoomNumber > '' and IsUsedCode=0  and build_id in(".$id.")")->select();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');
//     	$where['IsUsedCode'] = 2;
//     	$where['RoomNumber'] = array('gt', '');
//     	$rooms = M('jk_room_mdm')->where($where)->select();
        if (count($rooms) == 0 || !$rooms) {
            return true;
        }
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            //mastecode
            $masterDataJson['data'][$i]['masterCode'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '信息变更申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO'] = $room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO'] = $room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];
            $i++;
        }
        //数组转json

        $masterDataJson = json_encode($masterDataJson);
        file_put_contents('delete_log.log', $masterDataJson);
        $soap = new JKMdmController();

        $result = $soap->mdmMasterDataChang($masterDataJson);

        $result = json_decode($result->return);
        file_put_contents('ceshi5.txt', json_encode($result));
    }

    /**
     * 函数用途描述：快速创建mdm对应的楼栋信息
     * @date: 2017年9月18日 下午3:10:54
     * @author: luojun
     * @param: $code=>mdm项目标准码,$pid=>项目id
     * @return:
     */
    public function addMdmData($code,$pid)
    {
        set_time_limit(0); // 数据较大费时较久
        $done = M('jk_project')->where("id=$pid")->getField('done');
        file_put_contents('mdmpro.txt', "1\n", FILE_APPEND);
        if ((! $done||$done==3)&& $code) { // 未进行处理且项目标准码存在

            M('jk_project')->where("id=$pid")->save(array(
                'done' => 2
            )); // 项目历史数据处理中，防止多次调用
            // 获取项目标准码下的楼栋列表
            $builds = M('jk_build_tmp')->where("PROJECTNUMBER='$code' AND sys=0")
                ->field('distinct(BUILDNUMBER),BLDNAME,STAEGENUMBER')->limit(10)
                ->select();
            $flag = 1;
            $sta = time();
            foreach ($builds as $build) {
                // 新建对应信息的楼栋
                $info = array();
                $info['projectid'] = $pid;
                $info['create_time'] = time();
                $info['update_time'] = time();
                $info['pid'] = 0;
                $info['status'] = 1;
                $info['up_date'] = date('Y-m-d H:i:s', time());

                $info['masterCode'] = $build['BUILDNUMBER'];
                $info['StagesCode'] = $build['STAEGENUMBER'];
                $info['title'] = $build['BLDNAME'];
                $info['examine'] = 0;
                $bcode = $build['BUILDNUMBER'];
                // $bid = M('jk_floor')->where("projectid=$pid AND masterCode='$bcode'")->getField('id');
                // if (! $bid)
                $bid = M('jk_floor')->add($info); // 新增楼栋
                M('jk_build_tmp')->where("PROJECTNUMBER='$code' AND sys=0 AND BUILDNUMBER = '$bcode'")
                    ->save(array('sys'=>1));
                // 获取ERP楼栋单元信息，楼层信息，房间信息

                $units = M('jk_room_m')->where("BUILDNUMBER='$bcode'")
                    ->field("distinct(UNITNO),UNIT")
                    ->order('UNITNO')
                    ->select();
                if ($flag) {
                    file_put_contents('mdmpro.txt', M('jk_floor')->_sql() . json_encode($units) . $bid . "\r\n", FILE_APPEND);
                    // $flag=0;
                }
                foreach ($units as $unit) {
                    $uinfo = array();
                    $uinfo['projectid'] = $pid;
                    $uinfo['create_time'] = time();
                    $uinfo['update_time'] = time();

                    $uinfo['pid'] = $bid;

                    $uinfo['status'] = 1;
                    $uinfo['up_date'] = date('Y-m-d H:i:s', time());

                    $uinfo['title'] = $unit['UNIT'] == null ? '' : $unit['UNIT'];
                    $uinfo['sort'] = $unit['UNITNO'];
                    $unitno = $unit['UNITNO'];
                    // $uid = M('jk_floor')->where("projectid=$pid AND pid=$bid AND sort=$unitno")->getField('id');
                    // if (! $uid)
                    $uid = M('jk_floor')->add($uinfo); // 新增单元

                    $floors = M('jk_room_m')->where("BUILDNUMBER='$bcode' AND UNITNO=$unitno")
                        ->field("distinct(ABSOLUTELYFLOOR),FLOOR")
                        ->order('ABSOLUTELYFLOOR')
                        ->select();
                    if ($flag) {
                        file_put_contents('mdmpro.txt', M('jk_floor')->_sql() . json_encode($floors) . $uid . "\r\n", FILE_APPEND);
                        // $flag=0;
                    }
                    foreach ($floors as $floor) {
                        $finfo = array();
                        $finfo['projectid'] = $pid;
                        $finfo['create_time'] = time();
                        $finfo['update_time'] = time();
                        $finfo['up_date'] = date('Y-m-d H:i:s', time());
                        $finfo['pid'] = $uid;
                        $finfo['status'] = 1;
                        $finfo['title'] = $floor['FLOOR'];
                        $finfo['sort'] = $floor['ABSOLUTELYFLOOR'];
                        $finfono = $floor['ABSOLUTELYFLOOR'];

                        $fid = M('jk_floor')->where("projectid=$pid AND pid=$uid AND sort=$finfono")->getField('id');
                        if (! $fid)
                            $fid = M('jk_floor')->add($finfo); // 新增楼层

                        $rooms = M('jk_room_m')->where("BUILDNUMBER='$bcode' AND UNITNO=$unitno AND ABSOLUTELYFLOOR=$finfono")
                            ->field("ROOMNO,ROOM,ROOMNUMBER")
                            ->order('ROOMNO')
                            ->select();
                        if ($flag) {
                            file_put_contents('mdmpro.txt', M('jk_floor')->_sql() . json_encode($rooms) . $fid . "\r\n", FILE_APPEND);
                            $flag = 0;
                        }
                        foreach ($rooms as $room) {
                            $rinfo = array();
                            $rinfo['projectid'] = $pid;
                            $rinfo['create_time'] = time();
                            $rinfo['update_time'] = time();
                            $rinfo['up_date'] = date('Y-m-d H:i:s', time());
                            $rinfo['pid'] = $fid;
                            $rinfo['status'] = 1;
                            $rinfo['title'] = $room['ROOM'];
                            $rinfo['sort'] = $room['ROOMNO'];
                            $rinfo['masterCode'] = $room['ROOMNUMBER'];
                            $rnumber = $rinfo['masterCode'];
                            // $rid = M('jk_floor')->where("projectid=$pid AND pid=$fid AND masterCode='$rnumber'")->getField('id');
                            // if (! $rid)
                            $rid = M('jk_floor')->add($rinfo);
                        }
                    }
                }
                // file_put_contents('mdmpro.txt', (time()-$sta)."\r\n",FILE_APPEND);
            }
            $count = M('jk_build_tmp')->where("PROJECTNUMBER='$code' AND sys=0")
                ->field('distinct(BUILDNUMBER),BLDNAME,STAEGENUMBER')
                ->count();
            if($count){
                M('jk_project')->where("id=$pid")->save(array(
                    'done' => 3
                ));
                file_put_contents('mdmpro.txt', "3\n", FILE_APPEND);
            }
            else{
                M('jk_project')->where("id=$pid")->save(array(
                    'done' => 1
                ));
                file_put_contents('mdmpro.txt', "2\n", FILE_APPEND);
            }

        }
    }
    /**
     * 函数用途描述：接收组织架构
     * @date: 2017年10月27日 下午 14:03:05
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function mdmSetOrganize($json_data){
        //获取组织主数据
        //返回值json转数组

        set_time_limit(120);
        $result=json_decode($json_data);
        $result=json_decode(json_encode($result),true);

        $ret['status']=1;

        //遍历组织主数据，同步组织主数据信息
        foreach ($result as $v){

            //$v = $v['data'][0];

            $data = array();
            //查看对应的OrganizationCode的本地组织表是否存在，如果不存在则添加
            $map['OrganizationCode'] = $v['ORGANIZATIONCODE'];
            $is_find = M('mdm_organize')->where($map)->getField('OrganizationCode');
            if(!$is_find){
                //组装新增组织架构的数组
                $data['OrganizationCode'] = $v['ORGANIZATIONCODE'];
                $data['simpleName']       = $v['SIMPLENAME'];
                $data['FullName']         = $v['FULLNAME'];
                $data['PersonLiable']     = $v['PERSONLIABLE'];
                $data['PersonLiableTel']  = $v['PERSONLIABLETEL'];
                $data['fax']              = $v['FAX'];
                $data['adminAddress']     = $v['ADMINADDRESS'];
                $data['HigOrgzation']     = $v['HIGORGZATION'];
                $data['HigOrgzationCode'] = $v['HIGORGZATIONCODE'];
                $data['Levels']           = $v['LEVELS'];
                $data['CreateDataTime']   = $v['CREATEDATATIME'];
                $data['IsUsed']           = $v['ISUSED'];
                $data['IsUsedCode']       = $v['ISUSEDCODE'];
                $data['plate']            = $v['PLATE'];
                $data['plateCode']        = $v['PLATECODE'];
                $res = M('mdm_organize')->add($data);

                if(!$res){
                    file_put_contents('ceshi1.txt', "错误语句：".M()->getLastSql());
                    $ret['status']=0;
                }
            }else{
                $data['simpleName']       = $v['SIMPLENAME'];
                $data['FullName']         = $v['FULLNAME'];
                $data['PersonLiable']     = $v['PERSONLIABLE'];
                $data['PersonLiableTel']  = $v['PERSONLIABLETEL'];
                $data['fax']              = $v['FAX'];
                $data['adminAddress']     = $v['ADMINADDRESS'];
                $data['HigOrgzation']     = $v['HIGORGZATION'];
                $data['HigOrgzationCode'] = $v['HIGORGZATIONCODE'];
                $data['Levels']           = $v['LEVELS'];
                $data['CreateDataTime']   = $v['CREATEDATATIME'];
                $data['IsUsed']           = $v['ISUSED'];
                $data['IsUsedCode']       = $v['ISUSEDCODE'];
                $data['plate']            = $v['PLATE'];
                $data['plateCode']        = $v['PLATECODE'];


                $res = M('mdm_organize')->where("OrganizationCode='".$v['ORGANIZATIONCODE']."'")->save($data);
                if(!$res){
                    file_put_contents('ceshi1.txt', "错误语句：".M()->getLastSql());
                    $ret['status']=0;
                }
                $ret['sql'] = M()->getLastSql();
            }
            //增加或修改用户组信息
            // $data = array();
            // $id = M('auth_group')->where("OrganizationCode='".$is_find."'")->getField('id');
            // $pid = M('auth_group')->where("OrganizationCode='".$v['HIGORGZATIONCODE']."'")->getField('id');
            // //状态

            // if($v['ISUSEDCODE']==0){
            // $data['status'] = 1;
            // }else if($v['ISUSEDCODE']==1){
            // $data['status'] = 0;
            // }else if($v['ISUSEDCODE']==2){
            // $data['status'] = -1;
            // }
            // if($v['SIMPLENAME'] && $v['SIMPLENAME']!='null'){
            // $data['short_title']  = $v['SIMPLENAME'];
            // }else{
            // $data['short_title']  = null;
            // }
            // if($id){
            // //查找上级组织的ID
            // if($pid){
            // $data['pid']   = $pid;
            // }else{
            // $data['pid']   = 24;
            // }
            // $data['title'] = $v['FULLNAME'];

            // //$data['pid']   = $pid;
            // M('auth_group')->where("id='".$id."'")->save($data);

            // }else{
            // if($pid){
            // $data['pid']   = $pid;
            // }else{
            // $data['pid']   = 24;
            // }

            // $data['OrganizationCode'] = $is_find;
            // $data['title']            = $v['FULLNAME'];



            // M('auth_group')->add($data);


            // }
            // echo M()->getLastSql();
        }

        //echo json_encode($ret);
        return json_encode($ret);

    }
    /**
     * 函数用途描述：接收人员账号主数据
     * @date: 2017年10月30日 上午 9:38:05
     * @author: tanjiewen
     * @param: id：楼栋id
     * @return:
     */
    public function mdmSetAccount($json_data){
        //获取组织主数据
        //返回值json转数组
        set_time_limit(120);

        $result=json_decode($json_data);

        $result=json_decode(json_encode($result),true);
        $ret['status']=1;
        //遍历楼栋主数据，同步楼栋主数据信息

        foreach ($result as $v){

            //$v = $v['data'][0];

            $data = array();
            //查看对应的OrganizationCode的本地组织表是否存在，如果不存在则添加
            $map['PerAccountName'] = $v['PERACCOUNTNAME'];
            $is_find = M('mdm_account')->where($map)->getField('PERACCOUNTNAME');
            if(!$is_find){
                //组装新增组织架构的数组
                //$data['PersonnelCode']          = $v['PERSONNELCODE'];
                $data['PerAccountName']         = $v['PERACCOUNTNAME'];
                $data['PerAccountPass']         = $v['PERACCOUNTPASS'];
                $data['DistributionSystemCode'] = $v['DISTRIBUTIONSYSTEMCODE'];
                $data['DistributionSystem']     = $v['DISTRIBUTIONSYSTEM'];
                $data['PerAccountCode']         = $v['PERACCOUNTCODE'];
                $data['CreateDataTime']         = $v['CREATEDATATIME'];
                $data['IsUsed']                 = $v['ISUSED'];
                $data['IsUsedCode']             = $v['ISUSEDCODE'];

                $res = M('mdm_account')->add($data);

                if(!$res){
                    $ret['status']=0;
                }
            }else{
                //$data['PerAccountName']         = $v['PERACCOUNTNAME'];
                $data['PerAccountPass']         = $v['PERACCOUNTPASS'];
                $data['DistributionSystemCode'] = $v['DISTRIBUTIONSYSTEMCODE'];
                $data['DistributionSystem']     = $v['DISTRIBUTIONSYSTEM'];
                $data['PerAccountCode']         = $v['PERACCOUNTCODE'];
                $data['CreateDataTime']         = $v['CREATEDATATIME'];
                $data['IsUsed']                 = $v['ISUSED'];
                $data['IsUsedCode']             = $v['ISUSEDCODE'];

                $res = M('mdm_account')->where("PerAccountName='".$v['PERACCOUNTNAME']."'")->save($data);
                if(!$res){
                    $ret['status']=0;
                }

            }
        }
        echo json_encode($ret);
        return json_encode($ret);

    }
    /**
     * 函数用途描述：接收人员信息主数据
     * @date: 2017年10月30日 下午 15:00:05
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function mdmSetPerson($json_data){
        //获取人员主数据
        //返回值json转数组
        set_time_limit(120);

        $result=json_decode($json_data);
        $result=json_decode(json_encode($result),true);
        $ret['status']=1;
        //遍历人员主数据，同步人员主数据信息
        foreach ($result as $v){

            //$v = $v['data'][0];
            //dump($v);
            $data = array();
            //查看对应的OrganizationCode的本地组织表是否存在，如果不存在则添加
            $map['PersonnelID'] = $v['PERSONNELID'];
            $is_find = M('mdm_person')->where($map)->getField('PersonnelID');
            if(!$is_find){
                //组装新增组织架构的数组
                $data['PersonnelID']       = $v['PERSONNELID'];
                $data['IndexOf']           = $v['INDEXOF'];
                $data['PersonnelNum']      = $v['PERSONNELNUM'];
                $data['PersonnelName']     = $v['PERSONNELNAME'];
                $data['SimpleName']        = $v['SIMPLENAME'];
                $data['OldName']           = $v['OLDNAME'];
                $data['SimpleNamePingYin'] = $v['SIMPLENAMEPINGYIN'];
                $data['FullNamePingYin']   = $v['FULLNAMEPINGYIN'];
                $data['Gender']            = $v['GENDER'];
                $data['GenderCode']        = $v['GENDERCODE'];
                $data['Folk']              = $v['FOLK'];
                $data['Height']            = $v['HEIGHT'];
                $data['PoliticalFace']     = $v['POLITICALFACE'];
                $data['Wed']               = $v['WED'];
                $data['NativePlace']       = $v['NATIVEPLACE'];
                $data['NativePlaceCode']   = $v['NATIVEPLACECODE'];
                $data['Nationality']       = $v['NATIONALITY'];
                $data['NationalityCode']   = $v['NATIONALITYCODE'];
                $data['Birthday']          = $v['BIRTHDAY'];
                $data['IdCardNO']          = $v['IDCARDNO'];
                $data['IdCardAddress']     = $v['IDCARDADDRESS'];
                $data['hjAddress']         = $v['HJADDRESS'];
                $data['Regresidence']      = $v['REGRESIDENCE'];
                $data['PASSPORTNO']        = $v['PASSPORTNO'];
                $data['cell']              = $v['CELL'];
                $data['HomePhone']         = $v['HOMEPHONE'];
                $data['AddressTX']         = $v['ADDRESSTX'];
                $data['Email']             = $v['EMAIL'];
                $data['EmployTechPost']    = $v['EMPLOYTECHPOST'];
                $data['EmployeeType']      = $v['EMPLOYEETYPE'];
                $data['Department']        = $v['DEPARTMENT'];
                $data['DeletedStatus']     = $v['DELETEDSTATUS'];
                $data['CreateDataTime']    = $v['CREATEDATATIME'];
                $data['IsUsed']            = $v['ISUSED'];
                $data['IsUsedCode']        = $v['ISUSEDCODE'];
                $data['PersonTYPE']        = $v['PERSONTYPE'];
                $data['PerAccountName']    = $v['PERACCOUNTNAME'];
                $data['PerAccountPass']    = $v['PERACCOUNTPASS'];
                $data['DistributionSystemCode'] = $v['DISTRIBUTIONSYSTEMCODE'];
                $data['DistributionSystem'] = $v['DISTRIBUTIONSYSTEM'];
                $data['PerAccountCode']     = $v['PERACCOUNTCODE'];
                $data['PerCreateDataTime']  = $v['PERCREATEDATATIME'];

                $res = M('mdm_person')->add($data);

                if(!$res){
                    $ret['status']=0;
                    file_put_contents('error_sql.log', "出错语句为：".M()->getLastSql());
                }

            }else{
                $data['PersonnelID']       = $v['PERSONNELID'];
                $data['IndexOf']           = $v['INDEXOF'];
                $data['PersonnelNum']      = $v['PERSONNELNUM'];
                $data['PersonnelName']     = $v['PERSONNELNAME'];
                $data['SimpleName']        = $v['SIMPLENAME'];
                $data['OldName']           = $v['OLDNAME'];
                $data['SimpleNamePingYin'] = $v['SIMPLENAMEPINGYIN'];
                $data['FullNamePingYin']   = $v['FULLNAMEPINGYIN'];
                $data['Gender']            = $v['GENDER'];
                $data['GenderCode']        = $v['GENDERCODE'];
                $data['Folk']              = $v['FOLK'];
                $data['Height']            = $v['HEIGHT'];
                $data['PoliticalFace']     = $v['POLITICALFACE'];
                $data['Wed']               = $v['WED'];
                $data['NativePlace']       = $v['NATIVEPLACE'];
                $data['NativePlaceCode']   = $v['NATIVEPLACECODE'];
                $data['Nationality']       = $v['NATIONALITY'];
                $data['NationalityCode']   = $v['NATIONALITYCODE'];
                $data['Birthday']          = $v['BIRTHDAY'];
                $data['IdCardNO']          = $v['IDCARDNO'];
                $data['IdCardAddress']     = $v['IDCARDADDRESS'];
                $data['hjAddress']         = $v['HJADDRESS'];
                $data['Regresidence']      = $v['REGRESIDENCE'];
                $data['PASSPORTNO']        = $v['PASSPORTNO'];
                $data['cell']              = $v['CELL'];
                $data['HomePhone']         = $v['HOMEPHONE'];
                $data['AddressTX']         = $v['ADDRESSTX'];
                $data['Email']             = $v['EMAIL'];
                $data['EmployTechPost']    = $v['EMPLOYTECHPOST'];
                $data['EmployeeType']      = $v['EMPLOYEETYPE'];
                $data['Department']        = $v['DEPARTMENT'];
                $data['DeletedStatus']     = $v['DELETEDSTATUS'];
                $data['CreateDataTime']    = $v['CREATEDATATIME'];
                $data['IsUsed']            = $v['ISUSED'];
                $data['IsUsedCode']        = $v['ISUSEDCODE'];
                $data['PersonTYPE']        = $v['PERSONTYPE'];
                $data['PerAccountName']    = $v['PERACCOUNTNAME'];
                $data['PerAccountPass']    = $v['PERACCOUNTPASS'];
                $data['DistributionSystemCode'] = $v['DISTRIBUTIONSYSTEMCODE'];
                $data['DistributionSystem'] = $v['DISTRIBUTIONSYSTEM'];
                $data['PerAccountCode']     = $v['PERACCOUNTCODE'];
                $data['PerCreateDataTime']  = $v['PERCREATEDATATIME'];
                $res = M('mdm_person')->where("PersonnelID='".$v['PERSONNELID']."'")->save($data);
            }

            //存储用户信息
            $this->mdmSetMember($data);
        }
        echo json_encode($ret);
        return json_encode($ret);

    }
    /**
     * 函数用途描述：接收人员主数据并插入member表和uccentermember表
     * @date: 2017年11月24日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function mdmSetMember($data=''){

        $member = array();
        $ucmember = array();
        //先插入member表
        $member['PersonnelID'] = $data['PersonnelID'];
        //判断是否有相同PersonnelID的用户名
        $find = M('member')->where("PersonnelID='".$data['PersonnelID']."'")->field('uid,PersonnelID')->find();
        //所属组织机构id
        $auth_id = M('auth_group')->where("OrganizationCode='".$data['Department']."'")->getField('id');
        //解析账号的启用禁用状态

        $uc_status = substr($data['DistributionSystemCode'],13,1);
        $member['PersonnelID'] = $data['PersonnelID'];
        if($find){
            $member['nickname'] = $data['PerAccountName'];
            $member['username'] = $data['PersonnelName'];
            $member['mobile']   = $data['cell'];
            $member['position'] = $data['EmployTechPost'];
            $member['pid']      = 1;
            if($data['ISUSEDCODE']==0){
                $member['status'] = 1;
            }else if($data['ISUSEDCODE']==1){
                $member['status'] = 0;
            }else if($data['ISUSEDCODE']==2){
                $member['status'] = -1;
            }

            M('Member')->where("PersonnelID='".$data['PersonnelID']."'")->save($member);
            //保存ucentermember表中的数据
            $uid = $find['uid'];
            //uccenter保存的表
            $ucmember['password'] = $data['PerAccountPass'] ;
            $ucModel = UCenterMember();
            $uc_data = $ucModel->create($ucmember);
            $uc_data['username']    = $member['nickname'];
            $uc_data['update_time'] = time();
            $uc_data['PersonnelID'] = $data['PersonnelID'];
            $uc_data['status']      = $member['status'];
            $res = M('UcenterMember')->where("id=$uid")->save($uc_data);
            //是否重构权限
            if($auth_id){
                $gid = $auth_id;
                $AuthGroup = D('AuthGroup');
                if (is_numeric($uid)) {
                    if (is_administrator($uid)) {
                        // echo '超级管理员';
                        die;//超级管理员
                    }
                    if (!M('Member')->where(array('uid' => $uid))->find()) {
                        //echo '用户不存在';
                        die;//用户不存在
                    }
                }

                if ($gid && !$AuthGroup->checkGroupId($gid)) {
                    // echo '用户组有误';
                    die;
                }
                if ($AuthGroup->addToGroup($uid, $gid)) {
                    // echo '成功授权';
                }

            }
        }else{
            $member['nickname'] = $data['PerAccountName'];
            $member['username'] = $data['PersonnelName'];
            $member['mobile']   = $data['cell'];
            $member['position'] = $data['EmployTechPost'];
            $member['pid']      = 1;
            if($data['ISUSEDCODE']==0){
                $member['status'] = 1;
            }else if($data['ISUSEDCODE']==1){
                $member['status'] = 0;
            }else if($data['ISUSEDCODE']==2){
                $member['status'] = -1;
            }
            $uid =  M('Member')->add($member);
            //uccenter保存的表
            $info = M('Member')->field('nickname AS username,reg_ip,reg_time,status')->where("uid=$uid")->find();
            $ucModel = UCenterMember();
            $uc_data = $ucModel->create(array('password' => $data['PerAccountPass']));
            $info['username']    = $member['nickname'];
            $info['password']=$uc_data['password'];

            //$info['password']=$password;
            $info['status']  = $member['status'];

            $info['type'] = 1;
            $info['update_time'] = $info['reg_time'];
            $info['PersonnelID'] = $data['PersonnelID'];
            $res = M('UcenterMember')->add($info);
            //重构权限
            if($auth_id){
                $gid = $auth_id;//权限id

                $AuthGroup = D('AuthGroup');
                if (is_numeric($uid)) {
                    if (is_administrator($uid)) {
                        die;
                    }
                    if (!M('Member')->where(array('uid' => $uid))->find()) {
                        die;
                    }
                }

                if ($gid && !$AuthGroup->checkGroupId($gid)) {
                    die;
                }
                if ($AuthGroup->addToGroup($uid, $gid)) {
                    //$this->success(L('_SUCCESS_OPERATE_'),U('user/index'));
                } else {
                    //$this->error($AuthGroup->getError());
                }

            }


        }
    }
    /**
     * 函数用途描述：接收供应商主数据
     * @date: 2017年11月13日 下午 15:56:05
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function mdmSetProvider($json_data){
        //获取组织主数据
        //返回值json转数组
        //file_put_contents('ceshi.txt', time().":".$json_data);

        set_time_limit(120);

        $result=json_decode($json_data);
        $result=json_decode(json_encode($result),true);
        $ret['status']=1;
        //遍历楼栋主数据，同步楼栋主数据信息

        foreach ($result as $v){
            $data = array();
            //查看对应的OrganizationCode的本地组织表是否存在，如果不存在则添加
            $map['Providernumber'] = $v['PROVIDERNUMBER'];
            $is_find = M('jk_provider_mdm')->where($map)->getField('Providernumber');
            if(!$is_find){
                //组装新增组织架构的数组
                $data['Providernumber']          = $v['PROVIDERNUMBER'];
                $data['ProviderName']            = $v['PROVIDERNAME'];
                $data['ProviderType']            = $v['PROVIDERTYPE'];
                $data['UniformSocialCreditCode'] = $v['UNIFORMSOCIALCREDITCODE'];
                $data['Corporation']             = $v['CORPORATION'];
                $data['RegistrationAuthority']   = $v['REGISTRATIONAUTHORITY'];
                $data['RegistrationStatus']      = $v['REGISTRATIONSTATUS'];
                $data['RegisterFund']            = $v['REGISTERFUND'];
                $data['WorkAddress']             = $v['WORKADDRESS'];
                $data['BusinessScope']           = $v['BUSINESSSCOPE'];
                $data['EstablishDate']           = $v['ESTABLISHDATE'];
                $data['BusinessDateFrom']        = $v['BUSINESSDATEFROM'];
                $data['BusinessDateTo']          = $v['BUSINESSDATETO'];
                $data['RegistrationDate']        = $v['REGISTRATIONDATE'];
                $data['LicenceCode']             = $v['LICENCECODE'];
                $data['IsUsed']                  = $v['ISUSED'];
                $data['IsUsedCode']              = $v['ISUSEDCODE'];
                $data['CreateDataTime']          = $v['CREATEDATATIME'];

                $res = M('jk_provider_mdm')->add($data);

                if(!$res){
                    $ret['status']=0;
                    echo "出错语句为：".M()->getLastSql();die;
                }
            }else{
                $data['Providernumber']          = $v['PROVIDERNUMBER'];
                $data['ProviderName']            = $v['PROVIDERNAME'];
                $data['ProviderType']            = $v['PROVIDERTYPE'];
                $data['UniformSocialCreditCode'] = $v['UNIFORMSOCIALCREDITCODE'];
                $data['Corporation']             = $v['CORPORATION'];
                $data['RegistrationAuthority']   = $v['REGISTRATIONAUTHORITY'];
                $data['RegistrationStatus']      = $v['REGISTRATIONSTATUS'];
                $data['RegisterFund']            = $v['REGISTERFUND'];
                $data['WorkAddress']             = $v['WORKADDRESS'];
                $data['BusinessScope']           = $v['BUSINESSSCOPE'];
                $data['EstablishDate']           = $v['ESTABLISHDATE'];
                $data['BusinessDateFrom']        = $v['BUSINESSDATEFROM'];
                $data['BusinessDateTo']          = $v['BUSINESSDATETO'];
                $data['RegistrationDate']        = $v['REGISTRATIONDATE'];
                $data['LicenceCode']             = $v['LICENCECODE'];
                $data['IsUsed']                  = $v['ISUSED'];
                $data['IsUsedCode']              = $v['ISUSEDCODE'];
                $data['CreateDataTime']          = $v['CREATEDATATIME'];

                $res = M('jk_provider_mdm')->where("Providernumber='".$v['PROVIDERNUMBER']."'")->save($data);
                /* if(!$res){
                 $ret['status']=0;
                 echo M()->getLastSql();die;
                } */

            }
        }
        echo json_encode($ret);
        return json_encode($ret);

    }

    /**
     * 函数用途描述：保存供应商
     * @date: 2017年11月1日 下午5:06:23
     * @author: luojun
     * @param: $name：供应商关键字
     * @return:
     */
    public function selectpro($name='') {
        if(IS_POST){
            $data=$_POST['data'];
            $data=json_decode($data,true);

            $info=array();
            $info['Providernumber']=$data['mdmCode'];//MDM生成的唯一编码
            $info['ProviderName']=$data['vendorName'];//供应商名称
            $info['ProviderType']=$data['type'];//供应商企业类型（有限责任公司、个体工商户、有限合伙企业）
            $info['UniformSocialCreditCode']=$data['uscCode'];//统一社会信用代码
            $info['Corporation']=$data['legalPerson'];//法人代表/经营者/执行事务合伙人
            $info['RegistrationAuthority']=$data['registrationOrg'];//登记机关
            $info['RegistrationStatus']=$data['registrationStatus'];//登记状态（存续、在营、开业、在册、注销）
            $info['RegisterFund']=$data['registerFundation'];//注册资本
            $info['WorkAddress']=$data['address'];//经营场所（地址）
            $info['BusinessScope']=$data['manageScope'];//经营范围
            $info['EstablishDate']=$data['establishDate'];//成立日期
            $info['BusinessDateFrom']=$data['businessBeginDate'];//营业期限自（合伙期限自）
            $info['BusinessDateTo']=$data['businessEndDate'];//营业期限至（合伙期限至）
            $info['RegistrationDate']=$data['establishDate'];//注册日期
            $info['LicenceCode']=$data['approvalDate'];//核准日期
            $info['IsUsed']=0;//
            $info['IsUsedCode']='启用';//
            $info['CreateDataTime']=date("Y-m-d",time());//供应商数据创建时间
            $find=M('jk_provider_mdm')->where("Providernumber=".$data['mdmCode'])->find();
            if(!$find){
                M('jk_provider_mdm')->add($info);

            }
            $this->success('新增成功');
            return;
        }
        // else{
        // $url="http://192.168.9.84:8080/vendorReaper/api/vender/vendorName?";
        // $url.="vendorName=".urlencode($name);
        // $url.="&page=1&pageSize=30";

        // $respone=http_get_data($url);


        // $respone=json_decode($respone,true);

        // if($respone['totalElements']>0){
        // $data=$respone['content'];
        // $this->assign("data",$data);
        // foreach($data as $v){

        // }
        // //dump($data);
        // }

        // $this->display('selectpro');
        // }
    }
}
?>
