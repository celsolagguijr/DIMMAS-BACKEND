<?php

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/includes/Database.php');
require_once($root_dir.'/Controllers/NotificationController.php');

class DengueCaseController extends Database{
    
    private $data = null;

    public function __construct($data = ''){
        $this->data = $data;
    }

    public function getYears(){
        $query = "SELECT DISTINCT(DATE_FORMAT(case_date, '%Y')) AS 'YEARS' FROM dengue_cases ORDER BY case_date DESC";
        return $this->setquery($query)->getArray();
    }

    public function getBrgys(){

        $query = "SELECT * FROM barangays;";
        return $this->setquery($query )->get();
    }

    public function getBrgyCases($year){

        $brgys = $this->getBrgys();

        $brgys_cases = [];

        foreach($brgys as $brgy){
            
            $brgy_case = $this->getTotalCases("PER_BRGY",["brgy_id" => $brgy->id , "year" => $year])[0];
            
            $brgys_cases [] = [
                "brgy_id" => $brgy->id,
                "brgy"    => $brgy->name,
                "lat"     => $brgy->latitude, 
                "lng"     => $brgy->longtitude,
                "total_cases" => $brgy_case->TOTAL_CASES,
                "total_recoveries" => $brgy_case->TOTAL_RECOVERIES,
                "total_deaths" => $brgy_case->TOTAL_DEATHS
            ];
            
        }

        return $brgys_cases;

    }

    public function casesPerMonth($year){
        
        $labels = [];
        $t_cases = [];
        $t_recoveries = [];
        $t_deaths = [];

        $months = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        for ($i = 0; $i < count($months); $i++) { 
            
            $month = $months[$i];
            $label = $year."-".$month;
            $fetch_totals = $this->getTotalCases("PER_MONTH",["year_month"=>$label])[0];

            $labels[] = $label;
            $t_cases[] = $fetch_totals->TOTAL_CASES ? $fetch_totals->TOTAL_CASES : 0;
            $t_recoveries[] = $fetch_totals->TOTAL_RECOVERIES ? $fetch_totals->TOTAL_RECOVERIES : 0;
            $t_deaths[] = $fetch_totals->TOTAL_DEATHS ? $fetch_totals->TOTAL_DEATHS : 0;
        }

        return [
            "labels" => $labels,
            "total_cases" => $t_cases,
            "total_recoveries" => $t_recoveries,
            "total_deaths" => $t_deaths
        ];
       
    }

    private function getTotalCases($request,$data){

        $filter = "";

        switch ($request) {

            case 'PER_BRGY':
                
                $brgy_id = $data["brgy_id"];
                $year = $data["year"];
                $filter = "WHERE barangay_id = ".$brgy_id." "."AND DATE_FORMAT(case_date, '%Y') = ".$year;

            break;

            case 'PER_YEAR':
                
                $year = $data["year"];
                $filter = "WHERE DATE_FORMAT(case_date, '%Y') = ".$year;

            break;
            
            case 'PER_MONTH':
                
                $year_month = $data["year_month"];
                $filter = "WHERE DATE_FORMAT(case_date, '%Y-%M') = '".$year_month."'";

            break;
            
            // default:
            //     # code...
            //     break;
        }
        

        $query = "SELECT 
                        SUM(total_cases) AS 'TOTAL_CASES',
                        SUM(total_recoveries) AS 'TOTAL_RECOVERIES',
                        SUM(total_deaths) AS 'TOTAL_DEATHS'
                    FROM dengue_cases ".$filter;



        return $this->setquery($query )->get();
        
    }


    public function dashboardDatas(){

        $cardData      = $this->getTotalCases("PER_YEAR", ["year" => $this->data['year']] );
        $chartPerMonth = $this->casesPerMonth($this->data['year']);
        $chartPerBrgy  = $this->getBrgyCases($this->data['year']);

        return [
            "cardData"      => $cardData[0],
            "chartPerMonth" => $chartPerMonth,
            "chartPerBrgy"  => $chartPerBrgy
        ];
            


    }

    public function addCase(){
        
        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        $query = $this->insert([
            'total_cases'       => $this->data['total_cases'],
            'total_deaths'      => $this->data['total_deaths'],
            'total_recoveries'  => $this->data['total_recoveries'],
            'barangay_id'       => $this->data['brgy_id'],
            'case_date'         => $this->data['case_date'],
            'created_at' => $dateTime,
            'updated_at' => $dateTime
        ],"dengue_cases");


        try {

            $response = $this->setquery($query)->save();

            $barangay_name = $this->setquery("SELECT `name` FROM barangays WHERE id=".$this->data['brgy_id']." LIMIT 1")->getField('name');
            $admin_ids = $this->setquery("SELECT id FROM users WHERE user_type_id = 1")->getArray();
            $brgys_ids = $this->setquery("SELECT id FROM users WHERE barangay_id =".$this->data['brgy_id'])->getArray();

            Notification::create([
                "title"   => "New dengue case",
                "content" => "New case/s added to BRGY.".$barangay_name,
                "created_by" => $this->data["created_by"],
                "notify_to"  => $admin_ids 
            ]);

            Notification::create([
                "title"   => "New dengue case",
                "content" => "New case/s added to your Barangay",
                "created_by" => $this->data["created_by"],
                "notify_to"  => $brgys_ids 
            ]);

             return [
                 "status" => 200,
                 "message" => "Successfully Saved!",
                 "savedData"    => [
                     'id'                => $response["id"],
                     'total_cases'       => $this->data['total_cases'],
                     'total_deaths'      => $this->data['total_deaths'],
                     'total_recoveries'  => $this->data['total_recoveries'],
                     'barangay_id'       => $this->data['brgy_id'],
                     'case_date'         => $this->data['case_date'],
                 ]
             ];
 
         } catch (Exception $e) {
             
             return [
                 "status" => 401,
                 "message" => "Something went wrong. Please contact your support"
             ];
         }
    }

