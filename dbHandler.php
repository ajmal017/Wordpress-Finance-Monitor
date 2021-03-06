<?php 
/*
Class file that handles all exchanges with the wordpress database
*/
class DBHandler{

    private $logger;

    //Checks to see if database already includes tables
    //If not, creates them. Tables are hardocded
    public function CreateDB() { 
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Creating Database");

        $tablefinanceMonitorName = $wpdb->prefix . "financeMonitor";
        $tablefinanceMonitorStockDataName = $wpdb->prefix . "financeMonitorStockData";        

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );        
        
        $charset_collate = $wpdb->get_charset_collate();

        $tablefinanceMonitorStatement = "CREATE TABLE IF NOT EXISTS $tablefinanceMonitorName (
        ID int NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        keyEntry varchar(255),
        value varchar(255),
        PRIMARY KEY  (id)
        ) $charset_collate;";

        $tablefinanceMonitorStockDataStatement = "CREATE TABLE IF NOT EXISTS $tablefinanceMonitorStockDataName (
        ID int NOT NULL AUTO_INCREMENT,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        stockSymbol varchar(255),
        price decimal(8,2),
        numberOfStocks int,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $tablefinanceMonitorStatement );
        dbDelta( $tablefinanceMonitorStockDataStatement );
    }
    //Call this method to get from the database the last executed date of the email report
    public function getLastExecutedEmail(){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Getting last Executed email");
        $retVal = $this->getLastExecuted("lastExecutedEmail");
        $logger->write_log ("Last Executed Email - ".$retVal);
        return $retVal;
    }
    //Call this method to get from the database the last executed date of the monitoring
    public function getLastExecutedMonitoring(){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Getting last Executed Monitor");
        $retVal = $this->getLastExecuted("lastExecutedMonitor");
        $logger->write_log ("Last Executed Monitor - ".$retVal);
        return $retVal;
    }
    //Call this method to get from the database the last executed date of either the email report or monitoring itself
    public function getLastExecuted($key){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Getting last Executed");

        $tablefinanceMonitorName = $wpdb->prefix . "financeMonitor";
        $tablefinanceMonitorStockDataName = $wpdb->prefix . "financeMonitorStockData"; 
        
        $query ="SELECT value FROM $tablefinanceMonitorName WHERE keyEntry = '$key' ORDER BY id DESC LIMIT 1";
        $retVal = $wpdb->get_var($query);
        $logger->write_log ("Last Executed - ".$retVal);
        return $retVal;
    }
    //Call this method to insert into the database the last executed date of monitoring
    public function setLastExecutedMonitoring($format){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Setting last Executed Monitor");
        $this->setLastExecuted("lastExecutedMonitor",$format);
    }

    //Call this method to insert into the database the last executed date of an email report
    public function setLastExecutedEmail($format){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Setting last Executed Email");
        $this->setLastExecuted("lastExecutedEmail",$format);
    }

    //Call this method to insert into the database the last executed date of either Email report or monitor
    public function setLastExecuted($key,$format){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Setting last Executed");

        $tablefinanceMonitorName = $wpdb->prefix . "financeMonitor";
        $tablefinanceMonitorStockDataName = $wpdb->prefix . "financeMonitorStockData"; 
        //First delete previous lastExecuted
        $wpdb->delete( 
            $tablefinanceMonitorName, 
            array( 
                "keyEntry"=>"$key"
            )
        );
        $wpdb->insert( 
            $tablefinanceMonitorName, 
            array( 
                'keyEntry' => "$key", 
                'value' => current_time( $format )                
            )
        ); 
    }


    //Call this method to insert a stock price to the db
    public function setStockPrice($stockSymbol,$price,$numberOfStocks,$dateFormat){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Setting stock price");

        $tablefinanceMonitorStockDataName = $wpdb->prefix . "financeMonitorStockData"; 

        $wpdb->insert( 
            $tablefinanceMonitorStockDataName, 
            array( 
                'stockSymbol' => $stockSymbol,
                'price' => $price, 
                'numberOfStocks' => $numberOfStocks,
                'date' => current_time( $dateFormat )                
            )
        ); 
    }

    //Get last month's price of this stock
    //returns -1 if our database does not have a sample for the given period
    public function getLastMonthPrice($symbol){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Getting last month's price for ".$symbol);

        $retVal = $this->getLastPrice($symbol,"-1 month");

        return $retVal;
    }
    //Get last year's price of this stock
    //returns -1 if our database does not have a sample for the given period
    public function getLastYearPrice($symbol){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Getting last year's price for ".$symbol);

        $retVal = $this->getLastPrice($symbol,"-1 year");
        
        return $retVal;
    }
    //Get last month's price of this stock
    //returns -1 if our database does not have a sample for the given period
    private function getLastPrice($symbol,$timeAgo){
            global $wpdb,$logger;
            $this->InitialiseVars();
            $logger->write_log ("Getting ".$timeAgo." price for ".$symbol);
    
            $tablefinanceMonitorName = $wpdb->prefix . "financeMonitor";
            $tablefinanceMonitorStockDataName = $wpdb->prefix . "financeMonitorStockData"; 
    
            $lastMonthDate =date("Y-m-d H:i:s",strtotime($timeAgo));
           
            $query ="SELECT price FROM $tablefinanceMonitorStockDataName WHERE stockSymbol = '$symbol' AND date < '$lastMonthDate' ORDER BY id DESC LIMIT 1";
    
            $retVal = $wpdb->get_var($query);

            //if $retVal is empty it means we haven't build up enough data in our database
            if(empty($retVal)){
                $retVal=-1;
            }
            $logger->write_log ("Price- ".$retVal);
            return $retVal;
    }
    //Get last N price of this stock in the databse
    public function getLastNPrices($symbol,$number){
        global $wpdb,$logger;
        $this->InitialiseVars();
        $logger->write_log ("Getting last ".$number." prices for ".$symbol);

        $tablefinanceMonitorName = $wpdb->prefix . "financeMonitor";
        $tablefinanceMonitorStockDataName = $wpdb->prefix . "financeMonitorStockData"; 
      
        $query ="SELECT price FROM $tablefinanceMonitorStockDataName WHERE stockSymbol = '$symbol' ORDER BY id DESC LIMIT $number";

        $retVal = $wpdb->get_col($query);
        
        $logger->write_log ("Prices- ". implode(',',$retVal));
        
        return $retVal;
    }
    private function InitialiseVars()
    {
        global $logger;
       require_once( FINANCEMONITOR__PLUGIN_DIR . 'Logger.php');
       $logger = new Logger();
    }
}


?>