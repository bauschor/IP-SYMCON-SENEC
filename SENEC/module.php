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

            $this->RegisterVariableString("SENEC_Token", "Access Token");
            $this->RegisterVariableString("SENEC_ID", "Anlagen ID");

        }   
		

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
        }
 

        /**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        * SENEC_GetToken();
        **/

        // -------------------------------------------------------------------------        
        public function GetToken() {

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
            } else {
                $token = json_decode($response, true)['token'];
    			$this->SetValue("SENEC_Token", $token);
            }
            curl_close($curl);                                                              // cURL Session beenden
      	}

        // -------------------------------------------------------------------------        
        public function GetID() {

            define('USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');

            $baseurl        = $this->ReadPropertyString("SENEC_API_Base_Url");
            $anlagenstub    = $this->ReadPropertyString("SENEC_API_Anlagen_Stub");
            $token          = $this->GetValue("SENEC_Token");

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
        
            $response = curl_exec($curl);                                                               // ok, jetzt ausführen

            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
            } else {
                $id = json_decode($response, true)[0]['id'];
                $this->SetValue("SENEC_ID", $id);
            }
            $this->_setIPSvar($IPS_SELF, "test", 17);
            curl_close($curl);                                                              // cURL Session beenden
        }

        // -------------------------------------------------------------------------        
        public function GetData() {

            define('USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');

            $baseurl    = $this->ReadPropertyString("SENEC_API_Base_Url");
            $datastub   = $this->ReadPropertyString("SENEC_API_Anlagen_Stub");
            $token      = $this->GetValue("SENEC_Token");
            $id         = $this->GetValue("SENEC_ID",);
            
            $curl = curl_init();                                                                // los geht's

            curl_setopt($curl, CURLOPT_URL, $baseurl."/".$anlagenstub."/".$id."/".datastub);    // URL zu den Daten
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
        
            $response = curl_exec($curl);                                                               // ok, jetzt ausführen

            $curl_errno = curl_errno($curl);

            if ($curl_errno > 0) {
                $curl_error = curl_error($curl);
            } else {
                $json = json_decode($response, true);

                foreach ($json as $name => $value) {
                    $this->_setIPSvar($SENEC_Vars, $name, $value);
                }
            }
            curl_close($curl);                                                              // cURL Session beenden
        }



        // ---------------------------------------------------------------------------------------------------------------
        /**
        * interne Funktionen in diesem Modul
        **/

        // -----------------------------------------------------
        // Variablen anlegen und/oder aktualisieren
        // -----------------------------------------------------
        private function _setIPSvar($parentID, $name, $value){

            $ident = str_replace(array("-", "/"), "_", $name);
            $ips_type = _getIPStype($value);

            switch ($ips_type){
            case 31:                                         // array
        //        echo "\n\n".$name." is an array\n";

                $CatID = @IPS_GetObjectIDByIdent($ident, $parentID);

                if ($CatID === false){
                    $CatID = IPS_CreateCategory();          // Kategorie anlegen
                    IPS_SetParent($CatID, $parentID);       // Kategorie einsortieren
                    IPS_SetName($CatID, $name);             // Kategorie benennen
                    IPS_SetIdent($CatID, $ident);
                }
                foreach ($value as $Aname => $Avalue) {
                    $this->_setIPSvar($CatID, $Aname, $Avalue);       // ab in die Rekursion
                }
                break;

            case 32:                                        // object
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

    }
?>
