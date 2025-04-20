<?php
/*
*   Dieses Modul basiert auf Infos von
*   https://documenter.getpostman.com/view/10329335/UVCB9ihZ
*   https://documenter.getpostman.com/view/10329335/UVCB9ihW
*   https://www.postman.com/blue-moon-277072/senec-workspace/request/m6l3kql/data-availability
*
*   Und der Vorarbeit von https://community.symcon.de/u/oheidinger/summary
*   siehe hierzu auch https://community.symcon.de/t/senec-home-g2-plus/35997/6
*/

// Klassendefinition
    class SymconSenecData extends IPSModule {
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();
            
            $this->RegisterPropertyString("SENEC_API_Username", "");
	        $this->RegisterPropertyString("SENEC_API_Password", "");
		
            $this->RegisterPropertyString("SENEC_API_Login_Url",     "https://app-gateway-prod.senecops.com/v1/senec/login");
            $this->RegisterPropertyString("SENEC_API_Statistic_Url", "https://app-gateway-prod.senecops.com/v1/senec/anlagen");
            $this->RegisterPropertyString("SENEC_APIv1_Data_Url",    "https://app-gateway.prod.senec.dev/v1/senec/systems");
            $this->RegisterPropertyString("SENEC_APIv2_Data_Url",    "https://app-gateway.prod.senec.dev/v2/senec/systems");

            $this->RegisterPropertyInteger("SENEC_API_Data_Update_Interval", 6);
            $this->RegisterTimer("SENEC_API_Update_Data", 0, "SENEC_API_FullCycle($this->InstanceID);");

            $this->RegisterVariableString("SENEC_API_Token", "Access Token");
            $this->RegisterVariableString("SENEC_API_ID", "Anlagen ID");
            $this->RegisterVariableInteger("SENEC_API_ErrorCounter", "Anzahl API Fehler");

            $this->RegisterPropertyBoolean ("SENEC_API_TechnicalData_Loop", true);
            $this->RegisterPropertyBoolean ("SENEC_API_LiveData_Loop", true);
            $this->RegisterPropertyBoolean ("SENEC_API_Measurements_Loop", true);


            $this->RegisterPropertyInteger("SENEC_Local_Data_Update_Interval", 10);
            $this->RegisterTimer("SENEC_Local_Update_Data", 0, "SENEC_LOCAL_GetData($this->InstanceID);");

            $this->RegisterPropertyString("SENEC_Local_IP", "");
            $this->RegisterPropertyString('SENEC_Local_Query', '{"ENERGY":{"GUI_BAT_DATA_FUEL_CHARGE":"","STAT_STATE":"","GUI_BAT_DATA_POWER":"","GUI_INVERTER_POWER":"","GUI_HOUSE_POW":"","GUI_GRID_POW":"","SAFE_CHARGE_RUNNING":""},"PM1OBJ1":{}}');
            $this->RegisterVariableInteger("SENEC_Local_ErrorCounter", "Anzahl lokale Fehler");


            $this->RegisterPropertyString('SENEC_Local_Force_Charging', '{"ENERGY":{"SAFE_CHARGE_FORCE":"u8_01"}');
            $this->RegisterPropertyString('SENEC_Local_Prohibit_Charging', '{"ENERGY":{"SAFE_CHARGE_PROHIBIT":"u8_01"}');
        }   
		

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

            $minuten = $this->ReadPropertyInteger('SENEC_API_Data_Update_Interval');
            $this->_SetAPIupdateInterval($minuten);
            $this->SetValue("SENEC_API_ErrorCounter", 0);

            $sekunden = $this->ReadPropertyInteger('SENEC_Local_Data_Update_Interval');
            $this->_SetLALAupdateInterval($sekunden);
            $this->SetValue("SENEC_Local_ErrorCounter", 0);
        }
 
 
        /**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem Prefix 'SENEC', in PHP und JSON-RPC wie folgt zur Verfügung gestellt:
        *
        * SENEC_API_GetToken();
        * SENEC_API_GetID();
        * SENEC_API_GetData();
        * SENEC_API_GetTechnicalInfos();
        * SENEC_API_GetMeasurements();
        * SENEC_API_FullCycle();
        * SENEC_LOCAL_GetData();
        * SENEC_LOCAL_ForceCharging();
        * SENEC_LOCAL_ProhibitCharging();
        **/

        // -------------------------------------------------------------------------        
        public function API_GetToken() {

            $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

            $loginurl   = $this->ReadPropertyString("SENEC_API_Login_Url");
            $username   = $this->ReadPropertyString("SENEC_API_Username");            
            $password   = $this->ReadPropertyString("SENEC_API_Password");            

            $credentials = '{
                "username": "'.$username.'",
                "password": "'.$password.'"
            }';

            $curl = curl_init();                                                            // los geht's

            curl_setopt($curl, CURLOPT_URL, $loginurl);                                     // URL zum Loginformular
            curl_setopt($curl, CURLOPT_POST, true);                                         // Ein POST request soll es werden
            curl_setopt($curl, CURLOPT_POSTFIELDS, $credentials);                           // Die Infos als JSON Body schicken
            
            curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);                             // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                  // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                              // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                              // redirects nicht folgen

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                               // Die Antwort bitte nicht an STDOUT

            $headers = [
                'Content-Type: application/json'
            ];

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($curl);                                                                  // ok, jetzt ausführen
            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->_setIPSvar($this->InstanceID, "API_GetToken Status", $msg);
                $this->LogMessage("API_GetToken-".$msg, KL_ERROR);                                                 // Eintrag ins SYMCON Logfile