    public function getRecords(){

        $from = $this->data['from'];
        $to = $this->data['to'];

        $result = [];
        
        $query = "SELECT 
                        DISTINCT(DATE_FORMAT(case_date,'%Y-%m')) AS 'MONTH' 
                    FROM dengue_cases 
                        WHERE case_date BETWEEN '".$from."' AND '".$to."' ORDER BY case_date ASC";

        foreach($this->setquery($query)->get() as $monthThatHasCase){

            $dateFormat = explode('-',$monthThatHasCase->MONTH);

            $months = [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            ];

            
            $dailyCases = $this->setquery("SELECT 
                    total_cases AS 'TOTAL_CASES',
                    total_deaths AS 'TOTAL_DEATHS',
                    total_recoveries AS 'TOTAL_RECOVERIES',
                    b.`name` AS 'BARANGAY',
                    DATE_FORMAT(ds.`case_date`,'%M %d, %Y') AS 'CASE_DATE'
                FROM 
                    dengue_cases ds
                    LEFT JOIN barangays b
                        ON(ds.`barangay_id`=b.`id`)

                WHERE DATE_FORMAT(ds.case_date,'%Y-%m') = DATE_FORMAT('".($monthThatHasCase->MONTH.'-00')."','%Y-%m') 
                        AND ds.`case_date` BETWEEN '".$from."' AND '".$to."' ORDER BY ds.`case_date` ASC")->get();

            $result[]= [
                "month" => ($months[$dateFormat[1] - 1])." ".$dateFormat[0],
                "dailyCases" => $dailyCases
            ];

        }

        return $result;


    }


    public function getReportData(){
        
        $barangay       = $this->data['barangay'];
        $customDateFrom = $this->data['customDateFrom'];
        $customDateTo   = $this->data['customDateTo'];
        $filterDateBy   = $this->data['filterDateBy'];


        switch ($filterDateBy) {
            case 'Day':
                return $this->getDailyData(array(
                    'barangay'       => $barangay,
                    'customDateFrom' => $customDateFrom,
                    'customDateTo'   => $customDateTo
                ));

            break;
                
            case 'Month':
                return $this->getMonthlyData(array(
                    'barangay'       => $barangay,
                    'customDateFrom' => $customDateFrom,
                    'customDateTo'   => $customDateTo
                ));
            break;
                
            case 'Year':
                return $this->getYearlyData(array(
                    'barangay'       => $barangay,
                    'customDateFrom' => $customDateFrom,
                    'customDateTo'   => $customDateTo
                ));
            break;
            
            default:
                return $this->getDailyData(array(
                    'barangay' => $barangay,
                    'customDateFrom' => $customDateFrom,
                    'customDateTo' => $customDateTo
                ));
            break;
               
        }

    
    }


    public function getDailyData($filterData){

        $filters = $this->reportFilters($filterData);

        $query = "SELECT 
                        SUM(total_cases)  AS 'TOTAL_CASES',
                        SUM(total_deaths)  AS 'TOTAL_DEATHS',
                        SUM(total_recoveries)  AS 'TOTAL_RECOVERIES',
                        DATE_FORMAT(case_date,'%Y-%m-%d') AS 'DATES' 
                    
                    FROM dengue_cases ".$filters.' GROUP BY DATE_FORMAT(case_date,"%Y-%m-%d") ASC;';
                   

        return $this->setquery($query)->get();



    }

    public function getMonthlyData($filterData){

        $filters = $this->reportFilters($filterData);


        $query = "SELECT 
                    SUM(total_cases)  AS 'TOTAL_CASES',
                    SUM(total_deaths)  AS 'TOTAL_DEATHS',
                    SUM(total_recoveries)  AS 'TOTAL_RECOVERIES',
                    DATE_FORMAT(case_date,'%Y-%m') AS 'DATES' 
            
                FROM dengue_cases ".$filters.' GROUP BY DATE_FORMAT(case_date,"%Y-%m") ASC;';

     

        return $this->setquery($query)->get();

    }

    public function getYearlyData($filterData){

        $filters = $this->reportFilters($filterData);


        $query = "SELECT 
                    SUM(total_cases)  AS 'TOTAL_CASES',
                    SUM(total_deaths)  AS 'TOTAL_DEATHS',
                    SUM(total_recoveries)  AS 'TOTAL_RECOVERIES',
                    DATE_FORMAT(case_date,'%Y') AS 'DATES' 
            
                    FROM dengue_cases ".$filters.' GROUP BY DATE_FORMAT(case_date,"%Y") ASC;';

        return $this->setquery($query)->get();
    }

 

    public function reportFilters($filterData){


        $whereClause = '';


        if($filterData['barangay'] != null || ($filterData['customDateFrom'] != null AND  $filterData['customDateTo'] != null)){
            $whereClause .= 'WHERE ';

            $hasBarangay = false;

            if($filterData['barangay'] != null){
                $whereClause .='barangay_id = '.$filterData['barangay'].' ';
                $hasBarangay = true;
            }

            if($filterData['customDateFrom'] != null AND  $filterData['customDateTo'] != null){
                $whereClause .= $hasBarangay ? ' AND ' : '';
                $whereClause .= "case_date BETWEEN '".$filterData['customDateFrom']."' AND '".$filterData['customDateTo']."' ";
            }
        }


        return $whereClause;
    }

    


    


    
}
