classDiagram
    %% =======================================================
    %% CONFIGURAZIONE STILI E TEMI
    %% =======================================================
    classDef domain fill:#e1f5fe,stroke:#01579b,stroke-width:2px;
    classDef security fill:#ffebee,stroke:#b71c1c,stroke-width:2px;
    classDef infra fill:#f3e5f5,stroke:#4a148c,stroke-width:2px,stroke-dasharray: 5 5;
    classDef enum fill:#fff3e0,stroke:#e65100,stroke-width:1px;
    classDef debug fill:#fffde7,stroke:#fbc02d,stroke-width:1px;

    %% =======================================================
    %% AREA 1: DOMINIO DATI (Core Business)
    %% =======================================================
    namespace GestioneSoci {
        class Socio {
            +String CodiceFiscale
            +String Matricola
            +DatiAnagrafici DatiPersonali
            +StatoIscrizione Stato
            +List~Documento~ DocumentiAssociati
            +aggiornaAnagrafica()
            +verificaMorosita() bool
        }
        
        class DatiAnagrafici {
            <<Value Object>>
            +String Nome
            +String Cognome
            +Date DataNascita
            +String Indirizzo
            +String Email
        }

        class Documento {
            <<Abstract>>
            +UUID IdUnivoco
            +String NomeFile
            +String HashSHA256
            +StatoDocumento Stato
            +Date DataCaricamento
            +getMetadati() JSON
            +verificaIntegrita() bool
        }

        class ModuloIscrizione {
            +Int AnnoSolare
            +Decimal QuotaVersata
            +String MetodoPagamento
        }

        class ConsensoGDPR {
            +Bool TrattamentoDati
            +Bool CessioneTerzi
            +Bool Marketing
            +Date DataFirma
            +String VersioneInformativa
            +Bool Attivo
            +Date DataRevoca
            +String MotivoRevoca
            +aggiornaConsensi()
            +revoca()
        }
    }

    %% =======================================================
    %% AREA 2: SICUREZZA E CONTROLLO ACCESSI
    %% =======================================================
    namespace SecurityLayer {
        class UtenteSistema {
            <<Abstract>>
            +Int ID
            +String Username
            -String PasswordHash
            +String Token2FA
            +autentica() bool
            +cambiaPassword()
            +verificaTOTP() bool
            +generaCodiceOTP() string
            +pseudonimizza(String) String
        }

        class Amministratore {
            +creaUtente()
            +revocaPermessi()
            +visualizzaAuditLog()
        }

        class Operatore {
            +caricaPratica()
            +ricercaSocio()
            +stampaReport()
        }

        class AuditTrail {
            <<Singleton>>
            +logEvento(Utente, Azione, ResourceID)
            +esportaLog(Formato) File
            -scritturaSuDiscoProtetta()
            -pseudonimizzaDatiSensibili()
        }

        class AccessControlList {
            <<Service>>
            +verificaPermesso(Utente, Risorsa) bool
            +grant(Ruolo, Permesso)
        }
    }

    %% =======================================================
    %% AREA 3: INFRASTRUTTURA E SERVIZI ESTERNI
    %% =======================================================
    namespace InfrastrutturaIT {
        class ICloudStorage {
            <<Interface>>
            +upload(FileBlob) URL
            +download(UUID) FileBlob
            +delete(UUID)
        }

        class GoogleDriveAdapter {
            <<Service>>
            -String API_KEY
            -String ClientSecret
            +upload()
        }

        class SharePointAdapter {
            <<Service>>
            -String TenantID
            +upload()
        }

        class OCREngine {
            <<Service>>
            +processaImmagine(Bitmap) String
            +estraiCampiChiave(String) Dictionary
        }

        class DatabaseConnection {
            <<Singleton>>
            +getConnection() PDO
        }
    }

    %% =======================================================
    %% AREA 4: DIAGNOSTICA E DEBUG
    %% =======================================================
    namespace Debug {
        class SystemCheck {
            <<Service>>
            +runDiagnostics() Array
            +checkRoutes(List) Array
            +printReport()
        }
        class QueryLogger {
            <<Service>>
            +logQuery(String)
        }
        class UserErrorLogger {
            <<Service>>
            +logError(String)
        }
        class DatabaseInspector {
            <<Service>>
            +checkIntegrity() String
            +getTablesSummary() Array
            +getDatabaseSize() String
        }
        class LogViewer {
            <<Service>>
            +listLogs() Array
            +readTail(String, Int) String
        }
    }

    %% =======================================================
    %% DEFINIZIONE ENUMERATORI (Stati Fissi)
    %% =======================================================
    class StatoDocumento {
        <<Enumeration>>
        IN_ATTESA
        VALIDATO
        RESPINTO
        ARCHIVIATO
    }

    class StatoIscrizione {
        <<Enumeration>>
        ATTIVO
        SOSPESO
        DECADUTO
    }

    %% =======================================================
    %% RELAZIONI E DIPENDENZE
    %% =======================================================
    
    %% Relazioni Ereditarietà
    Documento <|-- ModuloIscrizione
    Documento <|-- ConsensoGDPR
    UtenteSistema <|-- Amministratore
    UtenteSistema <|-- Operatore
    ICloudStorage <|.. GoogleDriveAdapter : Implementa
    ICloudStorage <|.. SharePointAdapter : Implementa

    %% Relazioni Composizione
    Socio *-- DatiAnagrafici : Composto da
    Socio *-- ConsensoGDPR : Necessita

    %% Relazioni Aggregazione
    Socio o-- ModuloIscrizione : Possiede
    Socio o-- StatoIscrizione : Ha stato

    %% Relazioni Associazione
    Operatore ..> Socio : Gestisce
    Operatore ..> OCREngine : Usa
    Operatore ..> ICloudStorage : Carica Dati
    Documento ..> StatoDocumento : Ha stato
    
    %% Sicurezza
    UtenteSistema ..> AccessControlList : Controllato da
    AccessControlList --> AuditTrail : Notifica accessi
    
    %% Relazioni Debug
    GlobalExceptionHandler ..> UserErrorLogger : Usa
    SystemCheck ..> UtenteSistema : Verifica Permessi
    SystemCheck ..> ICloudStorage : Verifica Connessione

    %% =======================================================
    %% APPLICAZIONE STILI (CORRETTO)
    %% =======================================================
    
    %% Applicazione classe DOMAIN
    class Socio domain
    class DatiAnagrafici domain
    class Documento domain
    class ModuloIscrizione domain
    class ConsensoGDPR domain

    %% Applicazione classe SECURITY
    class UtenteSistema security
    class Amministratore security
    class Operatore security
    class AuditTrail security
    class AccessControlList security

    %% Applicazione classe INFRA
    class ICloudStorage infra
    class GoogleDriveAdapter infra
    class SharePointAdapter infra
    class OCREngine infra

    %% Applicazione classe ENUM
    class StatoDocumento enum
    class StatoIscrizione enum

    %% Applicazione classe DEBUG
    class SystemCheck debug
    class QueryLogger debug
    class UserErrorLogger debug
    class DatabaseInspector debug
    class LogViewer debug
```