//                $this->_SetAPIupdateInterval(0);                                            // Bei Fehler kein weiteres Update mehr
                $APIerrorCounter = $this->GetValue("SENEC_API_ErrorCounter") +1;
                $this->SetValue("SENEC_API_ErrorCounter", $APIerrorCounter);                

            } else {
                $token = json_decode($response, true)['token'];
    			$this->SetValue("SENEC_API_Token", $token);
                $msg = "OK, Token erhalten: ".$token;
                $this->_setIPSvar($this->InstanceID, "API_GetToken Status", "OK");                
            }
            curl_close($curl);                                                                             // cURL Session beenden

            return($msg);
      	}

        // -------------------------------------------------------------------------        
        public function API_GetID() {

            $user_agent  = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

            $anlagenurl  = $this->ReadPropertyString("SENEC_API_Statistic_Url");
            $token       = $this->GetValue("SENEC_API_Token");

            $curl = curl_init();                                                            // los geht's

            curl_setopt($curl, CURLOPT_URL, $anlagenurl);                                   // URL zu den Anlageninfos
            curl_setopt($curl, CURLOPT_POST, false);                                        // Diesesmal kein POST request

            curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);                             // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                  // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                              // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                              // redirects nicht folgen
        
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                               // Die Antwort bitte als Rückgabewert von curl_exec
        
            $headers = [
                'Content-Type: application/json',
                'authorization: '.$token
            ];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
            $response = curl_exec($curl);                                                   // ok, jetzt ausführen
            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->_setIPSvar($this->InstanceID, "API_GetID Status", $msg);
                $this->LogMessage("API_GetID-".$msg, KL_ERROR);                                                    // Eintrag ins SYMCON Logfile                

