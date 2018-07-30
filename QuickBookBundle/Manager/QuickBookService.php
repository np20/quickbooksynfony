<?php
namespace vTechSolution\Bundle\QuickBookBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use vTechSolution\Bundle\QuickBookBundle\Entity\Quickbook_Token;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\TimeActivity;

class QuickBookService
{
  private $container;
  private $doctrine;
  private $request;
  private $responseArray;
  private $icehrmdbDatabase;
  private $quickBookEmployeeDetail;
  private $quickbookCustomerDetail;
  private $quickbookServiceItemDetail;

  const HTTP_METHOD_GET    = 'GET';
  const HTTP_METHOD_POST   = 'POST';

  public function __construct(Container $container) {
        $this->container = $container;
        $this->request = $this->container->get('request');
        $this->doctrine = $this->container->get('doctrine');
        $this->responseArray = $this->quickBookEmployeeDetail = $this->quickbookCustomerDetail = $this->quickbookServiceItemDetail = array();
        $this->icehrmdbDatabase = null;
    }

  public function __destructor() {
      unset($this->container);
      unset($this->request);
      unset($this->doctrine);
      unset($this->responseArray);
      unset($this->icehrmdbDatabase);
    }


public function getAccessTokencode()
{
    $tokenEndPointUrl = $this->container->getParameter('oauthEndUrl');
    $grant_type = $this->container->getParameter('grantType_code');
    $grantType = $this->container->getParameter('grantType');
    $state = $this->container->getParameter('state');
    $authorizationRequestUrl = $this->container->getParameter('oauthUrl');
    $scope = $this->container->getParameter('scope');
    $redirect_uri = $this->container->getParameter('redirectUri');
    $response_type = $this->container->getParameter('responseType');

    $session_id = session_id();
    if (empty($session_id))
    {
        session_start();
    }

    if(isset($_SESSION['access_token']) && !empty($_SESSION['access_token'])){

      $refresh_token = $_SESSION['refresh_token'];

      $result = $this->refreshAccessToken($tokenEndPointUrl, $grantType, $refresh_token);

      $_SESSION['access_token'] = $result['access_token'];
      $_SESSION['refresh_token'] = $result['refresh_token'];

      $tokens = array('access_token' => $_SESSION['access_token'], 'refresh_token' => $_SESSION['refresh_token']);

      unset($grantType);
      unset($refresh_token);

      $this->responseArray = $tokens;
    
      return $this->responseArray;
  }else{
  
  $code = $this->request->get('code');

  if (!isset($code))
    {
      $authUrl = $this->getAuthorizationURL($authorizationRequestUrl, $scope, $redirect_uri, $response_type, $state);

      header("Access-Control-Allow-Origin: *");
      header('Access-Control-Allow-Credentials: true');
      header("Location: ".$authUrl);
      exit();
    }
  else{
     
      $responseState = $this->request->get('state');

      if(strcmp($state, $responseState) != 0){
        throw new Exception("The state is not correct from Intuit Server. Consider your app is hacked.");
      }

      $resulttoken = $this->getAccessToken($tokenEndPointUrl,  $code, $redirect_uri, $grant_type);
      $_SESSION['access_token'] = $resulttoken['access_token'];
      $_SESSION['refresh_token'] = $resulttoken['refresh_token'];

      unset($authUrl);
      unset($tokenEndPointUrl);
      unset($grant_type);
      unset($state);
      unset($authorizationRequestUrl);
      unset($scope);
      unset($redirect_uri);
      unset($response_type);
      unset($code);
      unset($responseState);
      $result = json_encode($resulttoken, true);
           
      $this->responseArray = $result;
      return $this->responseArray; 
    }
  }
}

public function getAuthorizationURL($authorizationRequestUrl, $scope, $redirect_uri, $response_type, $state)
{

  $client_id = $this->container->getParameter('clientId');
  $scope = $this->container->getParameter('scope');
  $redirect_uri = $this->container->getParameter('redirectUri');
  $response_type = $this->container->getParameter('responseType');
  $state = $this->container->getParameter('state');

  $parameters = array(
          'client_id' => $client_id,
          'scope' => $scope,
          'redirect_uri' => $redirect_uri,
          'response_type' => $response_type,
          'state' => $state
        );
  $authorizationRequestUrl .= '?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC1738);

  unset($client_id);
  unset($scope);
  unset($redirect_uri);
  unset($response_type);
  unset($state);
  unset($parameters);

  return $authorizationRequestUrl;
}

private function generateAuthorizationHeader(){
        $encodedClientIDClientSecrets = base64_encode($this->container->getParameter('clientId') . ':' . $client_secret = $this->container->getParameter('clientSecret'));
        $authorizationheader = 'Basic ' . $encodedClientIDClientSecrets;

        unset($encodedClientIDClientSecrets);

        return $authorizationheader;
    }

public function getAccessToken($tokenEndPointUrl, $code, $redirect_uri, $grantType){

       if(!isset($grantType)){
          throw new InvalidArgumentException('The grantType is mandatory.', InvalidArgumentException::INVALID_GRANT_TYPE);
       }

       $parameters = array(
         'grant_type' => $grantType,
         'code' => $code,
         'redirect_uri' => $redirect_uri
       );
       $authorizationHeaderInfo = $this->generateAuthorizationHeader();
       $http_header = array(
         'Accept' => 'application/json',
         'Authorization' => $authorizationHeaderInfo,
         'Content-Type' => 'application/x-www-form-urlencoded'
       );
       //Try catch???
       $result = $this->executeRequest($tokenEndPointUrl , $parameters, $http_header, self::HTTP_METHOD_POST);

        unset($parameters);
        unset($authorizationHeaderInfo);
        unset($http_header);

      return $result;
    }

public function refreshAccessToken($tokenEndPointUrl, $grant_type, $refresh_token){
      $parameters = array(
        'grant_type' => $grant_type,
        'refresh_token' => $refresh_token
      );

      $authorizationHeaderInfo = $this->generateAuthorizationHeader();
      $http_header = array(
        'Accept' => 'application/json',
        'Authorization' => $authorizationHeaderInfo,
        'Content-Type' => 'application/x-www-form-urlencoded'
      );
      $result = $this->executeRequest($tokenEndPointUrl , $parameters, $http_header, self::HTTP_METHOD_POST);

        unset($parameters);
        unset($authorizationHeaderInfo);
        unset($http_header);

      return $result;
    }

