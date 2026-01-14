<?php

// tests/Integration/TransactionResilienceTest.php

use MCAG\GestioneSoci\Socio;
use MCAG\GestioneSoci\DatiAnagrafici;
use MCAG\Enum\StatoIscrizione;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use MCAG\GestioneSoci\ModuloIscrizione;
use MCAG\Enum\StatoDocumento;

test('verifica rollback su errore durante il salvataggio dei documenti', function () {
    $repo = new PDOSocioRepository($this->db);
    $cf = 'TXTEST99X99X9999';

    // Pulizia preventiva
    $this->db->exec("DELETE FROM soci WHERE codice_fiscale = '$cf'");

    // 1. Crea un socio valido
    $socio = new Socio();
    $socio->CodiceFiscale = $cf;
    $socio->Matricola = 'M111';
    $socio->Stato = StatoIscrizione::ATTIVO;

    $dati = new DatiAnagrafici();
    $dati->Nome = 'Transaction';
    $dati->Cognome = 'Test';
    $dati->DataNascita = new \DateTime('1980-01-01');
    $dati->Indirizzo = 'Via Roma 1';
    $dati->Email = 'tx@test.com';
    $socio->DatiPersonali = $dati;

    // 2. Aggiungi un documento che causerà un errore (es. ID mancante o violazione vincolo se possibile,
    // o semplicemente mockando il salvataggio se avessimo un mock repo, ma qui testiamo l'integrazione reale).
    // In questo caso, forzeremo un errore inserendo un documento con un campo non valido
    // o manipolando il repo dei documenti per fallire.

    $doc = new ModuloIscrizione();
    $doc->IdUnivoco = 'DOC-ERR-1';
    $doc->NomeFile = 'error.pdf';
    $doc->HashSHA256 = bin2hex(random_bytes(32));
    $doc->Stato = StatoDocumento::IN_ATTESA;
    $doc->DataCaricamento = new \DateTime();
    $doc->AnnoSolare = 2025;

    $socio->aggiungiDocumento($doc);

    // Per forzare un errore "reale" nel DB durante la transazione,
    // possiamo provare a sovrascrivere il repo documenti nel SocioRepository con uno che fallisce.
    $brokenDocRepo = new class ($this->db) extends \MCAG\InfrastrutturaIT\Persistence\PDODocumentoRepository {
        public function save($doc, $cf): void
        {
            throw new Exception("Simulated DB Failure during document save");
        }
    };

    // Reflection per iniettare il repo rotto
    $reflection = new \ReflectionClass($repo);
    $prop = $reflection->getProperty('docRepo');
    $prop->setAccessible(true);
    $prop->setValue($repo, $brokenDocRepo);

    // 3. Tenta il salvataggio
    try {
        $repo->save($socio);
    } catch (Exception $e) {
        expect($e->getMessage())->toBe("Simulated DB Failure during document save");
    }

    // 4. VERIFICA ATOMICITA': Il socio non deve esistere nel DB perché la transazione deve essere andata in rollback
    $stmt = $this->db->prepare("SELECT * FROM soci WHERE codice_fiscale = ?");
    $stmt->execute([$cf]);
    $result = $stmt->fetch();

    expect($result)->toBeFalse(); // Il socio non deve essere stato salvato
});