//                $this->_SetAPIupdateInterval(0);                                            // Bei Fehler kein weiteres Update mehr
                $APIerrorCounter = $this->GetValue("SENEC_API_ErrorCounter") +1;
                $this->SetValue("SENEC_API_ErrorCounter", $APIerrorCounter);                
            } else {
                $id = json_decode($response, true)[0]['id'];
                $this->SetValue("SENEC_API_ID", $id);
                $msg = "OK, Anlagen ID: ".$id;
                $this->_setIPSvar($this->InstanceID, "API_GetID Status", "OK");                                           
            }            
            curl_close($curl);                                                              // cURL Session beenden

            return($msg);
        }

        // -------------------------------------------------------------------------        
        public function API_GetData() {

            $user_agent     = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

            $v2dataurl      = $this->ReadPropertyString("SENEC_APIv2_Data_Url");
            $token          = $this->GetValue("SENEC_API_Token");
            $id             = $this->GetValue("SENEC_API_ID",);
            $vars_api       = $this->_createIPScategory($this->InstanceID, "Vars (API)");

            $URL_dashboard  = $v2dataurl."/".$id."/dashboard";
            $result         = $this->_getAndStoreData($URL_dashboard, $token, $vars_api);

            // if ($result == "OK"){
            //     $minuten = $this->ReadPropertyInteger('SENEC_API_Data_Update_Interval');
            //     $this->_SetAPIupdateInterval($minuten);
            // }
            return $result;
        }

        // -------------------------------------------------------------------------        
        public function API_GetTechnicalInfos() {

            $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

            $v1dataurl  = $this->ReadPropertyString("SENEC_APIv1_Data_Url");
            $token      = $this->GetValue("SENEC_API_Token");
            $id         = $this->GetValue("SENEC_API_ID",);

            $vars_api   = $this->_createIPScategory($this->InstanceID, "Vars (API)");

            $URL_technical = $v1dataurl."/".$id."/technical-data";

            return $this->_getAndStoreData($URL_technical, $token, $vars_api);
        }
        
        // -------------------------------------------------------------------------        
        public function API_GetMeasurements() {

            $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

            $v2dataurl  = $this->ReadPropertyString("SENEC_APIv2_Data_Url");
            $token      = $this->GetValue("SENEC_API_Token");
            $id         = $this->GetValue("SENEC_API_ID",);

            $vars_api   = $this->_createIPScategory($this->InstanceID, "Vars (API)");

            $starttime  = mktime(0, 0, 0, 1, 1, date('Y') -1);
            $endtime    = mktime(23, 59, 59, 12, 31, date('Y'));

            $URL_history = $v2dataurl."/".$id."/measurements?resolution=YEAR&from=".$starttime."&to=".$endtime;

            return $this->_getAndStoreData($URL_history, $token, $vars_api);
        }          

        // -------------------------------------------------------------------------        
        public function API_FullCycle() {
            if(str_contains($this->API_GetToken(), "OK") == false){
                return 1;
            }
            if(str_contains($this->API_GetID(), "OK") == false){
                return 2;
            }
            if($this->ReadPropertyBoolean("SENEC_API_LiveData_Loop") == true) {            
                if($this->API_GetData() != "OK"){
                    return 3;
                }
            }
            if($this->ReadPropertyBoolean("SENEC_API_TechnicalData_Loop") == true) {
                if($this->API_GetTechnicalInfos() != "OK"){
                    return 4;
                }
            }
            if($this->ReadPropertyBoolean("SENEC_API_Measurements_Loop") == true) {
                if($this->API_GetMeasurements() != "OK"){
                    return 5;
                }
            }               
            return 0;
        }

        // -------------------------------------------------------------------------        
        public function LOCAL_GetData() {

            $ip = $this->ReadPropertyString('SENEC_Local_IP');
            $requestarray = $this->ReadPropertyString('SENEC_Local_Query');
            $timeout = 15;

            $vars_lala = $this->_createIPScategory($this->InstanceID, "Vars (LOCAL)");

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, "https://".$ip."/lala.cgi");
            curl_setopt($curl, CURLOPT_POST, true);                                 // Ein POST request soll es werden
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestarray);                  // Request als URL-Codierten String schicken
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                       // Die Antwort bitte nicht an STDOUT
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_HEADER, false);                              // Bitte den Header nicht in die Ausgabe aufnehmen
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                          // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                      // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                      // Keinen redirects folgen
        
            $response = curl_exec($curl);                                                          // Hier das Ergebnis
            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->LogMessage("LOCAL_GetData-".$msg, KL_ERROR);                                        // Eintrag ins SYMCON Logfile

                // $this->_SetLALAupdateInterval(0);
                $LOCALerrorCounter = $this->GetValue("SENEC_Local_ErrorCounter") +1;
                $this->SetValue("SENEC_Local_ErrorCounter", $LOCALerrorCounter);
            }else{
                $json = json_decode($response, true);                                   // Dekodieren der Antwort
        
                foreach ($json as $name => $value) {
                    $this->_setIPSvarLALA($vars_lala, $name, $value);                    
                }
                $msg = "OK";
            }
            $this->_setIPSvar($this->InstanceID, "LOCAL_GetData Status", $msg);
            // $this->_popupMessage($msg);

