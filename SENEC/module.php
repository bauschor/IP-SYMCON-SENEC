<?php
    // Klassendefinition
    class SymconSenecData extends IPSModule {
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();
            
            $this->RegisterPropertyString("SENEC_API_Username", "");
	        $this->RegisterPropertyString("SENEC_API_Password", "");
		
            $this->RegisterPropertyString("SENEC_API_Base_Url", "https://app-gateway-prod.senecops.com/v1/senec");
            $this->RegisterPropertyString("SENEC_API_Login_Stub", "login");
            $this->RegisterPropertyString("SENEC_API_Anlagen_Stub", "anlagen");
            $this->RegisterPropertyString("SENEC_API_Data_Stub", "dashboard");

            $this->RegisterPropertyInteger("SENEC_API_Data_Update_Interval", 6);
            $this->RegisterTimer("SENEC_API_Update_Data", 60*1000, "SENEC_API_GetData($this->InstanceID);");

            $this->RegisterVariableString("SENEC_API_Token", "Access Token");
            $this->RegisterVariableString("SENEC_API_ID", "Anlagen ID");

            $this->RegisterPropertyString("SENEC_Local_IP", "");
            $this->RegisterPropertyString('SENEC_Local_Query', '{"ENERGY":{"GUI_BAT_DATA_FUEL_CHARGE":"","STAT_STATE":"","GUI_BAT_DATA_POWER":"","GUI_INVERTER_POWER":"","GUI_HOUSE_POW":"","GUI_GRID_POW":""},"PM1OBJ1":{},"STATISTIC":{}}');
            $this->RegisterPropertyInteger("SENEC_Local_Data_Update_Interval", 10);
            $this->RegisterTimer("SENEC_Local_Update_Data", 0, "SENEC_LOCAL_GetData($this->InstanceID);");

        }   
		

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

            $minuten = $this->ReadPropertyInteger('SENEC_API_Data_Update_Interval');
            $this->_SetAPIupdateInterval($minuten);

            $sekunden = $this->ReadPropertyInteger('SENEC_Local_Data_Update_Interval');
            $this->_SetLALAupdateInterval($sekunden);

            $this->_createIPScategory($this->InstanceID, "Vars (API)");
            $this->_createIPScategory($this->InstanceID, "Vars (LOCAL)");
        }
 
 
        /**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wie folgt zur Verfügung gestellt:
        *
        * SENEC_API_GetToken();
        * SENEC_API_GetID();
        * SENEC_API_GetData();
        **/

        // -------------------------------------------------------------------------        
        public function API_GetToken() {

            define('USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');

            $baseurl    = $this->ReadPropertyString("SENEC_API_Base_Url");
            $loginstub  = $this->ReadPropertyString("SENEC_API_Login_Stub");
            $username   = $this->ReadPropertyString("SENEC_API_Username");            
            $password   = $this->ReadPropertyString("SENEC_API_Password");            

            $credentials = '{
                "username": "'.$username.'",
                "password": "'.$password.'"
            }';

            $curl = curl_init();                                                            // los geht's

            curl_setopt($curl, CURLOPT_URL, $baseurl."/".$loginstub);                       // URL zum Loginformular
            curl_setopt($curl, CURLOPT_POST, true);                                         // Ein POST request soll es werden
            curl_setopt($curl, CURLOPT_POSTFIELDS, $credentials);                           // Die Infos als JSON Body schicken
            
            curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);                              // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                  // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                              // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                              // redirects nicht folgen

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                               // Die Antwort bitte nicht an STDOUT

            $headers = [
                'Content-Type: application/json'
            ];

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


            $response = curl_exec($curl);                                                               // ok, jetzt ausführen

            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->_setIPSvar($this->InstanceID, "Fehler API_GetToken", $msg);
            } else {
                $token = json_decode($response, true)['token'];
    			$this->SetValue("SENEC_API_Token", $token);
                $msg = "Token erhalten: ".$token;
            }
            curl_close($curl);                                                              // cURL Session beenden

            $this->_popupMessage($msg);
      	}

        // -------------------------------------------------------------------------        
        public function API_GetID() {

            define('USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');

            $baseurl        = $this->ReadPropertyString("SENEC_API_Base_Url");
            $anlagenstub    = $this->ReadPropertyString("SENEC_API_Anlagen_Stub");
            $token          = $this->GetValue("SENEC_API_Token");

            $curl = curl_init();                                                            // los geht's

            curl_setopt($curl, CURLOPT_URL, $baseurl."/".$anlagenstub);                     // URL zu den Anlageninfos
            curl_setopt($curl, CURLOPT_POST, false);                                        // Diesesmal kein POST request

            curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);                              // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
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
                $this->_setIPSvar($this->InstanceID, "Fehler API_GetID", $msg);                           
            } else {
                $id = json_decode($response, true)[0]['id'];
                $this->SetValue("SENEC_API_ID", $id);
                $msg = "Anlagen ID: ".$id;                
            }            
            curl_close($curl);                                                              // cURL Session beenden
            $this->_popupMessage($msg);            
        }

        // -------------------------------------------------------------------------        
        public function API_GetData() {

            define('USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');

            $baseurl        = $this->ReadPropertyString("SENEC_API_Base_Url");
            $anlagenstub    = $this->ReadPropertyString("SENEC_API_Anlagen_Stub");
            $datastub       = $this->ReadPropertyString("SENEC_API_Data_Stub");
            $token          = $this->GetValue("SENEC_API_Token");
            $id             = $this->GetValue("SENEC_API_ID",);
            
            $curl = curl_init();                                                                // los geht's

            curl_setopt($curl, CURLOPT_URL, $baseurl."/".$anlagenstub."/".$id."/".$datastub);   // URL zu den Daten
            curl_setopt($curl, CURLOPT_POST, false);                                            // Diesesmal kein POST request

            curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);                                  // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                      // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                                  // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                                  // redirects nicht folgen
        
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                                   // Die Antwort bitte als Rückgabewert von curl_exec
        
            $headers = [
                'Content-Type: application/json',
                'authorization: '.$token
            ];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
            $response = curl_exec($curl);                                                       // ok, jetzt ausführen

            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->_setIPSvar($this->InstanceID, "Fehler API_GetData", $msg);                
                $this->_popupMessage($msg);
                $this->_SetAPIupdateInterval(0);                               
            } else {
                $json = json_decode($response, true);

                foreach ($json as $name => $value) {
                    $this->_setIPSvar($this->InstanceID, $name, $value);
                }
            }
            curl_close($curl);                                                              // cURL Session beenden
        }


        // -------------------------------------------------------------------------        
        public function LOCAL_GetData() {

            $ip = $this->ReadPropertyString('SENEC_Local_IP');
            $requestarray = $this->ReadPropertyString('SENEC_Local_Query');

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
        
            $response = curl_exec($curl);                                           // Hier das Ergebnis
            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
                $msg = "FEHLER: ".$curl_error;
                $this->_setIPSvar($this->InstanceID, "Fehler LOCAL_GetData", $msg);                
                $this->_popupMessage($msg);
                $this->_SetAPIupdateInterval(0);        
            }else{
                $json = json_decode($response, true);                               // Dekodieren der Antwort
        
                foreach ($json as $name => $value) {
                    $this->_setIPSvarLALA($this->InstanceID, $name, $value);
                }
            }

            curl_close($curl);                                                      // cURL Session beenden
        }

        // ---------------------------------------------------------------------------------------------------------------
        /**
        * interne Funktionen in diesem Modul
        **/
        // -----------------------------------------------------
        private function _createIPScategory($parentID, $name){

            $ident = str_replace(array("-", "/", ":", ".", "(", ")", " "), "_", $name);
            $CatID = @IPS_GetObjectIDByIdent($ident, $parentID);

            if ($CatID === false){
                $CatID = IPS_CreateCategory();                      // Kategorie anlegen
                IPS_SetParent($CatID, $parentID);                   // Kategorie einsortieren
                IPS_SetName($CatID, $name);                         // Kategorie benennen
                IPS_SetIdent($CatID, $ident);
            }
        }

        // -----------------------------------------------------
        private function _setIPSvar($parentID, $name, $value){

            $ident = str_replace(array("-", "/"), "_", $name);
            $ips_type = $this->_getIPStype($value);

            switch ($ips_type){
            case 31:                                                    // array
        //        echo "\n\n".$name." is an array\n";

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
                echo "\n\n".$name." is an object\n";  
        /*
                foreach ($value as $Oname => $Ovalue) {
                    $this->_setIPSvar($CatID, $Oname, $Ovalue);
                }
        */
                break;

            default:
        //        echo ($name." -> ".$value."\n");
                
                $ident = str_replace(array("-", "/", ":", "."), "_", $name);
                $var_id = @IPS_GetObjectIDByIdent($ident, $parentID);

                if ($var_id === false){
                    $var_id = IPS_CreateVariable($ips_type);
                    IPS_SetParent($var_id, $parentID);          // einsortieren
                    IPS_SetName($var_id, $name);                // name setzen
                    IPS_SetIdent($var_id, $ident);              // jetzt erst ident setzen, weil der pro zweig eindeutig sein muss
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
        //      echo "\n\n".$name." is an array\n";

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
        //      echo "\n\n".$name." is an object\n";  
        /*
                foreach ($value as $Oname => $Ovalue) {
                    $this->_setIPSvarLALA($CatID, $Oname, $Ovalue);
                }
        */
                break;

            default:
        //      echo ($name." -> ".$value."\n");
                
                $ident = str_replace(array("-", "/", ":", "."), "_", $name);

                $value = substr(strrchr($data, "_"), 1);
                $type = strstr($data, "_", true);
                $ips_type = _transformSENECtoIPStype($type);

                $var_id = @IPS_GetObjectIDByIdent($ident, $parentID);

                if ($var_id === false){
                    $var_id = IPS_CreateVariable($ips_type);    // Variable anlegen
                    IPS_SetParent($var_id, $parentID);          // einsortieren
                    IPS_SetName($var_id, $name);                // name setzen
                    IPS_SetIdent($var_id, $ident);              // jetzt erst ident setzen, weil der pro zweig eindeutig sein muss
                }

                SetValue($var_id, $value);                
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
            return 3;                   // string
        }        

        // -------------------------------------------------------------------------
        private function _transformSENECtoIPStype($type){

            switch ($type){
                case 'fl':
                    $value = "".$value;
                    $value = _hex2float($value);
                    $value = round($value, 2);                  
                    $ips_type = 2;              // float                  
                    break;
                case 'u1':
                case 'u3':
                case 'u6':            
                case 'u8':
                case 'i1':  
                case 'i3':
                case 'i8':            
                    $value = _hex2int($value);
                    $ips_type = 1;              // integer           
                    break;
                case 'st':
                case 'VARIABLE':            
                    $ips_type = 3;              // string                         
                    break;       
                default:
        //          echo "Unbekannter Datentyp: ".$type." (".$name." -> ".$value.") - (".$parent." - ".$ident.")\n";                
                    $ips_type = 3;              // string
                    break;
            }

            return($ips_type);
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
