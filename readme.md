# SYMCON-SENEC-DATA
Dieses Modul liest Daten eines SENEC Speichers aus (sowohl per Internet-API als auch lokal per IP/curl)

### Inhaltsverzeichnis

1. [Software-Installation](#1-software-installation)
2. [Hinzufügen der Instanz in IP-Symcon](#2-hinzufügen-der-instanz-in-ip-symcon)
3. [Konfiguration](#3-konfiguration)
4. [Darstellung im Objektbaum](#4-darstellung-im-objektbaum)
5. [WebFront](#5-webfront)
6. [Funktionsumfang](#6-funktionsumfang)

### 1. Software-Installation

Über das Modul-Control folgende URL hinzufügen:
`https://github.com/bauschor/IP-SYMCON-SENEC.git`  

### 2. Hinzufügen der Instanz in IP-Symcon

1. Im Objektbau mittels rechter Maustaste
2. Objekt hinzufügen -> Instanz
3. Suche per Schnellfilter "SENEC"

### 3.Konfiguration
Bei der Instanz-Konfiguration gibt es zwei Sektionen

#### Einstellungen für die Anbindung der API
![image](https://github.com/user-attachments/assets/9fa8d00e-5660-4b9a-b58f-f7e50f44556c)

Username & Passwort entsprechen den Login-Daten die für den Zugang zu https://mein-senec.de benötigt werden.

#### Einstellungen für die lokale Abfrage des SENEC Speichers
![image](https://github.com/user-attachments/assets/1f92159f-b6d5-4114-ae6a-d04da0b2f54e)

Bitte die IP-Adresse des lokalen SENEC Speichers eintragen
Den JSON-Request ist vorkonfiguriert.
Er bestimmt, welche Werte ausgelesen und im Objektbaum augelistet werden, z.B.

![image](https://github.com/user-attachments/assets/60b79a05-1c55-4135-8f8b-7c8ba8f00153)



### 4. Darstellung im Objektbaum
Für die Ergebnisse werden unterhalb der Instanz zwei neue Kategorien sowie einige Variablen anlegt, die Aufschluss über den Status des Moduls geben.

![image](https://github.com/user-attachments/assets/2e3009f0-576b-4e39-b18e-2de9a3ac76fc)

Unterhalb der beiden Kategorien finden sich die von der jeweiligen Schnittstelle eingelesenen Daten als aufklappbarer Baum

### 5. WebFront
Im Webfront werden die Status-Variablen (wie im Objektbaum) abgezeigt

### 6. Funktionsumfang
Es wwerden Funktionen für die Abfrage der API als auch für die Anfrage des lokalen SENEC Speichers zur Verfügung gestellt

Funktionsname | Beschreibung
-|-
SENEC_API_GetToken(); | Muss als Erstes aufgerufen werden, holt ein Token für die Authentifizierung
SENEC_API_GetID(); | Mit dem Token bekommt man die ID des Speichers
SENEC_API_GetData(); |Und jetzt darf man die Messwerte auslesen
SENEC_API_GetTechnicalInfos(); | Liefert Infos zum Speicher, Garantiedauer, etc
SENEC_API_GetMeasurements(); | Holt historische Daten für das aktuelle Kalenderjahr
SENEC_API_FullCycle(); | führt nacheinander GetToken, GetID, GetData, GetTechnicalInfos, GetMeasurements aus

Funktionsname | Beschreibung
-|-
SENEC_LOCAL_GetData(); | Triggert das lokale Auslesen der Daten
SENEC_LOCAL_ForceCharging(); | Startet eine manuelle Ladung des Akkus
SENEC_LOCAL_ProhibitCharging(); | Stoppt die manuelle Ladung des Akkus

### 7. Funktion
Die Knöpfe bei der Instanzkonfiguraion testen die einzelnen Funktionen.
Wenn bei den Einstellungen ein Updatezyklus ungleich 0 hinterlegt ist, holt das Modul gemäß den hinterlegten Zeiten die Daten automatisch.

![image](https://github.com/user-attachments/assets/355b1e84-0f76-4502-a1e9-0bd5b659e5c8)