//            $this->LogMessage("KL_DEBUG: ".$msg, KL_DEBUG);                                       // Eintrag ins SYMCON Logfile
//            $this->LogMessage("KL_ERROR: ".$msg, KL_ERROR);                                       // Eintrag ins SYMCON Logfile
//            $this->LogMessage("KL_MESSAGE: ".$msg, KL_MESSAGE);                                   // Eintrag ins SYMCON Logfile
//            $this->LogMessage("KL_NOTIFY: ".$msg, KL_NOTIFY);                                     // Eintrag ins SYMCON Logfile
//            $this->LogMessage("KL_WARNING: "$msg, KL_WARNING);                                    // Eintrag ins SYMCON Logfile

//            parent::SendDebug("LOCAL_GetData", (string) $msg, 0);                                 // Befüllt das Debug-Fenster des Moduls

            curl_close($curl);                                                      // cURL Session beenden

            return $msg;
        }

        // -------------------------------------------------------------------------        
        public function LOCAL_ForceCharging() {

            $ip = $this->ReadPropertyString('SENEC_Local_IP');
            $requestarray = $this->ReadPropertyString('SENEC_Local_Force_Charging');
            $timeout = 15;

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, "https://".$ip."/lala.cgi");
            curl_setopt($curl, CURLOPT_POST, true);                                 // Ein POST request soll es werden
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestarray);                  // Request als URL-Codierten String schicken
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                       // Die Antwort bitte nicht an STDOUT
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_HEADER, false);                              // Bitte den Header nicht in die Ausgabe aufnehmen
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                          // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                      // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                      // Keinen redirects folgen
        
            $response = curl_exec($curl);                                                          // Hier das Ergebnis
            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->LogMessage("LOCAL_ForceCharging-".$msg, KL_ERROR);                                  // Eintrag ins SYMCON Logfile
            }else{
                $msg = "Manuelles Laden gestartet";
            }
            $this->_setIPSvar($this->InstanceID, "LOCAL_GetData Status", $msg);
            // $this->_popupMessage($msg);                                              

            curl_close($curl);                                                                     // cURL Session beenden

            return $msg;
        }

        // -------------------------------------------------------------------------        
        public function LOCAL_ProhibitCharging() {

            $ip = $this->ReadPropertyString('SENEC_Local_IP');
            $requestarray = $this->ReadPropertyString('SENEC_Local_Prohibit_Charging');
            $timeout = 15;

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, "https://".$ip."/lala.cgi");
            curl_setopt($curl, CURLOPT_POST, true);                                 // Ein POST request soll es werden
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestarray);                  // Request als URL-Codierten String schicken
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                       // Die Antwort bitte nicht an STDOUT
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_HEADER, false);                              // Bitte den Header nicht in die Ausgabe aufnehmen
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                          // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                      // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                      // Keinen redirects folgen
        
            $response = curl_exec($curl);                                                          // Hier das Ergebnis
            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->LogMessage("LOCAL_ProhibitCharging-".$msg, KL_ERROR);                               // Eintrag ins SYMCON Logfile
            }else{
                $msg = "Manuelles Laden gestoppt";
            }
            $this->_setIPSvar($this->InstanceID, "LOCAL_GetData Status", $msg);                
            // $this->_popupMessage($msg);


            curl_close($curl);                                                                     // cURL Session beenden

            return $msg;
        }

        // ---------------------------------------------------------------------------------------------------------------
        /**
        * interne Funktionen in diesem Modul
        **/
        // -----------------------------------------------------
        private function _getAndStoreData($URL, $token, $destination){

            $user_agent  = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

            $curl = curl_init();                                                                                       // los geht's

            curl_setopt($curl, CURLOPT_URL, $URL);                                              // URL zu den Daten
            curl_setopt($curl, CURLOPT_POST, false);                                            // Diesesmal kein POST request

            curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);                                 // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                      // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                                  // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                                  // redirects nicht folgen
        
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                                   // Die Antwort bitte als Rückgabewert von curl_exec
        
            $headers = [
                'Content-Type: application/json',
                'authorization: '.$token
            ];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
            $response = curl_exec($curl);                                                                      // ok, jetzt ausführen
            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->LogMessage("getAndStoreData-".$msg, KL_ERROR);                                                  // Eintrag ins SYMCON Logfile

