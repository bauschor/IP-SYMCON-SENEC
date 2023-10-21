# SYMCON-SENEC-DATA
Dieses Modul liest Daten eines SENEC Speichers aus (sowohl per Internet-API als auch lokal per IP/curl)


Für die Ergebnisse werden unterhalb der Instanz zwei neue Kategorien angelegt

![image](https://github.com/bauschor/IP-SYMCON-SENEC/assets/24826836/02134215-bf55-4a94-92f5-2ae6672344d5)


Es folgende Funktionen zur Verfügung gestellt:
- SENEC_API_GetToken();
  (muss als erstes aufgerufen werden)
- SENEC_API_GetID();
  (mit dem Token bekommt man die ID des Speichers)
- SENEC_API_GetData();
  (und jetzt darf man die Daten auslesen)
- SENEC_API_FullCycle();
  (führt nacheinander GetToken, GetID und GetData aus)
  
- SENEC_LOCAL_GetData();
  (diese Funktion triggert das lokale Auslesen der Daten)
