<?php

namespace MCAG\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;

class PolicyController
{
    private Mustache_Engine $engine;

    public function __construct(Mustache_Engine $engine)
    {
        $this->engine = $engine;
    }

    public function privacy(Request $request, Response $response): Response
    {
        $policyContent = '
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h1 class="mb-4 text-primary"><i class="fa-solid fa-user-shield me-2"></i>Privacy Policy</h1>
                    <p class="lead text-muted">Informativa ai sensi degli art. 13-14 del Regolamento UE 2016/679 (GDPR).</p>
                    <hr class="my-4">
                    
                    <h4>1. Titolare del Trattamento</h4>
                    <p>Il Titolare del trattamento è <strong>MCAG - Militare Civile Archivio Gestionale</strong>.</p>
                    
                    <h4>2. Tipologia di Dati Trattati</h4>
                    <p>Il sistema tratta esclusivamente dati personali necessari per la gestione dell\'archivio soci e la sicurezza del sistema:</p>
                    <ul>
                        <li>Dati Anagrafici (Nome, Cognome, Codice Fiscale, Contatti).</li>
                        <li>Dati di Accesso e Log di Sicurezza (IP, Timestamp, User Agent).</li>
                    </ul>
                    
                    <h4>3. Finalità del Trattamento</h4>
                    <p>I dati sono trattati per:</p>
                    <ul>
                        <li>Gestione amministrativa dell\'associazione.</li>
                        <li>Garanzia di sicurezza e integrità del sistema informatico (Audit Log).</li>
                        <li>Adempimento di obblighi legali e fiscali.</li>
                    </ul>
                    
                    <h4>4. Diritti dell\'Interessato</h4>
                    <p>Gli interessati possono esercitare i diritti di accesso, rettifica, cancellazione e opposizione contattando il DPO all\'indirizzo: <a href="mailto:privacy@mcag.system">privacy@mcag.system</a>.</p>
                </div>
            </div>
        ';

        $html = $this->engine->render('layout/layout', [
            'content' => $policyContent,
            'title' => 'Privacy Policy - MCAG',
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function cookie(Request $request, Response $response): Response
    {
        $cookieContent = '
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h1 class="mb-4 text-info"><i class="fa-solid fa-cookie-bite me-2"></i>Cookie Policy</h1>
                    <p class="lead text-muted">Informativa estesa sull\'utilizzo dei Cookie e tecnologie similari.</p>
                    <hr class="my-4">
                    
                    <h4>1. Cosa sono i Cookie?</h4>
                    <p>I cookie sono piccoli file di testo che i siti visitati inviano al terminale dell\'utente, dove vengono memorizzati, per poi essere ritrasmessi agli stessi siti alla visita successiva.</p>
                    
                    <h4>2. Tipologie di Cookie utilizzate</h4>
                    <table class="table table-bordered mt-3">
                        <thead class="table-light">
                            <tr>
                                <th>Tipologia</th>
                                <th>Descrizione</th>
                                <th>Durata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Cookie Tecnici (Sessione)</strong></td>
                                <td>Necessari per l\'autenticazione e la navigazione sicura (es. PHPSESSID).</td>
                                <td>Sessione</td>
                            </tr>
                            <tr>
                                <td><strong>Cookie di Sicurezza</strong></td>
                                <td>Utilizzati per prevenire attacchi CSRF e garantire l\'integrità.</td>
                                <td>Sessione</td>
                            </tr>
                            <tr>
                                <td><strong>Preferenze (LocalStorage)</strong></td>
                                <td>Salviamo la scelta sul banner dei cookie (chiave: <code>cookieConsented</code>).</td>
                                <td>Persistente</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-success mt-4">
                        <i class="fa-solid fa-check-circle me-2"></i>
                        <strong>Nessun Tracciamento Pubblicitario</strong><br>
                        Questo sito NON utilizza cookie di profilazione o cookie di terze parti per finalità di marketing.
                    </div>
                    
                    <h4>3. Gestione del Consenso</h4>
                    <p>L\'utente può gestire le preferenze sui cookie direttamente dalle impostazioni del proprio browser o cliccando sul pulsante nel banner informativo.</p>
                </div>
            </div>
        ';

        $html = $this->engine->render('layout/layout', [
            'content' => $cookieContent,
            'title' => 'Cookie Policy - MCAG',
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}