//                $this->_SetAPIupdateInterval(0);                                                // Bei Fehler kein weiteres Update mehr
                $APIerrorCounter = $this->GetValue("SENEC_API_ErrorCounter") +1;
                $this->SetValue("SENEC_API_ErrorCounter", $APIerrorCounter);
            } else {
                $json = json_decode($response, true);

                foreach ($json as $name => $value) {
                    $this->_setIPSvar($destination, $name, $value);                    
                }
                $msg = "OK";
            }
            $this->_setIPSvar($this->InstanceID, "API_GetData Status", $msg);                
            // $this->_popupMessage($msg);

            curl_close($curl);                                                                                 // cURL Session beenden

            return $msg;            
        }

                
        // -----------------------------------------------------
        private function _createIPScategory($parentID, $name){

            $ident = str_replace(array("-", "/", ":", ".", "(", ")", " "), "_", $name);
            $CatID = @IPS_GetObjectIDByIdent($ident, $parentID);

            if ($CatID === false){
                $CatID = IPS_CreateCategory();                      // Kategorie anlegen
                IPS_SetParent($CatID, $parentID);                   // Kategorie einsortieren
                IPS_SetName($CatID, $name);                         // Kategorie benennen
                IPS_SetIdent($CatID, $ident);                       // Ident für diese Kategorie setzen
            }

            return $CatID;
        }

        // -----------------------------------------------------
        private function _setIPSvar($parentID, $name, $value){

            $ident = str_replace(array("-", "/"), "_", $name);
            $ips_type = $this->_getIPStype($value);

            switch ($ips_type){
            case 31:                                                    // array
                $CatID = @IPS_GetObjectIDByIdent($ident, $parentID);

                if ($CatID === false){
                    $CatID = IPS_CreateCategory();                      // Kategorie anlegen
                    IPS_SetParent($CatID, $parentID);                   // Kategorie einsortieren
                    IPS_SetName($CatID, $name);                         // Kategorie benennen
                    IPS_SetIdent($CatID, $ident);
                }
                foreach ($value as $Aname => $Avalue) {
                    $this->_setIPSvar($CatID, $Aname, $Avalue);         // ab in die Rekursion
                }
                break;

            case 32:                                                    // object
        /*
                foreach ($value as $Oname => $Ovalue) {
                    $this->_setIPSvar($CatID, $Oname, $Ovalue);
                }
        */
                break;

            default:
                $ident = str_replace(array("-", "/", ":", ".", " "), "_", $name);
                $var_id = @IPS_GetObjectIDByIdent($ident, $parentID);

                if ($var_id === false){
                    $var_id = IPS_CreateVariable($ips_type);
                    IPS_SetParent($var_id, $parentID);          // einsortieren
                    IPS_SetName($var_id, $name);                // name setzen
                    IPS_SetIdent($var_id, $ident);              // ident setzen, weil der pro zweig eindeutig sein muss
                }

                SetValue($var_id, $value);                
                break;
            }
        }

        // -----------------------------------------------------
        private function _setIPSvarLALA($parentID, $name, $value){

            $ident = str_replace(array("-", "/"), "_", $name);
            $ips_type = $this->_getIPStype($value);

            switch ($ips_type){
            case 31:                                                    // array
                $CatID = @IPS_GetObjectIDByIdent($ident, $parentID);

                if ($CatID === false){
                    $CatID = IPS_CreateCategory();                      // Kategorie anlegen
                    IPS_SetParent($CatID, $parentID);                   // Kategorie einsortieren
                    IPS_SetName($CatID, $name);                         // Kategorie benennen
                    IPS_SetIdent($CatID, $ident);                       // Ident setzen
                }
                foreach ($value as $Aname => $Avalue) {
                    $this->_setIPSvarLALA($CatID, $Aname, $Avalue);     // ab in die Rekursion
                }
                break;

            case 32:                                                    // object
        /*
                foreach ($value as $Oname => $Ovalue) {
                    $this->_setIPSvarLALA($CatID, $Oname, $Ovalue);
                }
        */
                break;

            default:
                $ident = str_replace(array("-", "/", ":", "."), "_", $name);

                $data = substr(strrchr($value, "_"), 1);
                $type = strstr($value, "_", true);

                switch ($type){
                    case 'fl':
                        $data = "".$data;
                        $data = $this->_hex2float($data);
                        $data = round($data, 2);                  
                        $ips_type = 2;              // float                  
                        break;
                    case 'u1':
                    case 'u3':
                    case 'u6':            
                    case 'u8':
                    case 'i1':  
                    case 'i3':
                    case 'i8':            
                        $data = $this->_hex2int($data);
                        $ips_type = 1;              // integer           
                        break;
                    case 'st':
                    case 'VARIABLE':            
                        $ips_type = 3;              // string                         
                        break;       
                    default:                        // unknown
                        $ips_type = 3;              // string
                        break;
                }

                $var_id = @IPS_GetObjectIDByIdent($ident, $parentID);

                if ($var_id === false){
                    $var_id = IPS_CreateVariable($ips_type);    // Variable anlegen
                    IPS_SetParent($var_id, $parentID);          // einsortieren
                    IPS_SetName($var_id, $name);                // name setzen
                    IPS_SetIdent($var_id, $ident);              // ident setzen, weil der pro zweig eindeutig sein muss
                }

                SetValue($var_id, $data);                
                break;
            }
        }
        
        
        // -------------------------------------------------------------------------
        private function _getIPStype($data){
            if (is_int($data)){
                return 1;               // integer
            }
            if (is_float($data)){
                return 2;               // float
            }
            if (is_bool($data)){
                return 0;               // boolean
            }
            if (is_numeric($data)){
                return 2;               // float
            }
            if (is_array($data)){
                return 31;              // array
            }  
            if (is_object($data)){
                return 32;              // object
            }        
            return 3;                   // unknown => string
        }        

        // --------------------------------------------------
        private function _hex2float($num) {
            
            $binfinal = sprintf("%032b", hexdec($num));
            $sign = substr($binfinal, 0, 1);
            $exp = substr($binfinal, 1, 8);
            $mantissa = "1".substr($binfinal, 9);
            $mantissa = str_split($mantissa);
            $exp = bindec($exp) -127;
            $significand=0;
            for ($i = 0; $i < 24; $i++) {
                $significand += (1 / pow(2,$i)) * $mantissa[$i];
            }
            return $significand * pow(2, $exp) * ($sign*-2+1);
        }

        // --------------------------------------------------
        private function _hex2int($num){
            return hexdec($num);
        }
 
        // -------------------------------------------------------------------------
        private function _popupMessage($text){

            $this->UpdateFormField('InfoPopup_Text', 'caption', $text);
            $this->UpdateFormField('InfoPopup', 'visible', true);
        }

        // -------------------------------------------------------------------------
        private function _SetAPIupdateInterval($minuten){
            $msec = $minuten > 0 ? $minuten * 60 * 1000 : 0;
            $this->SetTimerInterval('SENEC_API_Update_Data', $msec);
        }

        // -------------------------------------------------------------------------
        private function _SetLALAupdateInterval($sekunden){
            $msec = $sekunden > 0 ? $sekunden * 1000 : 0;
            $this->SetTimerInterval('SENEC_Local_Update_Data', $msec);
        }
    }
?>