   public function executeRequest($url, $parameters = array(), $http_header, $http_method)
    {
      $curl_options = array();

      switch($http_method){
            case self::HTTP_METHOD_GET:
              $curl_options[CURLOPT_HTTPGET] = 'true';
              if (is_array($parameters) && count($parameters) > 0) {
                $url .= '?' . http_build_query($parameters);
              } elseif ($parameters) {
                $url .= '?' . $parameters;
              }
              break;
            case self:: HTTP_METHOD_POST:
              $curl_options[CURLOPT_POST] = '1';
              if(is_array($parameters) && count($parameters) > 0){
                $body = http_build_query($parameters);
                $curl_options[CURLOPT_POSTFIELDS] = $body;
              }
              break;
            default:
              break;
      }

      if(is_array($http_header)){
            $header = array();
            foreach($http_header as $key => $value) {
                $header[] = "$key: $value";
            }
            $curl_options[CURLOPT_HTTPHEADER] = $header;
      }

      $curl_options[CURLOPT_URL] = $url;
      $ch = curl_init();

      curl_setopt_array($ch, $curl_options);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      
      $json_decode = json_decode($result, true);
       
       curl_close($ch);
       //var_dump($json_decode);

        unset($curl_options);
        unset($http_method);
        unset($parameters);
        unset($ch);

       return $json_decode;
    }


