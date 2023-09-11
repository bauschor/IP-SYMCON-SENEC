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
        * SENEC_GetToken()
        */

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
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                              // redirects folgen

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
            $username       = $this->ReadPropertyString("SENEC_API_Username");            
            $password       = $this->ReadPropertyString("SENEC_API_Password");            
            $token          = $this->GetValue("SENEC_Token");

            $curl = curl_init();                                                            // los geht's

            curl_setopt($curl, CURLOPT_URL, $baseurl."/".$anlagenstub);                     // URL zu den Anlageninfos
            curl_setopt($curl, CURLOPT_POST, false);                                        // Diesesmal kein POST request

            curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);                              // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                  // keine Prüfung ob Hostname im Zertifikat
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                              // keine Überprüfung des Peerzertifikats
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                              // redirects folgen
        
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
            curl_close($curl);                                                              // cURL Session beenden
        }
        
    }
?>