  public function startQuickBookProcess() {

      $grantType = $this->container->getParameter('grantType');
      $refreshToken = $this->container->getParameter('refreshToken');
      $clientId = $this->container->getParameter('clientId');
      $clientSecret = $this->container->getParameter('clientSecret');
      $oauthEndUrl = $this->container->getParameter('oauthEndUrl');
      $encodedcId = base64_encode($clientId . ':' .$clientSecret);
      $authorization = 'Basic ' . $encodedcId;

       $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $oauthEndUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=".$grantType."&refresh_token=".$refreshToken,
        CURLOPT_HTTPHEADER => array(
        "accept: application/json",
        "authorization:".$authorization,
        "cache-control: no-cache",
        "content-type: application/x-www-form-urlencoded"
          ),
        ));

      $response = curl_exec($curl);
      $this->responseArray = json_decode($response, true);

      curl_close($curl);

      unset($grantType);
      unset($refreshToken);
      unset($clientId);
      unset($clientSecret);
      unset($oauthEndUrl);
      unset($encodedcId);
      unset($authorization);
      unset($curl);

      return $this->responseArray;
  }

  public function timesheetProcess(){
    $accessToken = $this->request->get('access_token');
    $refreshToken = $this->request->get('refresh_token');
    $realmId = $this->container->getParameter('realmId');
    $clientId = $this->container->getParameter('clientId');
    $clientSecret = $this->container->getParameter('clientSecret');

    $this->icehrmdbDatabase = $this->container->get('v_tech_solution_quick_book.icehrmdb')->getPDO();
    $this->mappingdbDatabase = $this->container->get('v_tech_solution_quick_book.mappingdb')->getPDO();

    $timesheetId = $this->request->get('timesheet_id');

    $query = 'SELECT employee FROM `employeetimesheets` WHERE id='.$timesheetId;
    $employeeId = $this->icehrmdbDatabase->query($query)->fetchColumn(0);

    if ($employeeId != '') {

        $quickbookEmployeeId = $this->getQuickbookIdbyHrm($employeeId);
        $timesheets = $this->getEmployeeTimesheetbyHrm($employeeId, $timesheetId);

        if (count($this->quickBookEmployeeDetail) == 0) {
          $this->getEmployeeIdbyQuickbook($employeeId);
        }
        $quickbookEmployeeBillRate = $this->quickBookEmployeeDetail['BillRate'];
        
        if (count($this->quickbookCustomerDetail) == 0) {
          $this->getEmployeeMSPNamebyQuickbook($employeeId);
        }
        $quickbookCustomerId =  $this->quickbookCustomerDetail['Id'];
        $quickbookCustomerTaxtable =  $this->quickbookCustomerDetail['Taxable'];


        if (count($this->quickbookServiceItemDetail) == 0) {
          $this->getEmployeeServiceItembyQuickbook($employeeId);
        }
        $quickbookServiceItemId =  $this->quickbookServiceItemDetail['Id'];
        $quickbookServiceItemDescription =  $this->quickbookServiceItemDetail['Description'];

        $timesheetId = $this->request->get('timesheet_id');

        $query = 'SELECT hrm_timesheet_id FROM `quickbook_employee_timesheet` WHERE hrm_timesheet_id='.$timesheetId;

        $hrmtimesheetid = $this->mappingdbDatabase->query($query)->fetchColumn(0);

        if ($hrmtimesheetid <= 1){

         $dataService = DataService::Configure(array(
          'auth_mode' => 'oauth2',
          'ClientID' => $clientId,
          'ClientSecret' =>  $clientSecret ,
          'accessTokenKey' => $accessToken,
          'refreshTokenKey' => $refreshToken,
          'QBORealmID' => $realmId,
          'baseUrl' => "Development"
        ));

        foreach ($timesheets as $timeSheet)
        {

           $splitstarTime = explode(" ",$timeSheet[0]);
           $date= $splitstarTime[0];
           $startTime = $splitstarTime[1];
           $splitendTime = explode(" ",$timeSheet[1]);
           $endTime = $splitendTime[1];
           $theResourceObj = TimeActivity::create([
          "TxnDate" =>$date,
          "NameOf" => "Employee",

          "EmployeeRef" => [  "value" => $quickbookEmployeeId ],

          "CustomerRef" => [  "value"=> $quickbookCustomerId ],

          "ItemRef" => [  "value"=> $quickbookServiceItemId ],

          "StartTime"=> $startTime,
          "EndTime"=> $endTime,
          "BillableStatus" => "Billable",
          "Taxable" => $quickbookCustomerTaxtable,
          "HourlyRate" => $quickbookEmployeeBillRate,
          "Description"=> $quickbookServiceItemDescription

        ]);

      $resultingObj = $dataService->Add($theResourceObj);


        $error = $dataService->getLastError();
        if ($error) {
          echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
          echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
          echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {

          $quickbookTimesheetId = "{$resultingObj->Id}";



          if ($quickbookTimesheetId != ' ') {

          $status = "Timesheet Imported";
          $currentDateTime = date('Y-m-d h:i:s a', time());

          $query="INSERT INTO `quickbook_employee_timesheet`(`hrm_emp_id`, `quick_emp_id`, `hrm_timesheet_id`, `quick_timesheet_id`, `status`,`date_hrm`, `created_at`, `updated_at`) VALUES ('$employeeId','$quickbookEmployeeId','$timesheetId','$quickbookTimesheetId','$status','$date', '$currentDateTime','$currentDateTime')";

          $quickbookEmployeeTimesheet = $this->mappingdbDatabase->exec($query);

          $response = 1; // Timesheet has been inserted In Quickbook
          $this->responseArray = $response;
        }else{
          $response = 2; //Something wrong in Quickbook
          $this->responseArray = $response;
            }

        }
      }
    }else {

      $response = 3; //Timesheet has been inserted Already In Quickbook.
          $this->responseArray = $response;
    }
  }

      unset($tokens);
      unset($employeeId);
      unset($timesheetId);
      unset($quickbookEmployeeId);
      unset($quickbookTimesheetId);
      unset($status);
      unset($currentDateTime);
      unset($query);
      unset($quickbookEmployeeTimesheet);
      unset($quickbookBillRate);
      unset($quickbookEmployeeBillRate);
      unset($quickbookCustomerTaxtable);
      unset($quickbookServiceItemDescription);
      unset($response);

      return $this->responseArray;

  }

  private function getQuickbookIdbyHrm($employeeId){

    $query = 'SELECT quick_emp_id FROM `quickbook_user_mapping` WHERE hrm_emp_id='.$employeeId;

    $quickbookEmployeeId = $this->mappingdbDatabase->query($query)->fetchColumn(0);

    if ($quickbookEmployeeId == '' ) {
        $quickbookEmployeeId = $this->getQuickbookIdbyMappingtable($employeeId);
    }

    unset($query);

    return $quickbookEmployeeId;
  }

  private function getHrmEmployeedetail($employeeId){

      $query = 'SELECT private_email FROM `employees` WHERE id='.$employeeId;
      $isEmployeeEmail = $this->icehrmdbDatabase->query($query)->fetchColumn(0);
      unset($query);
      print_r($isEmployeeEmail);die();
      return $isEmployeeEmail;
  }

  private function getEmployeeIdbyQuickbook($employeeId){
      

      $accessToken = $this->request->get('access_token');
      $employeeEmail = $this->getHrmEmployeedetail($employeeId);
      $quickbookUrl = $this->container->getParameter('quickbookUrl');
      $realmId = $this->container->getParameter('realmId');

      $query ="select%20*%20from%20Employee%20where%20PrimaryEmailAddr%3D'".$employeeEmail."'";
      $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $quickbookUrl."/v3/company/".$realmId."/query?query=".$query."&minorversion=4",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer  ".$accessToken,
            "content-type: application/json"
          ),
        ));

      $qbeResponse = curl_exec($curl);
      curl_close($curl);

      $xmlqbeResponse = simplexml_load_string($qbeResponse, "SimpleXMLElement", LIBXML_NOCDATA);
      $jsonqbeResponse = json_encode($xmlqbeResponse);
      $quickbookEmployeeResponse = json_decode($jsonqbeResponse,TRUE);

      unset($accessToken);
      unset($quickbookUrl);
      unset($realmId);
      unset($query);
      unset($curl);
      unset($qbeResponse);
      unset($xml);
      unset($json);

      $this->quickBookEmployeeDetail = $quickbookEmployeeResponse['QueryResponse']['Employee'];
      return $this->quickBookEmployeeDetail;
  }

  private function getQuickbookIdbyMappingtable($employeeId){

      $quickbookId = $this->getEmployeeIdbyQuickbook($employeeId);
      $qbId = $quickbookId['Id'];
      $currentDate = date('Y-m-d h:i:s a', time());
      $this->mappingdbDatabase->exec(
              "INSERT INTO `quickbook_user_mapping` (`hrm_emp_id`, `quick_emp_id`, `sync_date`, `created_date`, `updated_date`) VALUES ('" . $employeeId . "','" . $qbId . "','".$currentDate."','".$currentDate."','".$currentDate."')");

      $query = 'SELECT quick_emp_id FROM `quickbook_user_mapping` WHERE hrm_emp_id='.$employeeId;
      $mappingData = $this->mappingdbDatabase->query($query)->fetchColumn(0);

      unset($qbid);
      unset($currentDate);
      return $mappingData;
  }

  private function getEmployeeMSPdetailbyHrm($employeeId){

      echo $query = "SELECT mspd.msp_email FROM `system_integration` as mpd left join `msp_details` `mspd` on `mpd`.`id` = `mspd`.`map_data_id` WHERE mpd.h_employee_id=".$employeeId;
      $isMspEmail = $this->mappingdbDatabase->query($query)->fetchAll();

      unset($query);
      return $isMspEmail;
  }

  private function getEmployeeMSPNamebyQuickbook($employeeId){

      $accessToken = $this->request->get('access_token');
      $mspDetails = $this->getEmployeeMSPdetailbyHrm($employeeId);
      $mspEmail = $mspDetails[0]['msp_email'];
      $quickbookUrl = $this->container->getParameter('quickbookUrl');
      $realmId = $this->container->getParameter('realmId');

      $query ="select%20id,Taxable%20from%20customer%20where%20PrimaryEmailAddr%3d'".$mspEmail."'";
      $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $quickbookUrl."/v3/company/".$realmId."/query?query=".$query."&minorversion=4",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer  ".$accessToken,
            "content-type: application/json"
          ),
        ));

      $qbeResponse = curl_exec($curl);
      curl_close($curl);

      $xmlqbeResponse = simplexml_load_string($qbeResponse, "SimpleXMLElement", LIBXML_NOCDATA);
      $jsonqbeResponse = json_encode($xmlqbeResponse);
      $quickbookCustomerResponse = json_decode($jsonqbeResponse,TRUE);

      unset($mspDetails);
      unset($mspEmail);
      unset($tokens);
      unset($accessToken);
      unset($quickbookUrl);
      unset($realmId);
      unset($query);
      unset($curl);
      unset($qbeResponse);
      unset($xml);
      unset($json);

      $this->quickbookCustomerDetail = $quickbookCustomerResponse['QueryResponse']['Customer'];
      return $this->quickbookCustomerDetail;
  }

  private function getEmployeeServiceItemdetailbyHrm($employeeId){

      $query = "SELECT empj.name FROM `employees` as emp left join `jobtitles` `empj` on `emp`.`job_title` = `empj`.`id` WHERE emp.id=".$employeeId;
      $isJobTitle = $this->icehrmdbDatabase->query($query)->fetchAll();

      unset($query);

      return $isJobTitle;
  }

  private function getEmployeeServiceItembyQuickbook($employeeId){

      $accessToken = $this->request->get('access_token');
      $serviceItemDetails = $this->getEmployeeServiceItemdetailbyHrm($employeeId);
      $serviceItemName =  str_replace(' ','%20',$serviceItemDetails[0]['name']);
      $quickbookUrl = $this->container->getParameter('quickbookUrl');
      $realmId = $this->container->getParameter('realmId');

      $query ="select%20id%2cDescription%20from%20Item%20where%20FullyQualifiedName%3d%27".$serviceItemName."%27&minorversion=4";
      $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $quickbookUrl."/v3/company/".$realmId."/query?query=".$query."&minorversion=4",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer  ".$accessToken,
            "content-type: application/json"
          ),
        ));

      $qbeResponse = curl_exec($curl);
      curl_close($curl);

      $xmlqbeResponse = simplexml_load_string($qbeResponse, "SimpleXMLElement", LIBXML_NOCDATA);
      $jsonqbeResponse = json_encode($xmlqbeResponse);
      $quickbookJobResponse = json_decode($jsonqbeResponse,TRUE);

      unset($serviceItemDetails);
      unset($serviceItemName);
      unset($accessToken);
      unset($tokens);
      unset($quickbookUrl);
      unset($realmId);
      unset($query);
      unset($curl);
      unset($qbeResponse);
      unset($xml);
      unset($json);

      $this->quickbookServiceItemDetail = $quickbookJobResponse['QueryResponse']['Item'];
      return $this->quickbookServiceItemDetail;
  }

 private function getEmployeeTimesheetbyHrm($employeeId, $timesheetId){

      if ($this->icehrmdbDatabase == null) {
        $this->icehrmdbDatabase = $this->container->get('v_tech_solution_quick_book.icehrmdb')->getPDO();
      }

      $query = 'SELECT * FROM `employeetimesheets` WHERE id='.$timesheetId;
      $employeetimesheetDates = $this->icehrmdbDatabase->query($query)->fetchAll();

      $startDate = $employeetimesheetDates[0]['date_start'];
      $endDate = $employeetimesheetDates[0]['date_end'];

      $query = "SELECT date_start,date_end FROM `employeetimeentry` WHERE employee='".$employeeId."' AND date_format(date_start, '%Y-%m-%d') BETWEEN '".$startDate."' AND '".$endDate."'";
      $employeeTimeentry = $this->icehrmdbDatabase->query($query)->fetchAll();

      unset($employeetimesheetDates);

      return $employeeTimeentry;
  }

}
